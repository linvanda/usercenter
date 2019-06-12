<?php

namespace App\Domain\User;

use App\Domain\Entity;

class Merchant extends Entity
{
    public function __construct(int $id, int $type)
    {
        $this->id = new MerchantId($id, $type);
    }
}