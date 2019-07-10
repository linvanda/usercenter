<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;
use App\Exceptions\InvalidPhoneException;

class UserService
{
    private $userRepository;
    protected $mergeService;
    protected $divergeService;

    public function __construct(
        IUserRepository $userRepository,
        MergeService $mergeService,
        DivergeService $divergeService
    ) {
        $this->userRepository = $userRepository;
        $this->mergeService = $mergeService;
        $this->divergeService = $divergeService;
    }

    /**
     * 添加新用户
     * 注意此处没有用"注册"概念，因为业务上说，注册是必须要手机号的，此处可以没有手机号
     * @param UserDTO $userDTO 新用户信息
     * @param int $updateStrategy 更新策略(如果需要走更新而不是添加的话)
     * @return User
     */
    public function addUser(UserDTO $userDTO, int $updateStrategy = User::UPDATE_ONLY_NULL): User
    {
        $thisPartnerUser = $userDTO->partnerUsers->first();

        // 新用户没有 partner 和 phone 信息，则直接新增用户记录
        if (!$thisPartnerUser && !$userDTO->phone) {
            return $this->userRepository->add(new User($userDTO));
        }

        $userOfPartner = $this->userRepository->getUserByPartner($thisPartnerUser);
        $userOfPhone = $this->userRepository->getUserByPhone($userDTO->phone ?? '');

        // partner 和 phone 都没有查到用户记录，说明是完全的新用户，直接添加
        if (!$userOfPartner && !$userOfPhone) {
            return $this->userRepository->add(new User($userDTO));
        }

        // 查到两条记录
        if ($userOfPartner && $userOfPhone) {
            // 两条记录如果是同一个人，则更新
            if ($userOfPartner->equal($userOfPhone)) {
                return $this->update($userOfPartner, $userDTO, $updateStrategy);
            }

            // 两条记录不是同一个人，需要进行分歧处理
            if ($checkedUid = $this->divergeService->dealDivergence($this, $userOfPhone, $userOfPartner)) {
                $this->userId->setUid($checkedUid);
            }
            return $checkedUid;
        }

        if ($userOfPhone) {
            return $this->registerWhenPhoneExistsOnly($userOfPhone, $updateStrategy);
        }

        return $this->registerWhenPartnerExistsOnly($userOfPartner, $updateStrategy);
    }

    /**
     * 更新用户信息
     * @param UserDTO $userDTO
     * @param int $updateStrategy
     * @return User 返回更新后的 User
     */
    public function updateUser(UserDTO $userDTO, int $updateStrategy = User::UPDATE_NEW): User
    {
        //
    }

    /**
     * @param User $user
     * @param UserDTO $userDTO
     * @return User 返回更新后的 User
     * @throws InvalidPhoneException
     * @throws \WecarSwoole\Exceptions\Exception
     */
    private function update(User $user, UserDTO $userDTO, int $updateStrategy): User
    {
        $user->updateFromDTO($userDTO, $this->userRepository, $updateStrategy);
        $this->userRepository->update($user);

        return $user;
    }
}
