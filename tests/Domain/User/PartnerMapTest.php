<?php

namespace Test\Domain\User;

use App\Domain\User\Partner;
use App\Domain\User\PartnerMap;
use PHPUnit\Framework\TestCase;

class PartnerMapTest extends TestCase
{
    public function testIsDivergent()
    {
        $partner1 = new Partner(123, Partner::P_WEIXIN, '999');
        $partner2 = new Partner(123, Partner::P_WEIXIN, '999');
        $partner3 = new Partner(345, Partner::P_WEIXIN, '999');
        $partner4 = new Partner(345, Partner::P_ALIPAY, '878');

        $map1 = new PartnerMap([$partner1, $partner4]);
        $map2 = new PartnerMap([$partner2]);
        $map3 = new PartnerMap([$partner3, $partner4]);
        $map4 = new PartnerMap([$partner4]);

        $this->assertFalse($map1->isDivergent($map2));
        $this->assertTrue($map1->isDivergent($map3));
        $this->assertFalse($map2->isDivergent($map4));
    }
}
