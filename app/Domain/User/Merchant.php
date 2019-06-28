<?php

namespace App\Domain\User;

use WecarSwoole\Entity;

class Merchant extends Entity
{
    public const T_PLATFORM = 0;
    public const T_STATION = 1;
    public const T_GROUP = 2;
    public const T_ORG = 3;
}
