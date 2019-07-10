<?php

namespace App\Domain\User;

use Swoole\Exception;
use WecarSwoole\Util\AutoProperty;

/**
 * 用户标识
 * Class UserId
 * @package App\Domain\User
 */
class UserId
{
    use AutoProperty;

    public const FLAG_UID = 1;
    public const FLAG_REL_UIDS = 2;
    public const FLAG_PHONE = 3;
    public const FLAG_PARTNER = 4;

    protected $uid;
    /**
     * 被合并的关联用户 uid
     * @var array
     */
    protected $relUids;
    protected $phone;
    /**
     * 第三方用户标识列表
     * @var PartnerUserMap
     */
    protected $partnerUsers;

    /**
     * UserId constructor.
     * @param int|null $uid
     * @param string|null $phone
     * @param array $relUids
     * @param PartnerUser $partner
     */
    public function __construct(
        int $uid = null,
        string $phone = null,
        array $relUids = [],
        PartnerUserMap $partners = null
    ) {
        $this->setProperties(func_get_args());
        $this->partnerUsers = $partners ?? new PartnerUserMap();
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     * @return bool
     */
    public function setUid(int $uid)
    {
        if ($this->uid && $this->uid != $uid) {
            return false;
        }

        $this->uid = $uid;
        return true;
    }

    public function getRelUids(): array
    {
        return $this->relUids;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getPartnerUsers(): PartnerUserMap
    {
        return $this->partnerUsers;
    }

    /**
     * 添加用户的第三方标识
     * @param PartnerUser $partner
     */
    public function addPartnerUser(PartnerUser $partner)
    {
        $this->partnerUsers[$partner->getPartnerKey()] = $partner;
    }

    /**
     * 获取用户在某第三方的标识
     * @param int $type
     * @param string $flag
     * @return PartnerUser|null
     */
    public function getPartnerUser(?int $type = null, $flag = null): ?PartnerUser
    {
        if ($type === null) {
            // 取第一个
            return $this->partnerUsers->first();
        }
        return $this->partnerUsers[PartnerUser::getPartnerKeyStatic($type, $flag)];
    }

    public function modify(?PartnerUser $partnerUser, $phone = '', bool $onlyModifyIfNull = false)
    {
        if ($partnerUser) {
            $key = $partnerUser->getPartnerKey();
            if (!$onlyModifyIfNull || !isset($this->partnerUsers[$key])) {
                $this->partnerUsers[$key] = $partnerUser;
            }
        }

        $this->phone = $onlyModifyIfNull ? ($this->phone ?: $phone) : ($phone ?: $this->phone);
    }
}
