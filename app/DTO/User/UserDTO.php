<?php

namespace App\DTO\User;

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
     * @mapping 1=>男,2=>女,0=>未知
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

    public function __construct(array $data = [], bool $strict = true, bool $mapping = true)
    {
        // partners 特殊处理：从二维数组构建对象
        if (isset($data['partners']) &&
            $data['partners'] &&
            is_array($data['partners']) &&
            is_array($data['partners'][0])
        ) {
            $data['partners'] = new PartnerMap(array_map(function ($item) {
                return new Partner($item['user_id'], $item['type'], $item['flag'] ?? null);
            }, $data['partners']));
        }

        parent::__construct($data, $strict, $mapping);

        if ($this->partners === null) {
            $this->partners = new PartnerMap([]);
        }
    }

    public function toArray(
        bool $camelToSnake = true,
        bool $withNull = true,
        bool $zip = false,
        array $exFields = []
    ): array {
        $arr = parent::toArray($camelToSnake, $withNull, $zip, $exFields);
        if (!$this->partners || !count($this->partners)) {
            $arr['partners'] = [];
        } else {
            // 解析 partners
            $arr['partners'] = array_map(function (Partner $partner) {
                return $partner->toArray();
            }, $this->partners->getArrayCopy());
        }

        return $arr;
    }
}
