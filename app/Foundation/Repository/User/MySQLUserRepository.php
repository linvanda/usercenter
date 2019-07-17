<?php

namespace App\Foundation\Repository\User;

use App\Domain\User\Partner;
use App\Domain\User\User;
use App\Domain\User\IUserRepository;
use App\Domain\User\UserId;
use App\DTO\User\UserDTO;
use App\Foundation\Repository\MySQLUserCenterRepository;
use EasySwoole\Utility\Random;
use Psr\SimpleCache\CacheInterface;
use Swoole\Exception;
use WecarSwoole\Exceptions\InvalidOperationException;

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
     * @return User 添加的 User
     * @throws Exception
     * @throws \Exception
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function add(User $user): User
    {
        $userData = [
            'nickname' => $user->nickname ?? '',
            'phone' => $user->phone(),
            'name' => $user->name,
            'gender' => $user->gender,
            'birthday' => $user->birthday,
            'headurl' => $user->headurl,
            'tinyheadurl' => $user->tinyHeadurl,
            'regtime' => $user->regtime,
            'channel' => $user->registerFrom,
            'invite_code' => $user->inviteCode,
            'password' => Random::character(8),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        // 微信大号
        if ($wxPartner = $user->getPartner(Partner::P_WEIXIN)) {
            $userData['wechat_openid'] = $wxPartner->userId();
        }

        // 添加到主表
        $this->query->insert('wei_users')->values($userData)->execute();

        if (!($uid = $this->query->lastInsertId())) {
            throw new Exception("添加用户失败");
        }

        $user->setUid($uid);

        $this->addUserPartner($user);
        $this->addCarNumber($user);

        return $user;
    }

    /**
     * @param User $user
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws InvalidOperationException
     * @throws \Exception
     */
    public function update(User $user, User $oldUser = null)
    {
        if (!$user || !$user->uid()) {
            return;
        }

        if (!$oldUser && !($oldUser = $this->getUserByUid($user->uid()))) {
            throw new InvalidOperationException("user is not exist:{$user->uid()}");
        }

        $userData = [
            'name' => $user->name === $oldUser->name ? null : $user->name,
            'phone' => $user->phone() === $oldUser->phone() ? null : $user->phone(),
            'nickname' => $user->nickname === $oldUser->nickname ? null : $user->nickname,
            'gender' => $user->gender === $oldUser->gender ? null : $user->gender,
            'birthday' => $user->birthday === $oldUser->birthday ? null : $user->birthday,
            'headurl' => $user->headurl === $oldUser->headurl ? null : $user->headurl,
            'tinyheadurl' => $user->tinyHeadurl === $oldUser->tinyHeadurl ? null : $user->tinyHeadurl,
            'birthday_change' => $user->birthdayChange === $oldUser->birthdayChange ? null : $user->birthdayChange,
            'name' => $user->name === $oldUser->name ? null : $user->name,
        ];

        if ($wxPartner = $user->getPartner(Partner::P_WEIXIN)) {
            $userData['wechat_openid'] = $wxPartner->userId();
        }

        $userData = array_filter($userData, function ($item) {
            return $item !== null;
        });

        if ($userData) {
            $this->query->update('wei_users')->set($userData)->where(['uid' => $user->uid()])->execute();
        }

        // TODO 车牌号
        if ($this->isCarNumberChanged($user, $oldUser)) {
            $this->addCarNumber($user);
        }

        // partner
        if ($user->getPartner(Partner::P_ALIPAY) && !$oldUser->getPartner(Partner::P_ALIPAY)) {
            $this->addUserPartner($user);
        }

        $this->clearUserCache($user);
    }

    /**
     * @param User $user
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(User $user)
    {
        $this->query->update('wei_users')->set(['del_time' => time()])->where(['uid' => $user->uid()])->execute();
        $this->clearUserCache($user);
    }

    /**
     * @param User $targetUser
     * @param User $abandonUser
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function saveMerge(User $targetUser, User $abandonUser)
    {
        // 更新 $targetUser
        $this->update($targetUser);

        // 删除 $abandonUser
        $this->delete($abandonUser);

        // TODO 记录 rel_uids
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
        if ($userId->getUid()) {
            $userArr = $this->getUserArrByUid($userId->getUid());
        } elseif ($userId->getPhone()) {
            $userArr = $this->getUserArrByPhone($userId->getPhone());
        } elseif ($userId->getPartners()) {
            // 用第一个查
            $userArr = $this->getUserArrByPartner($userId->getPartners()->first());
        }

        if (!$userArr) {
            return null;
        }

        $userDTO = new UserDTO($userArr);

        return $userDTO;
    }

    /**
     * @param Partner|null $partner
     * @return User|null
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserByPartner(?Partner $partner): ?User
    {
        if (!$partner || !($userArr = $this->getUserArrByPartner($partner))) {
            return null;
        }

        // 从 $userArr 创建 User 对象
        return new User(new UserDTO($userArr));
    }

    /**
     * @param string $phone
     * @return User|null
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserByPhone($phone = ''): ?User
    {
        if (!$phone || !($userArr = $this->getUserArrByPhone($phone))) {
            return null;
        }

        return new User(new UserDTO($userArr));
    }

    /**
     * @param int $uid
     * @return User|null
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserByUid(int $uid): ?User
    {
        if (!$uid || !($userArr = $this->getUserArrByUid($uid))) {
            return null;
        }

        return new User(new UserDTO($userArr));
    }

    /**
     * @param $phone
     * @return bool
     * @throws \Dev\MySQL\Exception\DBException
     * @throws \Exception
     */
    public function isPhoneBeUsed($phone): bool
    {
        return (bool)$this->query->select('phone')
            ->from('wei_users')->where(['phone' => $phone, 'del_time' => 0])->column();
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
     * @param Partner $partner
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getUserArrByPartner(Partner $partner): array
    {
        if ($userInfo = $this->getUserInfoFromCache($partner, UserId::FLAG_PARTNER)) {
            return $userInfo;
        }

        //TODO 用户系统重构后，应当从统一的 partner 表查询信息
        if ($partner->type() == Partner::P_WEIXIN) {
            $userInfo = $this->getUserArrFromDB("wechat_openid=:openid", ['openid' => $partner->userId()]);
        } elseif ($partner->type() == Partner::P_ALIPAY) {
            $uid = $this->query->select('uid')->from('wei_auth_users')
                ->where('user_id=:userid', ['userid' => $partner->userId()])
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

        if ($fields != '*' && strpos($fields, 'wechat_openid') === false) {
            $fields .= ",wechat_openid";
        }
        if ($fields != '*' && strpos($fields, 'uid') === false) {
            $fields .= ",uid";
        }

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

        // TODO 需获取 relUids
        $userInfo['rel_uids'] = [];

        /**
         * partner 信息
         */
        $userInfo['partners'] = [];
        // 微信
        if ($userInfo['wechat_openid']) {
            $userInfo['partners'][] = ['type' => Partner::P_WEIXIN, 'user_id' => $userInfo['wechat_openid']];
        }
        // 支付宝
        $alipayUserId = $this->query
            ->select('user_id')
            ->from('wei_auth_users')
            ->where("type=1 and is_delete=0 and uid=:uid", ['uid' => $userInfo['uid']])
            ->column();
        if ($alipayUserId) {
            $userInfo['partners'][] = ['type' => Partner::P_ALIPAY, 'user_id' => $alipayUserId];
        }

        return $userInfo;
    }

    /**
     * 从缓存获取用户数据
     * 注意：所有非 uid 获取情况都会转化为通过 uid 获取，这样更新缓存都时候只需要更新 uid 对应都缓存即可
     * @param mixed $flag 用户标识，如 uid，phone，partner 等
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

    /**
     * @param User $user
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function clearUserCache(User $user)
    {
        $this->cache->delete($this->getUserCacheKey($user->uid(), UserId::FLAG_UID));
    }

    private function getUserCacheKey($flag, int $type): string
    {
        if ($flag instanceof Partner) {
            $flag = $flag->userId() . '==' . $flag->type() . "==" . $flag->flag();
        }

        return "userinfo-" . md5("{$type}-{$flag}");
    }

    private function addCarNumber(User $user)
    {
        // TODO
    }

    /**
     * @param User $user
     * @throws InvalidOperationException
     * @throws \Exception
     */
    private function addUserPartner(User $user)
    {
        // 支付宝大号
        if ($alipayPartner = $user->getPartner(Partner::P_ALIPAY)) {
            $this->query->insert('wei_auth_users')
                ->values([
                    'type' => 1,
                    'uid' => $user->uid(),
                    'user_id' => $alipayPartner->userId(),
                    'create_time' => time(),
                    'update_time' => time()
                ])->execute();
        }
    }

    private function isCarNumberChanged(User $newUser, User $oldUser): bool
    {
        $carNum1 = $newUser->carNumbers;
        $carNum2 = $oldUser->carNumbers;

        return count($carNum1) != count($carNum2) || array_diff($carNum1, $carNum2) || array_diff($carNum2, $carNum1);
    }
}
