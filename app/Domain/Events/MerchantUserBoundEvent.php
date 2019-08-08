<?php

namespace App\Domain\Events;

use App\Domain\User\Merchant;
use App\Domain\User\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 用户绑定到商户事件
 * @package App\Domain\Events
 */
class MerchantUserBoundEvent extends Event
{
    private $user;
    private $merchant;

    public function __construct(User $user, Merchant $merchant)
    {
        $this->user = $user;
        $this->merchant = $merchant;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function merchant(): Merchant
    {
        return $this->merchant;
    }
}
