<?php

namespace App\Domain\Events;

use App\Domain\User\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 用户用户合并事件
 * Class UserMergedEvent
 * @package App\Domain\Events
 */
class UserMergedEvent extends Event
{
    private $targetUser;
    private $abandonUser;

    public function __construct(User $targetUser, User $abandonUser)
    {
        $this->targetUser = $targetUser;
        $this->abandonUser = $abandonUser;
    }

    public function targetUser(): User
    {
        return $this->targetUser;
    }

    public function abandonUser(): User
    {
        return $this->abandonUser;
    }
}
