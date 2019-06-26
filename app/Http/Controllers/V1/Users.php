<?php

namespace App\Http\Controllers\V1;

use App\Domain\User\IUserRepository;
use App\Domain\User\UserId;
use Psr\SimpleCache\CacheInterface;
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
            ]
        ];
    }

    /**
     * 通过 partner_id+partner_type、phone 或者 uid 获取用户信息
     * @throws \Swoole\Exception
     */
    public function info()
    {
        $params = $this->params();

        $userId = new UserId();
        $flag = isset($params['partner_type']) && $params['flag_type'] == UserId::FLAG_PARTNER ?
            [$params['user_flag'], $params['partner_type']] : $params['user_flag'];
        $userId->setFLag($flag, $params['flag_type']);

        $this->return(Container::get(IUserRepository::class)->getDTOByUserId($userId));
    }

    /**
     * 添加用户
     */
    public function add()
    {
    }

    /**
     * 修改用户信息
     */
    public function edit()
    {
    }
}
