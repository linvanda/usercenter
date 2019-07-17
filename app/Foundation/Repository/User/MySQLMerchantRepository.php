<?php

namespace App\Foundation\Repository\User;

use App\Domain\User\User;
use App\Foundation\Repository\MySQLUserCenterRepository;
use App\Domain\User\IMerchantRepository;
use App\Domain\User\Merchant;

class MySQLMerchantRepository extends MySQLUserCenterRepository implements IMerchantRepository
{
    /**
     * @param Merchant $merchant
     * @return mixed|void
     * @throws \Exception
     */
    public function save(Merchant $merchant)
    {
        // 目前仅支持 group_id 的存储
        if (!$merchant->users() || $merchant->type() !== Merchant::T_GROUP) {
            return;
        }

        foreach ($merchant->users() as $user) {
            $this->query->replace('wei_user_account')
                ->values(
                    [
                        'phone' => $user->phone() ?: '',
                        'birthday' => $user->birthday,
                        'group_id' => $merchant->id(),
                        'uid' => $user->uid(),
                        'nickname' => $user->nickname
                    ]
                )->execute();
        }
    }

    /**
     * @param User $targetUser
     * @param User $abandonUser
     * @return mixed|void
     * @throws \Exception
     */
    public function mergeMerchantUser(User $targetUser, User $abandonUser)
    {
        if ($targetUser->equal($abandonUser)) {
            return;
        }

        // 备注：目前 wei_user_account 并没有真正用起来，此处直接删除 abandonUser的数据
        $this->query->update('wei_user_account')
            ->set(['is_valid' => 0])
            ->where(['uid' => $abandonUser->uid()])
            ->execute();
    }

    /**
     * @param Merchant $merchant
     * @param User $user
     * @return bool
     * @throws \Dev\MySQL\Exception\DBException
     * @throws \Exception
     */
    public function isUserAlreadyBound(Merchant $merchant, User $user): bool
    {
        return (bool)$this->query->select('uaid')
            ->from('wei_user_account')
            ->where(['uid' => $user->uid(), 'group_id' => $merchant->id()])
            ->column();
    }
}
