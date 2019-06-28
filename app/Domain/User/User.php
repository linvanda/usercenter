<?php

namespace App\Domain\User;

use App\DTO\User\UserDTO;
use WecarSwoole\Entity;
use WecarSwoole\Exceptions\CriticalErrorException;
use WecarSwoole\Exceptions\Exception;

class User extends Entity
{
    public const GENDER_MALE = 1;
    public const GENDER_FEMAIL = 2;
    public const GENDER_UNKNOW = 0;

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

    public function __construct(UserDTO $userDTO = null, IUserRepository $userRepository, MergeService $mergeService)
    {
        if ($userDTO) {
            // 从 DTO 创建 User 对象
            $this->buildFromArray($userDTO->toArray());
        }

        // 组装 user 标识
        $this->userId = new UserId($userDTO->uid, $userDTO->phone, $userDTO->relUids, $userDTO->partnerUsers);
        $this->userRepository = $userRepository;
        $this->mergeService = $mergeService;
    }

    /**
     * 用户注册
     * @throws CriticalErrorException
     * @throws Exception
     */
    public function register(bool $updateWhenExist = false)
    {
        if ($this->userId->getUid()) {
            throw new Exception("can not add user who has the uid");
        }

        // 根据 partner 检查用户是否存在
        $thisPartnerUser = $this->userId->getPartnerUser();
        $userOfPartner = $this->userRepository->getUserByPartner($thisPartnerUser);
        if ($userOfPartner) {
            // 该用户是否存在 phone，如果存在，则检查 phone 和当前用户是否一致
            if ($userOfPartner->userId->getPhone()) {
                if ($this->userId->getPhone() && $this->userId->getPhone() != $userOfPartner->userId->getPhone()) {
                    throw new CriticalErrorException(
                        "该用户已存在记录，但手机号冲突，请联系人工处理",
                        501,
                        ['ex_phone' => $userOfPartner->userId->getPhone(), 'new_phone' => $this->userId->getPhone()]
                    );
                }

                // 手机号相同，看是否需要更新用户信息
                if ($updateWhenExist) {
                    $this->userId->setUid($userOfPartner->userId->getUid());
                    $this->userRepository->update($this);
                }

                return $userOfPartner->userId->getUid();
            } else {
                // 查出来的那个用户记录没有手机号
                if (!$this->userId->getPhone()) {
                    // 当前的也没有手机号
                    if ($updateWhenExist) {
                        $this->userId->setUid($userOfPartner->userId->getUid());
                        $this->userRepository->update($this);
                    }
                    return $userOfPartner->userId->getUid();
                } else {
                    // 当前的有手机号，检查该手机号是否存在用户
                    $userOfPhone = $this->userRepository->getUserByPhone($this->userId->getPhone());
                    if (!$userOfPhone) {
                        // 手机号没有查到用户记录，则更新
                        $this->userId->setUid($userOfPartner->userId->getUid());
                        $this->userRepository->update($this);

                        return $this->userId->getUid();
                    } else {
                        // 手机号已经存在用户
                        if ($thatPartnerUser = $userOfPhone->userId->getPartnerUser(
                            $thisPartnerUser->type(),
                            $thisPartnerUser->flag()
                        )) {
                            // 查出来的用户也存在同类型partner，抛出异常
                            throw new CriticalErrorException(
                                "用户数据存在异常，需要人工处理",
                                502,
                                [
                                    'new_phone' => $this->userId->getPhone(),
                                    'new_partner' => $this->userId->getPartnerUser()->toArray(),
                                    'exist_partner' => $thatPartnerUser->toArray()
                                ]
                            );
                        } else {
                            // 查出来的用户不存在同类型partner，合并
                            return $this->mergeService->merge($this, $userOfPhone);
                        }
                    }
                }
            }
        } else {
            // partner 没有查到用户记录
            // phone 是否存在用户记录
            $userOfPhone = $this->userRepository->getUserByPhone($this->userId->getPhone());
            if (!$userOfPhone) {
                return $this->userRepository->add($this);
            }

            // phone 查到了用户记录，看此用户记录是否是否存在同类 partner
            if ($thatPartnerUser = $userOfPhone->userId->getPartnerUser(
                $thisPartnerUser->type(),
                $thisPartnerUser->flag()
            )) {
                // 查出来的用户存在同类型partner，抛出异常
                throw new CriticalErrorException(
                    "用户数据异常，需要人工处理",
                    503,
                    [
                        'new_phone' => $this->userId->getPhone(),
                        'new_partner' => $this->userId->getPartnerUser()->toArray(),
                        'exist_partner' => $thatPartnerUser->toArray()
                    ]
                );
            } else {
                // 绑定 openid
                $this->userId->setUid($userOfPhone->userId->getUid());
                $this->userRepository->update($this, true);
                return $this->userId->getUid();
            }
        }
    }
}
