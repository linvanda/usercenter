<?php

namespace App\Domain\User;

class MergeService
{
    /**
     * 合并两个用户
     * @param User $user1
     * @param User $user2
     * @param bool $useFirstAsTarget 是否强制将第一个用户选为目标用户（第二个合并到第一个身上）,默认系统会自己选择合并目标
     * @return User 返回目标用户（生效到那个）
     */
    public function merge(User $user1, User $user2, bool $useFirstAsTarget = false): User
    {
        $targetUser = $abandonUser = null;

        if ($useFirstAsTarget) {
            $targetUser = $user1;
            $abandonUser = $user2;
        } else {
            list($targetUser, $abandonUser) = $this->chooseTargetAndAbandonUser($user1, $user2);
        }


    }

    private function chooseTargetAndAbandonUser(User $user1, User $user2): array
    {
        return [$user1, $user2];
    }
}
