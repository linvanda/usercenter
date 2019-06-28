<?php

namespace App\Foundation\Repository\User;

use App\Domain\User\PartnerUser;
use App\Domain\User\PartnerUserMap;
use App\Domain\User\User;
use App\Domain\User\IUserRepository;
use App\Domain\User\UserId;
use App\DTO\User\UserDTO;
use App\Foundation\Repository\MySQLUserCenterRepository;
use Psr\SimpleCache\CacheInterface;

/**
 * MySQL 版仓储实现
 */
class MySQLUserRepository extends MySQLUserCenterRepository implements IUserRepository
{
    private $cache;

    /**
     * MySQLUserRepository constructor.
     * @param CacheInterface $cache
     * @throws \Exception
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;

        parent::__construct();
    }

    /**
     * 添加用户
     * @param User $user
     * @return int|bool 成功返回 uid，失败返回 false
     */
    public function add(User $user)
    {
        // TODO: Implement add() method.
    }

    /**
     * 根据 UserId 获取用户信息
     * 只会取 UserId 中的一个字段查询
     * 查询优先级：uid > phone > partner > relUid
     * 如果根据 uid 查不到用户，还要用 uid 到合并表查询
     * @param UserId $userId
     * @return UserDTO
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getDTOByUserId(UserId $userId): ?UserDTO
    {
        if ($userId->uid) {
            $userArr = $this->getUserArrByUid($userId->uid);
        } elseif ($userId->phone) {
            $userArr = $this->getUserArrByPhone($userId->phone);
        } elseif ($userId->partners) {
            // 用第一个查
            $userArr = $this->getUserArrByPartner(reset($userId->getPartnerUsers()));
        }

        if (!$userArr) {
            return null;
        }

        // TODO 需获取 relUids
        $userArr['rel_uids'] = [];

        $userDTO = new UserDTO($userArr);

        // TODO UserDTO 需要设置 partnerIds 字段
        $userDTO->partnerUsers = new PartnerUserMap();

        return $userDTO;
    }

    /**
     * @param int $uid
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getUserArrByUid(int $uid): array
    {
        if (!($userInfo = $this->getUserInfoFromCache($uid, UserId::FLAG_UID))) {
            $userInfo = $this->getUserArrFromDB("uid=:uid", ['uid' => $uid]);
            if ($userInfo) {
                $this->cacheUserInfo($userInfo, $uid, UserId::FLAG_UID);
            }
        }

        // TODO 如果从用户表查不到数据，应该还要从合并表查询

        // TODO 查询 parters 列表

        return $userInfo;
    }

    /**
     * @param string $phone
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getUserArrByPhone(string $phone): array
    {
        if (!($userInfo = $this->getUserInfoFromCache($phone, UserId::FLAG_PHONE))) {
            $userInfo = $this->getUserArrFromDB("phone=:phone", ['phone' => $phone]);
            if ($userInfo) {
                $this->cacheUserInfo($userInfo, $phone, UserId::FLAG_PHONE);
            }
        }

        return $userInfo;
    }

    /**
     * @param PartnerUser $partner
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getUserArrByPartner(PartnerUser $partner): array
    {
        if ($userInfo = $this->getUserInfoFromCache($partner, UserId::FLAG_PARTNER)) {
            return $userInfo;
        }

        //TODO 用户系统重构后，应当从统一的 partner 表查询信息
        if ($partner->type() == PartnerUser::P_WEIXIN) {
            $userInfo = $this->getUserArrFromDB("wechat_openid=:openid", ['openid' => $partner->id()]);
        } elseif ($partner->type() == PartnerUser::P_ALIPAY) {
            $uid = $this->query->select('uid')->from('wei_auth_users')
                ->where('user_id=:userid', ['userid' => $partner->id()])
                ->where('is_delete=0')
                ->column();

            if ($uid) {
                $userInfo = $this->getUserArrFromDB('uid=:uid', ['uid' => $uid]);
            } else {
                return [];
            }
        }

        if ($userInfo) {
            $this->cacheUserInfo($userInfo, $partner, UserId::FLAG_PARTNER);
        }

        return $userInfo;
    }

    /**
     * @param string $where
     * @param array $whereParams
     * @param null $fields
     * @return array
     * @throws \Exception
     */
    private function getUserArrFromDB(string $where, array $whereParams = [], $fields = null): array
    {
        $fields = $fields ?? 'uid,name,nickname,phone,gender,birthday,headurl,
        tinyheadurl,regtime,channel,birthday_change,invite_code';

        $userInfo = $this->query->select($fields)
            ->from('wei_users')
            ->where($where, $whereParams)
            ->where("del_time=0")
            ->one();

        if (!$userInfo) {
            return [];
        }

        //TODO 车牌号。目前用户车牌号存在专车认证表里面，这个不太合理。后面要迁移到用户表。暂时不查询车牌号
        $userInfo['car_numbers'] = [];

        return $userInfo;
    }

    /**
     * 从缓存获取用户数据
     * 注意：所有非 uid 获取情况都会转化为通过 uid 获取，这样更新缓存都时候只需要更新 uid 对应都缓存即可
     * @param $flag
     * @param int $type
     * @return array|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getUserInfoFromCache($flag, int $type): ?array
    {
        switch ($type) {
            case UserId::FLAG_UID:
                return $this->cache->get($this->getUserCacheKey($flag, $type));
            case UserId::FLAG_PHONE:
                $uidOfPhone = $this->cache->get($this->getUserCacheKey($flag, $type));
                if (!$uidOfPhone) {
                    return null;
                }
                return $this->cache->get($this->getUserCacheKey($uidOfPhone, UserId::FLAG_UID));
            case UserId::FLAG_PARTNER:
                $uidOfPartner = $this->cache->get(
                    $this->getUserCacheKey($flag, $type)
                );
                if (!$uidOfPartner) {
                    return null;
                }

                return $this->cache->get($uidOfPartner, UserId::FLAG_UID);
        }

        return null;
    }

    /**
     * @param array $userInfo
     * @param $flag
     * @param $type
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function cacheUserInfo(array $userInfo, $flag, $type)
    {
        if (!$userInfo || !$userInfo['uid']) {
            return;
        }

        $this->cache->set($this->getUserCacheKey($userInfo['uid'], UserId::FLAG_UID), $userInfo, 86400 * 3);

        if ($type !== UserId::FLAG_UID) {
            $this->cache->set($this->getUserCacheKey($flag, $type), $userInfo['uid'], 86400 * 3);
        }
    }

    private function getUserCacheKey($flag, int $type): string
    {
        if ($flag instanceof PartnerUser) {
            $flag = $flag->id() . '==' . $flag->type();
        }

        return "userinfo-" . md5("{$type}-{$flag}");
    }
}
