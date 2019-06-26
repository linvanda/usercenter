<?php

namespace App\Http\Routes;

use WecarSwoole\Http\ApiRoute;

class Users extends ApiRoute
{
    public function map()
    {
        /*
         * 查询用户信息
         * flag_params:
         *      user_flag：必填。用户标识
         * query_params:
         *      flag_type: 必填。表示 user_flag 的类型：1 uid，3 phone，4 partner_id(第三方id)
         *      partner_type：flag_type = 4 时必填，第三方类型，1 微信，2 支付宝，100 其它
         */
        $this->get('/v1/users/{user_flag}', '/V1/Users/info');
        // 添加用户
        $this->post('/v1/users', '/V1/Users/add');
        // 用户-商户关系绑定
        $this->post('/v1/merchants/{merchant}/users/{uid}', '/V1/MerchantUsers/bind');
        // 修改用户信息
        $this->put('/v1/users/{uid}', '/V1/Users/edit');
        // 查询商户-用户列表
        $this->get('/v1/merchants/{merchant}/users', '/V1/MerchantUsers/getUsers');
        // 合并用户
        $this->post('/v1/users/merge', '/V1/Merge/mergeUsers');
    }
}
