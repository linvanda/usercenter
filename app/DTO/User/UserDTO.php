<?php

namespace App\DTO\User;

use App\Domain\User\PartnerUser;
use App\Domain\User\PartnerUserMap;
use App\Domain\User\User;
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
     * @var PartnerUserMap
     */
    public $partnerUsers;

    public function __construct(array $data = [], bool $strict = true, bool $mapping = true)
    {
        parent::__construct($data, $strict, $mapping);

        if ($this->partnerUsers === null) {
            $this->partnerUsers = new PartnerUserMap([]);
        }
    }

    public function toArray(
        bool $camelToSnake = true,
        bool $withNull = true,
        bool $zip = false,
        array $exFields = []
    ): array {
        $arr = parent::toArray($camelToSnake, $withNull, $zip, $exFields);

        if (!$this->partnerUsers || !count($this->partnerUsers)) {
            $arr['partner_users'] = [];
        } else {
            // 解析 partnerUsers
            $arr['partner_users'] = array_map(function (PartnerUser $partnerUser) {
                return $partnerUser->toArray();
            }, $this->partnerUsers->getArrayCopy());
        }

        return $arr;
    }
}
