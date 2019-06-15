<?php

namespace App\Foundation\Repository\User;

use App\Domain\User\IMerchantRepository;
use WecarSwoole\Repository\MySQLRepository;

class MySQLMerchantRepository extends MySQLRepository implements IMerchantRepository
{
    public function add()
    {
        return $this->query->insert('merchant_users')
            ->values([
                'merchant_type' => 1,
                'merchant_id' => mt_rand(100, 10000),
                'uid' => 12345,
                'channel' => '测试'
            ])
            ->execute();
    }

    public function dbAlias(): string
    {
        return 'user_center';
    }
}