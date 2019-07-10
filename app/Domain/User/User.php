<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;
use App\Exceptions\BirthdayException;
use App\Exceptions\InvalidPhoneException;
use App\Exceptions\PartnerException;
use App\Exceptions\UserRegisterConflictException;
use WecarSwoole\Entity;
use WecarSwoole\Exceptions\Exception;
use WecarSwoole\Util\Random;

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
    protected $carNumbers = [];
    /**
     * 生日修改次数
     * @var int
     */
    protected $birthdayChange = 0;
    /**
     * 邀请码
     * @var string
     */
    protected $inviteCode;

    public function __construct(UserDTO $userDTO = null)
    {
        if ($userDTO) {
            // 从 DTO 创建 User 对象
            $this->buildFromArray($userDTO->toArray());
        }

        // 组装 user 标识
        $this->userId = new UserId($userDTO->uid, $userDTO->phone, $userDTO->relUids ?? [], $userDTO->partnerUsers);

        // 邀请码
        if (!$this->inviteCode) {
            $this->inviteCode = Random::str(12);
        }
    }

    public function equal(User $user): bool
    {
        return $user && $user->userId->getUid() == $this->userId->getUid();
    }

    /**
     * 基于 DTO 信息更新自身信息
     * @param UserDTO $userDTO
     * @param IUserRepository $userRepository
     * @param int $updateStrategy 更新策略
     * @param bool $forceChangePhone 是否强制修改手机号，如果需要修改手机号，必须设置为 true
     * @throws BirthdayException
     * @throws Exception
     * @throws InvalidPhoneException
     * @throws PartnerException
     */
    public function updateFromDTO(
        UserDTO $userDTO,
        IUserRepository $userRepository,
        int $updateStrategy = self::UPDATE_ONLY_NULL,
        $forceChangePhone = false
    ) {
        if ($updateStrategy === self::UPDATE_NONE) {
            return;
        }

        $this->validateDTO($userDTO);

        // 仅填充空属性，此时不需要考虑 phone、partner、birthday 等敏感属性的判断
        if ($updateStrategy === self::UPDATE_ONLY_NULL) {
            $this->userId->modify($userDTO->partnerUsers->first(), $userDTO->phone, true);

            // 车牌号
            if ($userDTO->carNumbers) {
                $this->carNumbers = array_merge($this->carNumbers ?? [], $userDTO->carNumbers);
            }

            // 生日变更次数
            if (!$this->birthday && $userDTO->birthday) {
                $this->birthdayChange = 1;
            }

            $this->name = $this->name ?: $userDTO->name;
            $this->nickname = $this->nickname ?: $userDTO->nickname;
            $this->gender = $this->gender ?: $userDTO->gender;
            $this->birthday = $this->birthday ?: $userDTO->birthday;
            $this->headurl = $this->headurl ?: $userDTO->headurl;
            $this->tinyHeadurl = $this->tinyHeadurl ?: $userDTO->tinyHeadurl;
            $this->registerFrom = $this->registerFrom ?: $userDTO->registerFrom;

            return;
        }

        /**
         * 用新值替换旧值（如果新值不是 null 的话）
         * 此时需要校验 phone、partner、birthday 等敏感信息的修改
         */
        if ($updateStrategy === self::UPDATE_NEW) {
            // 必须显式指定要修改手机号，否则不允许修改
            if (!$forceChangePhone && $userDTO->phone && $this->phone && $userDTO->phone != $this->phone) {
                throw new Exception("can not change phone unless declare force change it");
            }

            // 手机号检验
            if ($userDTO->phone &&
                $userDTO->phone !== $this->phone &&
                $userRepository->isPhoneBeUsed($userDTO->phone)
            ) {
                throw new InvalidPhoneException("phone has been register:{$userDTO->phone}");
            }

            // 生日修改次数检验
            if ($userDTO->birthday &&
                $this->birthday &&
                $userDTO->birthday !== $this->birthday &&
                $this->birthdayChange > 0
            ) {
                throw new BirthdayException("birthday can be change once only");
            }

            // partner 校验：如果同类型 partner 已经有值，则不允许修改
            /** @var PartnerUser $newPartner */
            if ($newPartner = $userDTO->partnerUsers->first()) {
                if (!$newPartner->equal($this->userId->getPartnerUsers()[$newPartner->getPartnerKey()])) {
                    throw new PartnerException("partner has exits,can not change it as {$newPartner->userId()}");
                }
            }

            /**
             * 更新
             */
            $this->userId->modify($userDTO->partnerUsers->first(), $userDTO->phone);

            // 车牌号
            if ($userDTO->carNumbers) {
                $this->carNumbers = array_merge($this->carNumbers ?? [], $userDTO->carNumbers);
            }

            // 生日变更次数
            if ($userDTO->birthday && $userDTO->birthday != $this->birthday) {
                $this->birthdayChange += 1;
            }

            $this->name = $userDTO->name ?? $this->name;
            $this->nickname = $userDTO->nickname ?? $this->nickname;
            $this->gender = $userDTO->gender ?? $this->gender;
            $this->birthday = $userDTO->birthday ?? $this->birthday;
            $this->headurl = $userDTO->headurl ?: $this->headurl;
            $this->tinyHeadurl = $userDTO->tinyHeadurl ?: $this->tinyHeadurl;
            $this->registerFrom = $this->registerFrom ?: $userDTO->registerFrom;

            return;
        }
    }

    /**
     * @param $name
     * @return PartnerUserMap|array|int|mixed|null
     * @throws \WecarSwoole\Exceptions\PropertyNotFoundException
     */
    public function __get($name)
    {
        if ($name == 'phone') {
            return $this->userId->getPhone();
        }

        if ($name == 'id' || $name == 'uid') {
            return $this->userId->getUid();
        }

        if ($name == 'partnerUsers') {
            return $this->userId->getPartnerUsers();
        }

        if ($name == 'relUids') {
            return $this->userId->getRelUids();
        }

        return parent::__get($name);
    }

    private function updateIfNull(UserDTO $userDTO)
    {

    }

    /**
     * @param UserDTO $userDTO
     * @throws Exception
     * @throws InvalidPhoneException
     */
    private function validateDTO(UserDTO $userDTO)
    {
        if (isset($userDTO->gender) &&
            !in_array($userDTO->gender, [self::GENDER_FEMAIL, self::GENDER_MALE, self::GENDER_UNKNOW])
        ) {
            throw new Exception("invalid gender:{$userDTO->gender}");
        }

        if (isset($userDTO->phone) && strlen($userDTO->phone) !== 11) {
            throw new InvalidPhoneException("phone length must 11：{$userDTO->phone}");
        }

        if ($userDTO->carNumbers && !is_array($userDTO->carNumbers)) {
            throw new Exception("carNumbers must be array");
        }
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
