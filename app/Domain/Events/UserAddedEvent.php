<?php

namespace App\Domain\Events;

use App\Domain\User\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 添加用户事件
 * Class UserAddedEvent
 * @package App\Domain\Events
 */
class UserAddedEvent extends Event
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function user(): User
    {
        return $this->user;
    }
}
