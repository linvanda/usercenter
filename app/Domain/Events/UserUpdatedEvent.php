<?php

namespace App\Domain\Events;

use App\Domain\User\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 跟新用户信息事件
 * Class UserUpdatedEvent
 * @package App\Domain\Events
 */
class UserUpdatedEvent extends Event
{
    private $oldUser;
    private $newUser;

    public function __construct(User $oldUser, User $newUser)
    {
        $this->oldUser = $oldUser;
        $this->newUser = $newUser;
    }

    public function oldUser(): User
    {
        return $this->oldUser;
    }

    public function newUser(): User
    {
        return $this->newUser;
    }
}
