<?php

namespace App\Domain\User;
use WecarSwoole\OTA\IExtractable;
use WecarSwoole\OTA\ObjectToArray;

/**
 * 第三方用户标识
 * type + flag 唯一确定一个第三方（如微信大号），id 则是这个第三方的 id（如大号 openid）
 * Class Partner
 * @package App\Domain\User
 */
class Partner implements IExtractable
{
    use ObjectToArray;

    /**
     * 第三方类型
     */
    public const P_WEIXIN = 1; // 微信大号
    public const P_ALIPAY = 2; // 支付宝大号
    public const P_WX_ACCOUNT = 3; // 微信公众号（油站的公众号，小号）
    public const P_OTHER = 100; // 其它类型，一般是各种合作第三方
    // 支付宝和微信大号的flag
    protected const FLAGS = [
        self::P_WEIXIN => 'wxb44ac0b31fbb1c11',
        self::P_ALIPAY => '2016091801918137'
    ];

    // 第三方的用户编号，如微信大号 openid
    protected $userId;
    // 第三方类型，见前面的常量定义
    protected $type;
    // 第三方标识，如公众号 app_id。由于微信
    protected $flag;

    public function __construct($userId, int $type, $flag)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->flag = $flag;
    }

    public function userId()
    {
        return $this->userId;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function flag()
    {
        return $this->flag;
    }

    /**
     * 返回第三方的唯一标识，根据 type 和 flag 生成
     * @return string
     */
    public function getPartnerKey(): string
    {
        return self::getPartnerKeyStatic($this->type, $this->flag);
    }

    public static function getPartnerKeyStatic(int $type, $flag): string
    {
        return $type . '-' . ($flag ?? self::FLAGS[$type]);
    }

    public function equal(?Partner $partner): bool
    {
        if (!$partner) {
            return false;
        }

        return $this->userId === $partner->userId &&
            $this->type === $partner->type &&
            $this->flag === $partner->flag;
    }
}
