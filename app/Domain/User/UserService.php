<?php

namespace App\Domain\User;

use App\Domain\Events\UserAddedEvent;
use App\Domain\Events\UserUpdatedEvent;
use App\DTO\User\UserDTO;
use App\Exceptions\InvalidPhoneException;
use App\Exceptions\UserRegisterConflictException;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    private $userRepository;
    private $divergeService;
    private $eventDispatcher;

    public function __construct(
        IUserRepository $userRepository,
        DivergeService $divergeService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->divergeService = $divergeService;
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
                    '用户数据存在异常，需要人工处理',
                    503,
                    [
                        'new_phone' => $userOfPhone->phone,
                        'new_partner' => $userDTO->partners->first()->toArray()
                    ]
                );
            }
        }

        // 只有 partner 查出了记录
        if ($userDTO->phone && $userOfPartner->phone) {
            // 两者的 phone 不一致，抛出异常
            throw new UserRegisterConflictException(
                '用户数据存在异常，需要人工处理',
                504,
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
     * @return User 返回更新后的 User
     */
    public function updateUser(UserDTO $userDTO, int $updateStrategy = User::UPDATE_NEW): User
    {
        // TODO
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

        // 发布用户信息更新事件
        $this->eventDispatcher->dispatch(new UserUpdatedEvent($oldUser, $user));

        return $user;
    }

    private function add(UserDTO $userDTO): User
    {
        $user = $this->userRepository->add(new User($userDTO));

        // 发布用户添加事件
        $this->eventDispatcher->dispatch(new UserAddedEvent($user));
        return $user;
    }
}
