<?php

namespace App\Domain\User;

use WecarSwoole\Entity;

class User extends Entity
{
    /**
     * @var string
     * @field
     */
    protected $name;
    /**
     * @var string
     * @field
     */
    protected $nickname;
    /**
     * @var string
     * @field
     */
    protected $phone;

    /**
     * @var string
     * @field
     * @mapping 男=>1,女=>0
     */
    protected $gender;

    public function __construct($phone = '', $name = '', $nickname = '')
    {
        $this->phone = $phone;
        $this->name = $name;
        $this->nickname = $nickname;
    }
}