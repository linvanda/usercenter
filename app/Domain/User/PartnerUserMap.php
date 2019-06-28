<?php

namespace App\Domain\User;

use WecarSwoole\Collection\Map;
use WecarSwoole\Exceptions\Exception;

/**
 * 第三方用户标识集合
 * Class PartnerUserMap
 * @package App\Domain\User
 */
class PartnerUserMap extends Map
{
    /**
     * PartnerUserCollection constructor.
     * @param array $partnerUsers
     * @throws Exception
     */
    public function __construct(array $partnerUsers = [])
    {
        $partnerUsers = array_filter($partnerUsers);
        reset($partnerUsers);
        // 转 k => v
        if ($partnerUsers && is_int(key($partnerUsers))) {
            $partnerUsers = array_combine(
                array_map(
                    function ($pUser) {
                        return $pUser->getPartnerKey();
                    },
                    $partnerUsers
                ),
                $partnerUsers
            );
        }

        parent::__construct($partnerUsers, PartnerUser::class);
    }
}
