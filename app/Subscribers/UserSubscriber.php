<?php

namespace App\Subscribers;

use App\Domain\Events\UserAddedEvent;
use App\Domain\Events\UserUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * 用户事件订阅者
 * Class UserSubscriber
 * @package App\Subscribers
 */
class UserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            UserAddedEvent::class => [
                ['userAdded']
            ],
            UserUpdatedEvent::class => [
                ['userUpdated']
            ]
        ];
    }

    public function userAdded(UserAddedEvent $event)
    {
        //
    }

    public function userUpdated(UserUpdatedEvent $event)
    {
        //
    }
}
