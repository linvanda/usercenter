<?php

namespace App\Foundation\Repository;

use WecarSwoole\Repository\MySQLRepository;

class MySQLUserCenterRepository extends MySQLRepository
{
    protected function dbAlias(): string
    {
        return 'weicheche';
    }
}
