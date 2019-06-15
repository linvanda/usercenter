<?php

namespace App\Domain\User;

/**
 * 商户聚合仓储
 * Interface IMerchantRepository
 * @package App\Domain\User
 */
interface IMerchantRepository
{
    public function add(Merchant $merchant);
}