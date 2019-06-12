<?php

namespace App\Http\Controllers\V1;

use WecarSwoole\Http\Controller;

/**
 * 商户用户
 * Class MerchantUsers
 * @package App\Http\Controllers\V1
 */
class MerchantUsers extends Controller
{
    /**
     * 用户绑定到商户
     */
    public function bind()
    {

    }

    /**
     * 获取商户用户列表
     */
    public function getUsers()
    {
        $this->return([['name' => '李娇'], ['name' => '李娇'],]);
    }
}