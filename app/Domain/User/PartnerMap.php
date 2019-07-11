<?php

namespace App\Domain\User;

use WecarSwoole\Collection\Map;
use WecarSwoole\Exceptions\Exception;

/**
 * 第三方用户标识集合
 * Class PartnerMap
 * @package App\Domain\User
 */
class PartnerMap extends Map
{
    /**
     * PartnerCollection constructor.
     * @param array $partners
     * @throws Exception
     */
    public function __construct(array $partners = [])
    {
        $partners = array_filter($partners);
        reset($partners);
        // 转 k => v
        if ($partners && is_int(key($partners))) {
            $partners = array_combine(
                array_map(
                    function (Partner $pUser) {
                        return $pUser->getPartnerKey();
                    },
                    $partners
                ),
                $partners
            );
        }
        parent::__construct($partners, Partner::class);
    }

    /**
     * @param Partner $partner
     * @throws Exception
     */
    public function add(Partner $partner)
    {
        $this->offsetSet($partner->getPartnerKey(), $partner);
    }

    /**
     * 判断两个 partnerMap 是否有分歧
     * 所谓分歧，指两者有同类型却不同userid的 partner
     * @param PartnerMap $partnerMap
     * @return bool
     */
    public function isDivergent(PartnerMap $partnerMap): bool
    {
        if (!count($this) || !count($partnerMap)) {
            return false;
        }

        foreach ($this as $key => $partner) {
            if (isset($partnerMap[$key]) && $partnerMap[$key] != $partner) {
                return true;
            }
        }

        return false;
    }
}
