<?php

namespace App\Domain\User;

use Swoole\Exception;
use WecarSwoole\Exceptions\InvalidOperationException;
use WecarSwoole\Util\AutoProperty;

/**
 * 用户标识
 * Class UserId
 * @package App\Domain\User
 */
class UserId
{
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
     * @var PartnerMap
     */
    protected $partners;

    /**
     * UserId constructor.
     * @param int|null $uid
     * @param string|null $phone
     * @param array $relUids
     * @param PartnerMap|null $partners
     */
    public function __construct(
        int $uid = null,
        string $phone = null,
        array $relUids = [],
        PartnerMap $partners = null
    ) {
        $this->uid = $uid;
        $this->phone = $phone;
        $this->relUids = $relUids ?? [];
        $this->partners = $partners ?? new PartnerMap();
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

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public function getPartners(): PartnerMap
    {
        return $this->partners;
    }

    /**
     * 添加用户的第三方标识
     * @param Partner $partner
     */
    public function addPartner(Partner $partner)
    {
        $this->partners[$partner->getPartnerKey()] = $partner;
    }

    /**
     * 获取用户在某第三方的标识
     * @param int $type
     * @param string $flag
     * @return Partner|null
     * @throws InvalidOperationException
     */
    public function getPartner(int $type, $flag = null): ?Partner
    {
        if (!$flag && !($flag = Partner::FLAGS[$type])) {
            throw new InvalidOperationException("invalid type or flag for partner:type:{$type},flag:{$flag}");
        }
        return $this->partners[Partner::getPartnerKeyStatic($type, $flag)];
    }

    public function modify(PartnerMap $partners = null, $phone = '', bool $onlyModifyIfNull = false)
    {
        if ($partners) {
            foreach ($partners as $partnerKey => $partner) {
                if (!$onlyModifyIfNull || !isset($this->partners[$partnerKey])) {
                    $this->partners[$partnerKey] = $partner;
                }
            }
        }

        $this->phone = $onlyModifyIfNull ? ($this->phone ?: $phone) : ($phone ?: $this->phone);
    }
}
