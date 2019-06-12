<?php

namespace App\Foundation\Repository;

use App\Domain\User\User;
use App\Domain\User\IUserRepository;
use WecarSwoole\Repository\MySQLRepository;

/**
 * MySQL 版仓储实现
 * Class MySQLUserRepository
 * @package App\Foundation\Repository
 */
class MySQLUserRepository extends MySQLRepository implements IUserRepository
{
    /**
     * 添加用户
     * @param User $user
     * @return int|bool 成功返回 uid，失败返回 false
     */
    public function add(User $user)
    {
        $this->query->insert('users')->values([
            [
                'name' => $user->name,
                'phone' => $user->phone,
                'nickname' => $user->nickname,
            ]
        ])->execute();

        return $this->query->lastInsertId();
    }

    public function addTest()
    {
        $this->query->insert('users')->values([
            'name' => '测试事务',
            'phone' => mt_rand(100000, 9999999999),
            'nickname' => '昵称',
        ])->execute();
    }

    /**
     * 根据 uid 获取用户
     * @param int $uid
     * @return User
     * @throws \App\Exceptions\PropertyNotFoundException
     * @throws \App\Exceptions\InvalidOperationException
     */
    public function getById(int $uid): ?User
    {
        $userInfo = $this->query->select('*')->from('users')->where(['uid' => $uid])->one();

        if ($userInfo) {
            $user = new User($userInfo['phone'], $userInfo['name'], $userInfo['nickname']);
            $user->setId($userInfo['uid']);
            return $user;
        }

        return null;
    }
}