<?php

namespace App\Domain\User;

use Psr\EventDispatcher\EventDispatcherInterface;
use App\Domain\Events\MerchantUserBoundEvent;
use WecarSwoole\Exceptions\Exception;

class MerchantService
{
    private $merchantRepository;
    private $userRepository;
    private $eventDispatcher;

    public function __construct(
        IMerchantRepository $merchantRepository,
        IUserRepository $userRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->merchantRepository = $merchantRepository;
        $this->userRepository = $userRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * 用户-商户关系绑定
     * @param int $uid
     * @param int $merchantType
     * @param int $merchantId
     * @throws Exception
     */
    public function bind(int $uid, int $merchantType, int $merchantId)
    {
        if (!$user = $this->userRepository->getUserByUid($uid)) {
            throw new Exception("用户不存在:{$uid}", ErrCode::USER_NOT_EXIST);
        }

        $merchant = new Merchant($merchantId, $merchantType);

        $this->bindUser($merchant, $user);
    }

    public function bindUser(Merchant $merchant, User $user)
    {
        if ($merchant->isPlatform()) {
            return;
        }

        $merchant->addUser($user, $this->merchantRepository);
        $this->merchantRepository->save($merchant);

        $this->eventDispatcher->dispatch(new MerchantUserBoundEvent($user, $merchant));
    }
}
