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
         *      partner_type：flag_type = 4 时必填，见 Partner 里面的常量定义
         *      partner_flag：flag_type = 4 时一般需要提供该参数，第三方标识（如微信卡号），微信大号和支付宝大号可以选填（建议带上）
         */
        $this->get('/v1/users/{user_flag}', '/V1/Users/info');
        /**
         * 添加用户
         * 可接收的字段：name、nickname、phone、gender、birthday、id_number_type、id_number、channel、
         *           partner_type 、partner_id、partner_flag、merchant_type、merchant_id、car_numbers
         *           update_strategy 当用户存在时，更新策略：1 不更新，2 仅更新空值，3 以新值更新旧值，默认 2
         */
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
