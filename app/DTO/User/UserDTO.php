<?php

namespace App\DTO\User;

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
}