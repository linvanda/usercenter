<?php

namespace App\Http\Controllers\V1;

use App\Domain\User\IUserRepository;
use App\Domain\User\Merchant;
use App\Domain\User\PartnerUser;
use App\Domain\User\PartnerUserMap;
use App\Domain\User\User;
use App\Domain\User\UserId;
use App\DTO\User\UserDTO;
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
                'birthday' => ['optional', 'regex' => ['arg' => '\d{4}-\d{2}-\d{2}', 'msg' => '生日必须是 YYYY-mm-dd 格式']],
                'phone' => ['optional', 'length' => 11],
                'partner_type' => ['optional', 'integer'],
                'merchant_type' => [
                    'optional',
                    'inArray' => [
                        Merchant::T_PLATFORM,
                        Merchant::T_GROUP,
                        Merchant::T_ORG,
                        Merchant::T_STATION
                    ]
                ],
            ]
        ];
    }

    /**
     * @throws \Exception
     */
    public function test()
    {
//        $resp = API::invoke('weiche:oil.info', ['name' => '三字'], ['throw_exception' => true]);
//        $url = Url::realUrl('v1/user/{uid}', [], ['uid' => 3243]);
//        $this->return($resp->getBody());
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
                    new PartnerUser($params['user_flag'], $params['partner_type'], $params['partner_flag'])
                );
                break;
        }

        $this->return(Container::get(IUserRepository::class)->getDTOByUserId($userId)->toArray());
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
        $userDTO = new UserDTO($params, true, false);

        // partner 标识处理
        if ($params['partner_type'] && $params['partner_id']) {
            $userDTO->partnerUsers->add(
                new PartnerUser($params['partner_id'], $params['partner_type'], $params['partner_flag'])
            );
        }

        $this->return(['uid' => Container::make(User::class)->register($userDTO)]);
    }

    /**
     * 修改用户信息
     */
    public function edit()
    {
    }
}
