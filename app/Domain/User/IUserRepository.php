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
     * @return int|bool 成功返回 uid，失败返回 false
     */
    public function add(User $user);

    /**
     * 根据 UserId 获取用户信息
     * @param UserId $userId
     * @return UserDTO
     */
    public function getDTOByUserId(UserId $userId): ?UserDTO;
}
