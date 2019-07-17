<?php

namespace App\Subscribers;

use App\Domain\Events\UserAddedEvent;
use App\Domain\Events\UserMergedEvent;
use App\Domain\Events\UserUpdatedEvent;
use App\Domain\User\User;
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
            ],
            UserMergedEvent::class => [
                ['userMerged']
            ]
        ];
    }

    public function userAdded(UserAddedEvent $event)
    {
        // TODO 初始化用户等级

        // TODO 初始化卡、积分
    }

    public function userUpdated(UserUpdatedEvent $event)
    {
        $this->changeLog($event->oldUser(), $event->newUser(), 1);
    }

    public function userMerged(UserMergedEvent $event)
    {
        $this->changeLog($event->abandonUser(), $event->targetUser(), 2);

        // TODO 通知其他子系统
    }

    /**
     * 记录变更日志
     * @param User $oldUser
     * @param User $newUser
     * @param int $changeType 1 update, 2 merge
     */
    private function changeLog(User $oldUser, User $newUser, int $changeType)
    {
        // TODO 记录变更日志
    }
}
