<?php

namespace App\Domain\User;

use App\Domain\Events\UserBoundEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use WecarSwoole\Exceptions\Exception;

/**
 * 商户
 * Class Merchant
 */
class Merchant
{
    // 平台
    public const T_PLATFORM = 0;
    // 油站
    public const T_STATION = 1;
    // 集团
    public const T_GROUP = 2;
    // 油站组
    public const T_ORG = 3;

    private $merchantId;
    private $merchantType;
    private $users = [];

    /**
     * Merchant constructor.
     * @param int $merchantId
     * @param int $merchantType
     * @throws Exception
     */
    public function __construct(int $merchantId, int $merchantType)
    {
        if (!$this->isInvalidMerchantType($merchantType)) {
            throw new Exception("invalid merchant type:{$merchantType}");
        }

        $this->merchantType = $merchantType;
        $this->merchantId = $merchantId;
    }

    public function type(): int
    {
        return $this->merchantType;
    }

    public function id(): int
    {
        return $this->merchantId;
    }

    /**
     * 是否喂车平台
     * @return bool
     */
    public function isPlatform(): bool
    {
        return $this->merchantType === self::T_PLATFORM;
    }

    /**
     * 是否集团
     * @return bool
     */
    public function isGroup(): bool
    {
        return $this->type() === self::T_GROUP;
    }

    /**
     * 是否油站
     * @return bool
     */
    public function isStation(): bool
    {
        return $this->type() === self::T_STATION;
    }

    /**
     * 是否油站组
     * @return bool
     */
    public function isORG(): bool
    {
        return $this->type() === self::T_ORG;
    }

    /**
     * 绑定用户到商户
     * @param User $user
     * @param IMerchantRepository $merchantRepository
     */
    public function addUser(
        User $user,
        IMerchantRepository $merchantRepository
    ) {
        if ($merchantRepository->isUserAlreadyBound($this, $user)) {
            return;
        }

        $this->users[$user->uid()] = $user;
    }

    public function users(): array
    {
        return $this->users;
    }

    private function isInvalidMerchantType(int $merchantType): bool
    {
        return in_array(
            $merchantType,
            [
                self::T_PLATFORM,
                self::T_STATION,
                self::T_ORG,
                self::T_GROUP
            ]
        );
    }
}
