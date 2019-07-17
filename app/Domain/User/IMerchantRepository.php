<?php

namespace App\Domain\User;

/**
 * 商户聚合仓储
 * Interface IMerchantRepository
 * @package App\Domain\User
 */
interface IMerchantRepository
{
    /**
     * @param Merchant $merchant
     * @return mixed
     */
    public function save(Merchant $merchant);

    /**
     * 用户合并是迁移绑定关系
     * @param User $targetUser
     * @param User $abandonUser
     * @return mixed
     */
    public function mergeMerchantUser(User $targetUser, User $abandonUser);

    /**
     * 用户是否已经绑定到商户
     * @param User $user
     * @return bool
     */
    public function isUserAlreadyBound(Merchant $merchant, User $user): bool;
}
