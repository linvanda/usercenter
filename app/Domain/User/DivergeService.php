<?php

namespace App\Domain\User;
use App\Exceptions\UserRegisterConflictException;

/**
 * 用户分歧处理
 * Class DivergeService
 * @package App\Domain\User
 */
class DivergeService
{
    private $userRepository;
    private $mergeService;

    public function __construct(IUserRepository $userRepository, MergeService $mergeService)
    {
        $this->userRepository = $userRepository;
        $this->mergeService = $mergeService;
    }

    /**
     * 处理分歧
     * @param User $newUser 计划插入的用户，根据该用户信息查到了两个分歧用户
     * @param User $userOfPhone 根据 $newUser 的手机号查到的用户
     * @param User $userOfPartner 根据 $newUser 的 partner 信息查到的用户
     * @return int 处理完分歧，选中的正确的那个用户的uid
     * @throws UserRegisterConflictException
     */
    public function dealDivergence(User $newUser, User $userOfPhone, User $userOfPartner): int
    {
        if ($userOfPartner->equal($userOfPhone)) {
            return $userOfPhone->userId->getUid();
        }

        $errContext = [
            'new_phone' => $newUser->userId->getPhone(),
            'new_partner' => $newUser->userId->getPartnerUser()->toArray(),
            'extra' => '根据新用户的phone和partner信息查出两条不一样的记录'
        ];

        // partner 查出来的有 phone，属于无法自动处理的分歧，需要人工处理
        if ($userOfPartner->userId->getPhone()) {
            throw new UserRegisterConflictException("用户数据存在异常，需要人工处理", 502, $errContext);
        }

        // phone 查出来的有同类型 partner（如另一个微信大号），也需要人工处理
        $newUserPartner = $newUser->userId->getPartnerUser();
        if ($userOfPhone->userId->getPartnerUser($newUserPartner->type(), $newUserPartner->flag())) {
            throw new UserRegisterConflictException("用户数据存在冲突，需要人工处理", 503, $errContext);
        }

        // partner 的没有 phone，phone 的没有 partner，将 partner 用户合并到 phone 用户身上
        return $this->mergeService->merge($userOfPhone, $userOfPartner, true);
    }
}
