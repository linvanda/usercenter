<?php

namespace App\DTO\User;

use App\Domain\User\Merchant;
use App\Domain\User\Partner;
use App\Domain\User\PartnerMap;
use WecarSwoole\DTO;

/**
 * 用户信息 DTO
 * Class UserDTO
 * @package App\DTO\User
 */
class UserDTO extends DTO
{
    public $uid;
    /**
     * @var array 关联 uid，即曾经被合到该用户身上的 uid 集合，包括其自身的 uid
     */
    public $relUids;
    public $phone;
    public $name;
    public $nickname;
    /**
     * @var int 性别：1=>男,2=>女,0=>未知
     */
    public $gender;
    public $birthday;
    public $regtime;
    public $headurl;
    /**
     * @field tinyheadurl
     */
    public $tinyHeadurl;
    /**
     * 用户来源
     * @field channel
     * @var string
     */
    public $registerFrom;
    /**
     * 车牌号列表
     * @var array
     */
    public $carNumbers;
    /**
     * 生日修改次数
     * @var int
     */
    public $birthdayChange;
    /**
     * 邀请码
     * @var string
     */
    public $inviteCode;
    /**
     * 用户的第三方标识列表
     * @var PartnerMap
     */
    public $partners;

    /**
     * @var Merchant
     */
    public $merchant;

    public function __construct(array $data = [], bool $strict = true, bool $mapping = true)
    {
        parent::__construct(self::formatOrigionData($data), $strict, $mapping);
    }

    public function toArray(
        bool $camelToSnake = true,
        bool $withNull = true,
        bool $zip = false,
        array $exFields = []
    ): array {
        $arr = parent::toArray($camelToSnake, $withNull, $zip, $exFields);

        // partners 的处理
        if (!$this->partners || !count($this->partners)) {
            $arr['partners'] = [];
        } else {
            // 解析 partners
            $arr['partners'] = array_map(function (Partner $partner) {
                return $partner->toArray();
            }, $this->partners->getArrayCopy());
        }

        // merchant 的处理
        unset($arr['merchant']);

        return $arr;
    }

    private static function formatOrigionData(array $data)
    {
        /**
         * partners 的处理
         */
        // 从二维数组构建对象
        if (self::hasValidPartnersArr($data)) {
            $data['partners'] = new PartnerMap(array_map(function ($item) {
                return new Partner($item['user_id'], $item['type'], $item['flag'] ?? null);
            }, $data['partners']));
        } elseif (isset($data['partner_type']) && isset($data['partner_id'])) {
            $data['partners'] = new PartnerMap(
                [new Partner($data['partner_id'], $data['partner_type'], $data['partner_flag'])]
            );
        } elseif (isset($data['partners']) && !$data['partners'] instanceof PartnerMap) {
            $data['partners'] = new PartnerMap([]);
        }

        /**
         * merchant 的处理
         */
        $data['merchant'] = new Merchant(
            $data['merchant_id'] ?? 0,
            $data['merchant_type'] ?? Merchant::T_PLATFORM
        );

        return $data;
    }

    private static function hasValidPartnersArr(array $data): bool
    {
        return isset($data['partners']) &&
        $data['partners'] &&
        is_array($data['partners']) &&
        is_array($data['partners'][0]);
    }
}
