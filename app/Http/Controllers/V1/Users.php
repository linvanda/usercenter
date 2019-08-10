<?php

namespace App\Http\Controllers\V1;

use App\Domain\User\IUserRepository;
use App\Domain\User\Merchant;
use App\Domain\User\Partner;
use App\Domain\User\User;
use App\Domain\User\UserId;
use App\Domain\User\UserService;
use App\DTO\User\UserDTO;
use WecarSwoole\Client\API;
use WecarSwoole\Config\Config;
use WecarSwoole\Container;
use WecarSwoole\Http\Controller;

/**
 * 用户控制器
 * Class Users
 * @package App\Http\Controllers\V1
 */
class Users extends Controller
{
    protected function validateRules(): array
    {
        return [
            'info' => [
                'user_flag' => ['required'],
                'flag_type' => ['required', 'inArray' => [1, 3, 4]],
                'partner_type' => ['integer', 'optional'],
            ],
            'add' => [
                'gender' => ['optional', 'inArray' => [0, 1, 2]],
                'birthday' => ['optional', 'regex' => ['arg' => '/\d{4}-\d{2}-\d{2}/', 'msg' => '生日必须是 YYYY-mm-dd 格式']],
                'phone' => ['optional', 'length' => 11],
                'partner_type' => ['optional', 'integer'],
                'update_strategy' => [
                    'optional',
                    'inArray' => [
                        User::UPDATE_ONLY_NULL,
                        User::UPDATE_NEW,
                        User::UPDATE_NONE
                    ]
                ],
                'merchant_type' => [
                    'optional',
                    'inArray' => [
                        Merchant::T_PLATFORM,
                        Merchant::T_GROUP,
                        Merchant::T_ORG,
                        Merchant::T_STATION
                    ]
                ],
                'merchant_id' => ['optional', 'integer'],
            ],
            'edit' => [
                'uid' => ['required'],
                'gender' => ['optional', 'inArray' => [0, 1, 2]],
                'birthday' => ['optional', 'regex' => ['arg' => '/\d{4}-\d{2}-\d{2}/', 'msg' => '生日必须是 YYYY-mm-dd 格式']],
                'phone' => ['optional', 'length' => 11],
            ],
            'changePhone' => [
                'uid' => ['required'],
                'phone' => ['required', 'length' => 11],
            ],
            'bindPartner' => [
                'uid' => ['required'],
                'partner_id' => ['required'],
                'partner_flag' => ['required'],
                'partner_type' => ['required', 'inArray' => [
                    Partner::P_WEIXIN,
                    Partner::P_ALIPAY,
                    Partner::P_OTHER
                ]]
            ],
            'clearCache' => [
                'flag' => ['required', 'equal' => ['5hr39opqm9i4', false, '非法请求']],
                'uid' => ['required']
            ]
        ];
    }

    /**
     * 通过 partner_id+partner_type、phone 或者 uid 获取用户信息
     * @throws \Throwable
     */
    public function info()
    {
        $params = $this->params();

        $userId = null;
        switch ($params['flag_type']) {
            case UserId::FLAG_UID:
                $userId = new UserId($params['user_flag'], null, [], null);
                break;
            case UserId::FLAG_PHONE:
                $userId = new UserId(null, $params['user_flag'], [], null);
                break;
            case UserId::FLAG_PARTNER:
                $userId = new UserId(
                    null,
                    null,
                    [],
                    new Partner($params['user_flag'], $params['partner_type'], $params['partner_flag'])
                );
                break;
        }

        $userDTO = Container::get(IUserRepository::class)->getDTOByUserId($userId);
        $this->return($userDTO ? $userDTO->toArray() : []);
    }

    /**
     * 添加用户
     * 可接收字段：name、nickname、phone、gender、birthday、id_number_type、id_number、channel、
     *           partner_type 、partner_id、partner_flag、merchant_type、merchant_id、car_numbers
     * 其中没有必填字段
     * 添加成功则返回 uid，否则抛出异常
     * @throws \Throwable
     */
    public function add()
    {
        $params = $this->params();
        if (isset($params['car_numbers']) && is_string($params['car_numbers'])) {
            $params['car_numbers'] = array_filter(explode(',', $params['car_numbers']));
        }
        $userDTO = new UserDTO($params, true, false);

        $this->return(
            [
                'uid' => Container::get(UserService::class)
                    ->addUser($userDTO, $params['update_strategy'] ?? User::UPDATE_ONLY_NULL)->uid()
            ]
        );
    }

    /**
     * 修改用户信息
     * 可修改的信息：name、nickname、phone、gender、birthday
     * @throws \Throwable
     */
    public function edit()
    {
        Container::get(UserService::class)->updateUser(
            new UserDTO(
                [
                    'uid' => $this->params('uid'),
                    'name' => $this->params('name'),
                    'nickname' => $this->params('nickname'),
                    'phone' => $this->params('phone'),
                    'gender' => $this->params('gender'),
                    'birthday' => $this->params('birthday')
                ],
                true,
                false
            )
        );

        $this->return();
    }

    /**
     * 绑定用户 partner
     *  uid 必填，用户uid
     *  partner_id      必填，第三方用户id（如微信大号 openid）
     *  partner_flag    必填，第三方标识（如微信大号 app_id）
     *  partner_type    必填，第三方类型（如微信大号）
     * @throws \Throwable
     */
    public function bindPartner()
    {
        $params = $this->params();
        $partner = new Partner($params['partner_id'], $params['partner_type'], $params['partner_flag']);
        Container::get(UserService::class)->bindPartner($params['uid'], $partner);

        $this->return();
    }

    /**
     * 清除用户缓存
     * @throws \Throwable
     */
    public function clearCache()
    {
        Container::get(IUserRepository::class)->clearUserCache($this->params('uid'));
        $this->return('清除成功');
    }
}
