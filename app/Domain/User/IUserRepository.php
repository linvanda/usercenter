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

    public function update(User $user, User $oldUser = null);

    /**
     * 添加 partner
     * @param User $user
     * @param Partner $partner
     */
    public function addPartner(User $user, Partner $partner);

    public function saveMerge(User $targetUser, User $abandonUser);

    public function delete(User $user);

    /**
     * 根据 UserId 获取用户信息
     * @param UserId $userId
     * @return UserDTO
     */
    public function getDTOByUserId(UserId $userId): ?UserDTO;

    public function getUserByPartner(?Partner $partner): ?User;

    public function getUserByPhone($phone = ''): ?User;

    public function getUserByUid(int $uid): ?User;

    public function isPhoneBeUsed($phone): bool;

    public function clearUserCache(int $uid);
}
