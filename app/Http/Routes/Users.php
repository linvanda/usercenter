<?php

namespace App\Http\Routes;

use WecarSwoole\Http\ApiRoute;

class Users extends ApiRoute
{
    public function map()
    {
        // 添加用户
        $this->post('/v1/users', '/V1/Users/add');
        // 用户-商户关系绑定
        $this->post('/v1/merchants/{merchant}/users/{uid}', '/V1/MerchantUsers/bind');
        // 修改用户信息
        $this->put('/v1/users/{uid}', '/V1/Users/edit');
        // 查询用户信息
        $this->get('/v1/users/{uid}', '/V1/Users/info');
        // 查询商户-用户列表
        $this->get('/v1/merchants/{merchant}/users', '/V1/MerchantUsers/getUsers');
        // 合并用户
        $this->post('/v1/users/merge', '/V1/Merge/mergeUsers');
        $this->delete('/v1/users/{uid}', 'V1/Users/delete');
    }
}