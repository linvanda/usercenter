<?php

namespace App\Domain\User;

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
     * 根据 uid 获取用户
     * @param int $uid
     * @return User
     */
    public function getById(int $uid): ?User;

    public function addTest();
}