<?php

namespace App\Domain\User;

/**
 * 第三方用户
 * Class Partner
 * @package App\Domain\User
 */
class Partner
{
    /**
     * 第三方类型
     */
    public const P_WEIXIN = 1;
    public const P_ALIPAY = 2;
    public const P_OTHER = 100;

    protected $id;
    protected $type;

    public function __construct(string $id, int $type)
    {
        $this->id = $id;
        $this->type = $type;
    }

    public function id()
    {
        return $this->id;
    }

    public function type(): int
    {
        return $this->type;
    }
}
