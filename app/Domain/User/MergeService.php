<?php

namespace App\Domain\User;

use App\Domain\Events\UserMergedEvent;
use App\Exceptions\InvalidMergeException;
use Psr\EventDispatcher\EventDispatcherInterface;

class MergeService
{
    private $userRepository;
    private $merchantRepository;
    private $eventDispatcher;

    public function __construct(
        IUserRepository $userRepository,
        IMerchantRepository $merchantRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->merchantRepository = $merchantRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * 合并两个用户
     * @param User $user1
     * @param User $user2
     * @param bool $useFirstAsTarget 是否强制将第一个用户选为目标用户（第二个合并到第一个身上）,默认系统会自己选择合并目标
     * @param bool $forceMerge 是否强制合并，如果两个用户都有 phone (且不一样)，则必须强制合并才行
     * @return User 返回目标用户（生效到那个）
     * @throws InvalidMergeException
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function merge(User $user1, User $user2, bool $useFirstAsTarget = false, bool $forceMerge = false): User
    {
        list($targetUser, $abandonUser) = $this->chooseTargetAndAbandonUser(
            $user1,
            $user2,
            $useFirstAsTarget,
            $forceMerge
        );

        $this->mergeUser($targetUser, $abandonUser, $forceMerge);

        // 发布合并事件
        $this->eventDispatcher->dispatch(new UserMergedEvent($targetUser, $abandonUser));

        return $targetUser;
    }

    /**
     * @param User $targetUser
     * @param User $abandonUser
     * @param bool $forceMerge
     * @throws InvalidMergeException
     */
    private function mergeUser(User $targetUser, User $abandonUser, bool $forceMerge)
    {
        $targetUser->mergeFromOtherUser($abandonUser, $forceMerge);
        $this->userRepository->saveMerge($targetUser, $abandonUser);

        // 合并商户绑定关系
        $this->merchantRepository->mergeMerchantUser($targetUser, $abandonUser);
    }

    /**
     * @param User $user1
     * @param User $user2
     * @param bool $useFirstAsTarget
     * @param bool $forceMerge
     * @return array
     * @throws InvalidMergeException
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    private function chooseTargetAndAbandonUser(
        User $user1,
        User $user2,
        bool $useFirstAsTarget = false,
        bool $forceMerge = false
    ): array {
        if (!$forceMerge && $user1->phone() && $user2->phone() && $user1->phone() !== $user2->phone()) {
            throw new InvalidMergeException(
                "must force merge when they have phone all.phone:{$user1->phone()},{$user2->phone()}"
            );
        }

        if ($useFirstAsTarget) {
            return [$user1, $user2];
        }

        // 优先取有 phone 的
        if ($user1->phone() && !$user2->phone()) {
            return [$user1, $user2];
        }

        if ($user2->phone() && !$user1->phone()) {
            return [$user2, $user1];
        }

        /**
         * 都有 phone (phone 一致)或者都没有 phone
         */

        // TODO 目前没有考虑用户加油情况，严格来说，还要检查哪个加过油，哪个有积分等

        // 取 partners 多的
        if (count($user1->partners()) > count($user2->partners())) {
            return [$user1, $user2];
        }

        if (count($user2->partners()) > count($user1->partners())) {
            return [$user2, $user1];
        }

        // partner 相等，优先取有微信大号 partner 的
        $user1HasWX = $this->userHasWXPartner($user1);
        $user2HasWX = $this->userHasWXPartner($user2);
        if ($user1HasWX && !$user2HasWX) {
            return [$user1, $user2];
        }
        if ($user2HasWX && !$user1HasWX) {
            return [$user2, $user1];
        }

        // 最后取最早注册的
        if ($user1->regtime < $user2->regtime) {
            return [$user1, $user2];
        }

        return [$user2, $user1];
    }

    /**
     * @param User $user
     * @return bool
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    private function userHasWXPartner(User $user): bool
    {
        return $user->getPartner(Partner::P_WEIXIN, Partner::FLAGS[Partner::P_WEIXIN]) ? true : false;
    }
}
