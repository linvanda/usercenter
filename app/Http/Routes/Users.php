<?php

namespace App\Http\Routes;

use WecarSwoole\Http\ApiRoute;

class Users extends ApiRoute
{
    public function map()
    {
        /*
         * 查询用户信息
         * user_flag：必填。用户标识 (如 uid、phone、大号 openid)
         * flag_type: 必填。表示 user_flag 的类型：1 uid，3 phone，4 partner_id(第三方id)
         * partner_flag：flag_type = 4 时需要提供该参数，第三方标识（如微信大号appid）
         * partner_type：flag_type = 4 时必填，见 Partner 里面的常量定义
         */
        $this->get('/v1/user/info', '/V1/Users/info');

        /**
         * 添加用户
         * 可接收的字段：
         * name、nickname、phone、gender、birthday、id_number_type、id_number、channel、
         * partner_type 、partner_id、partner_flag、merchant_type、merchant_id、car_numbers
         * update_strategy 当用户存在时，更新策略：1 不更新，2 仅更新空值，3 以新值更新旧值，默认 2
         */
        $this->post('/v1/user/add', '/V1/Users/add');

        /**
         * 用户-商户关系绑定
         * uid 必填，用户uid
         * merchant_type 必填，商户类型
         * merchant_id 必填，商户 id
         */
        $this->post('/v1/user/merchant/bind', '/V1/MerchantUsers/bind');

        /**
         * 修改用户信息
         *  uid 必填，用户uid
         *  以下可选
         *  name、nickname、phone、gender、birthday
         */
        $this->post('/v1/user/edit', '/V1/Users/edit');

        /**
         * 合并用户
         * target_uid   必填，合并目标用户（保留的那个）
         * abandon_uid  必填，被合并的用户（舍弃的那个）
         */
        $this->post('/v1/user/merge', '/V1/Merge/mergeUsers');

        /**
         * 绑定用户 partner
         *  uid 必填，用户uid
         *  partner_id      必填，第三方用户id（如微信大号 openid）
         *  partner_flag    必填，第三方标识（如微信大号 app_id）
         *  partner_type    必填，第三方类型（如微信大号）
         */
        $this->post('/v1/user/partner/bind', '/V1/Users/bindPartner');

        /**
         * 清除用户缓存
         */
        $this->get('/v1/user/cache/clear', '/V1/Users/clearCache');

        // 测试
        $this->get('/v1/test/index', '/V1/Test/index');
    }
}
