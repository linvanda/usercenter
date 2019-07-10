<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;

/**
 * 用户聚合仓储
 * Interface IUserRepository
 * @package App\Domain\User
 */
interface IUserRepository
{
    /**
     * 添加用户
     * @param User $user
     * @return User 新增的用户
     */
    public function add(User $user): User;

    /**
     * 根据 UserId 获取用户信息
     * @param UserId $userId
     * @return UserDTO
     */
    public function getDTOByUserId(UserId $userId): ?UserDTO;

    public function getUserByPartner(?PartnerUser $partnerUser): ?User;

    public function getUserByPhone($phone = ''): ?User;

    public function getUserByUid(int $uid): ?User;

    public function update(User $user);

    public function isPhoneBeUsed($phone): bool;
}
