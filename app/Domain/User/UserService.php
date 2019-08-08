<?php

namespace App\Domain\User;

use App\Domain\Events\UserAddedEvent;
use App\Domain\Events\UserUpdatedEvent;
use App\DTO\User\UserDTO;
use App\ErrCode;
use App\Exceptions\InvalidPhoneException;
use App\Exceptions\UserRegisterConflictException;
use Psr\EventDispatcher\EventDispatcherInterface;
use WecarSwoole\Exceptions\Exception;

class UserService
{
    private $userRepository;
    private $divergeService;
    private $merchantService;
    private $eventDispatcher;

    public function __construct(
        IUserRepository $userRepository,
        DivergeService $divergeService,
        MerchantService $merchantService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->divergeService = $divergeService;
        $this->merchantService = $merchantService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * 添加新用户
     * 注意此处没有用"注册"概念，因为业务上说，注册是必须要手机号的，此处可以没有手机号
     * @param UserDTO $userDTO
     * @param int $updateStrategy
     * @return User
     * @throws InvalidPhoneException
     * @throws UserRegisterConflictException
     * @throws \App\Exceptions\BirthdayException
     * @throws \App\Exceptions\PartnerException
     * @throws \WecarSwoole\Exceptions\Exception
     */
    public function addUser(UserDTO $userDTO, int $updateStrategy = User::UPDATE_ONLY_NULL): User
    {
        // 新用户没有 partner 和 phone 信息，则直接新增用户记录
        if (!count($userDTO->partners) && !$userDTO->phone) {
            return $this->add($userDTO);
        }

        $userOfPartner = $this->userRepository->getUserByPartner($userDTO->partners->first());
        $userOfPhone = $this->userRepository->getUserByPhone($userDTO->phone ?? '');

        // partner 和 phone 都没有查到用户记录，说明是完全的新用户，直接添加
        if (!$userOfPartner && !$userOfPhone) {
            return $this->add($userDTO);
        }

        // 查到两条记录
        if ($userOfPartner && $userOfPhone) {
            // 两条记录如果是同一个人，则更新
            if ($userOfPartner->equal($userOfPhone)) {
                return $this->update($userOfPartner, $userDTO, $updateStrategy);
            }

            // 两条记录不是同一个人，需要进行分歧处理
            return $this->divergeService->dealDivergence($userDTO, $userOfPhone, $userOfPartner);
        }

        // 只有 phone 查出了记录
        if ($userOfPhone) {
            if ($userOfPhone->partners()->isDivergent($userDTO->partners)) {
                // phone 查出来的用户的 partner 和当前的不一致，抛出异常
                throw new UserRegisterConflictException(
                    "用户{$userDTO->phone}数据存在异常，需要人工处理",
                    ErrCode::USER_DATA_ANOMALY,
                    [
                        'new_phone' => $userOfPhone->phone(),
                        'new_partner' => $userDTO->partners->first()->toArray()
                    ]
                );
            } else {
                // 更新
                return $this->update($userOfPhone, $userDTO, $updateStrategy);
            }
        }

        // 只有 partner 查出了记录
        if ($userDTO->phone && $userOfPartner->phone()) {
            // 两者的 phone 不一致，抛出异常
            throw new UserRegisterConflictException(
                "您已用手机{$userOfPartner->phone()}注册过，如需帮助，请联系工作人员",
                ErrCode::PHONE_ALREADY_EXIST,
                [
                    'new_phone' => $userDTO->phone,
                    'new_partner' => $userDTO->partners->first()->toArray()
                ]
            );
        }

        return $this->update($userOfPartner, $userDTO, $updateStrategy);
    }

    /**
     * 更新用户信息
     * @param UserDTO $userDTO
     * @param int $updateStrategy
     * @throws Exception
     * @throws InvalidPhoneException
     * @throws \App\Exceptions\BirthdayException
     * @throws \App\Exceptions\PartnerException
     */
    public function updateUser(UserDTO $userDTO, int $updateStrategy = User::UPDATE_NEW)
    {
        if (!$user = $this->userRepository->getUserByUid($userDTO->uid)) {
            throw new Exception("用户不存在:{$userDTO->uid}", ErrCode::USER_NOT_EXIST);
        }

        $this->update($user, $userDTO, $updateStrategy, true);
    }

    /**
     * 给用户绑定第三方标识
     * @param int $uid
     * @param Partner $partner
     * @throws Exception
     */
    public function bindPartner(int $uid, Partner $partner)
    {
        if (!$user = $this->userRepository->getUserByUid($uid)) {
            throw new Exception("用户不存在:{$uid}", ErrCode::USER_NOT_EXIST);
        }

        $oldUser = clone $user;
        $user->addPartner($partner);
        $this->userRepository->addPartner($user, $partner);

        // 发布更新事件
        $this->eventDispatcher->dispatch(new UserUpdatedEvent($oldUser, $user));
    }

    /**
     * 将 $userDTO 的信息更新到 $user 上
     * @param User $user
     * @param UserDTO $userDTO
     * @param int $updateStrategy 更新策略
     * @param bool $forceChangePhone 是否强制更新手机
     * @return User 返回更新后的 User
     * @throws InvalidPhoneException
     * @throws \App\Exceptions\BirthdayException
     * @throws \App\Exceptions\PartnerException
     * @throws \WecarSwoole\Exceptions\Exception
     */
    private function update(User $user, UserDTO $userDTO, int $updateStrategy, $forceChangePhone = false): User
    {
        $oldUser = clone $user;
        $user->updateFromDTO($userDTO, $this->userRepository, $updateStrategy, $forceChangePhone);
        $this->userRepository->update($user);

        // merchant
        $this->merchantService->bindUser($userDTO->merchant, $user);

        // 发布用户信息更新事件
        $this->eventDispatcher->dispatch(new UserUpdatedEvent($oldUser, $user));

        return $user;
    }

    private function add(UserDTO $userDTO): User
    {
        $user = $this->userRepository->add(new User($userDTO));

        // merchant
        $this->merchantService->bindUser($userDTO->merchant, $user);

        // 发布用户添加事件
        $this->eventDispatcher->dispatch(new UserAddedEvent($user));

        return $user;
    }
}
