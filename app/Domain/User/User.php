<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;
use App\Exceptions\UserRegisterConflictException;
use WecarSwoole\Entity;
use WecarSwoole\Exceptions\Exception;

class User extends Entity
{
    public const GENDER_MALE = 1;
    public const GENDER_FEMAIL = 2;
    public const GENDER_UNKNOW = 0;
    public const UPDATE_NONE = 1;
    public const UPDATE_ONLY_NULL = 2;
    public const UPDATE_NEW = 3;

    /**
     * @var UserId
     */
    protected $userId;
    protected $name;
    protected $nickname;
    protected $gender;
    protected $birthday;
    protected $regtime;
    protected $headurl;
    protected $tinyHeadurl;
    /**
     * 用户来源
     * @var string
     */
    protected $registerFrom;
    /**
     * 车牌号列表
     * @var array
     */
    protected $carNumbers;
    /**
     * 生日修改次数
     * @var int
     */
    protected $birthdayChange;
    /**
     * 邀请码
     * @var string
     */
    protected $inviteCode;

    /**
     * @var IUserRepository
     */
    protected $userRepository;
    protected $mergeService;
    protected $divergeService;

    public function __construct(
        IUserRepository $userRepository,
        MergeService $mergeService,
        DivergeService $divergeService,
        UserDTO $userDTO = null
    ) {
        if ($userDTO) {
            // 从 DTO 创建 User 对象
            $this->buildFromArray($userDTO->toArray());
            // 组装 user 标识
            $this->userId = new UserId($userDTO->uid, $userDTO->phone, $userDTO->relUids ?? [], $userDTO->partnerUsers);
        }

        $this->userRepository = $userRepository;
        $this->mergeService = $mergeService;
        $this->divergeService = $divergeService;
    }

    /**
     * 用户注册
     * @param int $updateStrategy 更新策略
     * @return int
     * @throws UserRegisterConflictException
     * @throws Exception
     */
    public function register(int $updateStrategy = self::UPDATE_ONLY_NULL): ?int
    {
        if ($this->userId->getUid()) {
            throw new Exception("can not add user who has the uid");
        }

        $thisPartnerUser = $this->userId->getPartnerUser();
        $userOfPartner = $this->userRepository->getUserByPartner($thisPartnerUser);
        $userOfPhone = $this->userRepository->getUserByPhone($this->userId->getPhone());

        // partner 和 phone 都没有查到用户记录，说明是完全的新用户，直接添加
        if (!$userOfPartner && !$userOfPhone) {
            return $this->userRepository->add($this);
        }

        // 查到两条记录
        if ($userOfPartner && $userOfPhone) {
            // 两条记录如果是同一个人，则更新
            if ($userOfPartner->equal($userOfPhone)) {
                $this->userId->setUid($userOfPhone->userId->getUid());
                $this->userRepository->update($this, $updateStrategy);

                return $this->userId->getUid();
            }

            // 两条记录不是同一个人，需要进行分歧处理
            if ($checkedUid = $this->divergeService->dealDivergence($this, $userOfPhone, $userOfPartner)) {
                $this->userId->setUid($checkedUid);
            }
            return $checkedUid;
        }

        if ($userOfPhone) {
            return $this->registerWhenPhoneExistsOnly($userOfPhone, $updateStrategy);
        }

        return $this->registerWhenPartnerExistsOnly($userOfPartner, $updateStrategy);
    }

    public function equal(User $user): bool
    {
        return $user && $user->userId->getUid() == $this->userId->getUid();
    }

    /**
     * 注册：当 phone 已经有用户而 partner 没有用户时
     * @param User $userOfPhone
     * @param int $updateStrategy
     * @return int 最终有效的 uid
     * @throws UserRegisterConflictException
     * @throws Exception
     */
    private function registerWhenPhoneExistsOnly(User $userOfPhone, int $updateStrategy): int
    {
        if (!$userOfPhone) {
            throw new Exception("userOfPhone不存在");
        }

        $thisPartnerUser = $this->userId->getPartnerUser();

        if ($thisPartnerUser &&
            $userOfPhone->userId->getPartnerUser($thisPartnerUser->type(), $thisPartnerUser->flag())) {
            // phone 查出来的用户的 partner 和当前的不一致，抛出异常
            throw new UserRegisterConflictException(
                '用户数据存在异常，需要人工处理',
                503,
                [
                    'new_phone' => $this->userId->getPhone(),
                    'new_partner' => $thisPartnerUser->toArray()
                ]
            );
        }

        // 否则，试图更新
        $this->userId->setUid($userOfPhone->userId->getUid());
        $this->userRepository->update($this, $updateStrategy);

        return $this->userId->getUid();
    }

    /**
     * @param User $userOfPartner
     * @param int $updateStrategy
     * @return int
     * @throws UserRegisterConflictException
     * @throws Exception
     */
    private function registerWhenPartnerExistsOnly(User $userOfPartner, int $updateStrategy): int
    {
        if (!$userOfPartner) {
            throw new Exception("userOfPartner 不存在");
        }

        // partner 有用户而 phone 没有用户
        if ($this->userId->getPhone() && $userOfPartner->userId->getPhone()) {
            // 两者的 phone 不一致，抛出异常
            throw new UserRegisterConflictException(
                '用户数据存在异常，需要人工处理',
                504,
                [
                    'new_phone' => $this->userId->getPhone(),
                    'new_partner' => $this->userId->getPartnerUser()->toArray()
                ]
            );
        }

        $this->userId->setUid($userOfPartner->userId->getUid());

        // 至此，要么传入了 phone，而 partner 查出来的记录没有 phone；要么没有传入 phone
        $this->userRepository->update($this, $this->userId->getPhone() ? User::UPDATE_NEW : $updateStrategy);

        return $this->userId->getUid();
    }
}
