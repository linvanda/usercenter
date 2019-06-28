<?php

namespace App\Domain\User;

class MergeService
{
    /**
     * 合并两个用户
     * @param User $user1
     * @param User $user2
     * @param bool $useFirstAsTarget 是否强制将第一个用户选为目标用户（第二个合并到第一个身上）,默认系统会自己选择合并目标
     * @return int 合并成功则返回目标用户（生效到那个）uid，失败返回 0
     */
    public function merge(User $user1, User $user2, bool $useFirstAsTarget = false): int
    {

    }
}
