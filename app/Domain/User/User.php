<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;
use App\ErrCode;
use App\Exceptions\BirthdayException;
use App\Exceptions\InvalidMergeException;
use App\Exceptions\InvalidPhoneException;
use App\Exceptions\PartnerException;
use EasySwoole\Utility\Random;
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

    private const DEFAULT_HEADURL = 'http://fs1.weicheche.cn/avatar/origin/default_male.jpg';
    private const DEFAULT_TINY_HEADURL = 'http://fs1.weicheche.cn/avatar/tiny/default_male.jpg';

    /** @var UserId $userId 是内部标识，不应当对外暴露*/
    protected $userId;
    protected $name;
    protected $nickname;
    protected $gender;
    protected $birthday;
    /** @var string */
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
        $this->buildFromUserDTO($userDTO);
    }

    public function __clone()
    {
        $this->userId = clone $this->userId;
    }

    public function uid(): int
    {
        return $this->userId->getUid();
    }

    public function setUid(int $uid)
    {
        $this->userId->setUid($uid);
    }

    public function partners(): PartnerMap
    {
        return $this->userId->getPartners();
    }

    public function addPartner(Partner $partner)
    {
        $this->userId->addPartner($partner);
    }

    /**
     * @param int $type
     * @param $flag
     * @return Partner|null
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function getPartner(int $type, $flag = null): ?Partner
    {
        return $this->userId->getPartner($type, $flag);
    }

    public function relUids(): array
    {
        return $this->userId->getRelUids();
    }

    public function phone()
    {
        return $this->userId->getPhone();
    }

    public function equal(User $user): bool
    {
        return $user && $user->userId->getUid() === $this->userId->getUid();
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

        $this->validateDataFormat($userDTO);

        if ($updateStrategy === self::UPDATE_ONLY_NULL) {
            $this->updateIfNull($userDTO);
        } elseif ($updateStrategy === self::UPDATE_NEW) {
            $this->updateToNew($userDTO, $userRepository, $forceChangePhone);
        }
    }

    /**
     * 将 $otherUser 信息合并到自身
     * @param User $otherUser
     * @param bool $mergePhone
     * @throws InvalidMergeException
     */
    public function mergeFromOtherUser(User $otherUser, bool $mergePhone = false)
    {
        if (!$mergePhone && $this->phone() && $otherUser->phone() && $this->phone() !== $otherUser->phone()) {
            throw new InvalidMergeException("can not merge when phones are not the same.");
        }

        $this->updateIfNull(
            new UserDTO([
                'partners' => $otherUser->partners(),
                'phone' => $otherUser->phone(),
                'carNumbers' => $otherUser->carNumbers,
                'birthday' => $otherUser->birthday,
                'name' => $otherUser->name,
                'nickname' => $otherUser->nickname,
                'gender' => $otherUser->gender,
                'headurl' => $otherUser->headurl,
                'tinyHeadurl' => $otherUser->tinyHeadurl,
                'registerFrom' => $otherUser->registerFrom,
            ], true, false)
        );
    }

    /**
     * 仅更新本对象空属性
     * @param UserDTO $userDTO
     */
    private function updateIfNull(UserDTO $userDTO)
    {
        $this->userId->modify($userDTO->partners, $userDTO->phone, true);

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
    }

    /**
     * @param UserDTO $userDTO
     * @param IUserRepository $userRepository
     * @param $forceChangePhone
     * @throws BirthdayException
     * @throws Exception
     * @throws InvalidPhoneException
     * @throws PartnerException
     */
    private function updateToNew(UserDTO $userDTO, IUserRepository $userRepository, $forceChangePhone)
    {
        // 必须显式指定要修改手机号，否则不允许修改
        if (!$forceChangePhone && $userDTO->phone && $this->phone() && $userDTO->phone != $this->phone()) {
            throw new Exception("can not change phone unless declare force change it");
        }

        $this->validateUpdateRule($userDTO, $userRepository);

        /**
         * 更新数据
         */
        $this->userId->modify($userDTO->partners, $userDTO->phone);

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
        $this->registerFrom = $this->registerFrom ?: $userDTO->registerFrom;// 这里例外
    }

    /**
     * 更新合法性校验
     * @param UserDTO $userDTO
     * @param IUserRepository $userRepository
     * @throws BirthdayException
     * @throws InvalidPhoneException
     * @throws PartnerException
     */
    private function validateUpdateRule(UserDTO $userDTO, IUserRepository $userRepository)
    {
        // 手机号检验
        if ($userDTO->phone && $userDTO->phone !== $this->phone() && $userRepository->isPhoneBeUsed($userDTO->phone)) {
            throw new InvalidPhoneException("phone has been register:{$userDTO->phone}", ErrCode::PHONE_ALREADY_EXIST);
        }

        // 生日修改次数检验
        if ($userDTO->birthday &&
            $this->birthday &&
            $userDTO->birthday !== $this->birthday &&
            $this->birthdayChange > 0
        ) {
            throw new BirthdayException("birthday can be change once only", ErrCode::BIRTHDAY_CHANGE_LIMIT);
        }

        // partner 校验：如果同类型 partner 已经有值，则不允许修改
        if ($this->partners()->isDivergent($userDTO->partners)) {
            throw new PartnerException(
                "partner has exits,can not change it",
                ErrCode::PARTNER_CANNOT_BE_CHANGED,
                ['uid' => $this->uid(), 'partner' => $userDTO->partners->first()->toArray()]
            );
        }
    }

    /**
     * 数据格式校验
     * @param UserDTO $userDTO
     * @throws Exception
     * @throws InvalidPhoneException
     */
    private function validateDataFormat(UserDTO $userDTO)
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

    private function buildFromUserDTO(UserDTO $userDTO)
    {
        $this->userId = new UserId(
            $userDTO->uid,
            $userDTO->phone,
            $userDTO->relUids ?? [],
            $userDTO->partners
        );
        $this->name = $userDTO->name;
        $this->nickname = $userDTO->nickname;
        $this->gender = $userDTO->gender;
        $this->birthday = $userDTO->birthday;
        $this->carNumbers = $userDTO->carNumbers;
        $this->registerFrom = $userDTO->registerFrom;
        $this->regtime = $userDTO->regtime ?: date('Y-m-d H:i:s');
        $this->inviteCode = $userDTO->inviteCode ?: Random::character(12);
        $this->headurl = $userDTO->headurl ?: self::DEFAULT_HEADURL;
        $this->tinyHeadurl = $userDTO->tinyHeadurl ?: self::DEFAULT_TINY_HEADURL;
        $this->birthdayChange = $userDTO->birthdayChange ?: 0;
    }
}
