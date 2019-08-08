<?php

namespace App\Http\Controllers\V1;

use App\Domain\User\UserService;
use WecarSwoole\Container;
use WecarSwoole\Http\Controller;

/**
 * 商户用户
 * Class MerchantUsers
 * @package App\Http\Controllers\V1
 */
class MerchantUsers extends Controller
{
    protected function validateRules(): array
    {
        return [
            'bind' => [
                'uid' => ['required'],
                'merchant_type' => ['required'],
                'merchant_id' => ['required'],
            ]
        ];
    }

    /**
     * 用户绑定到商户
     * 参数：
     *  uid 必填
     *  merchant_type   必填
     *  merchant_id     必填
     * @throws \Throwable
     */
    public function bind()
    {
        $params = $this->params();

        Container::get(UserService::class)->bindMerchant(
            $params['uid'],
            $params['merchant_type'],
            $params['merchant_id']
        );
        $this->return();
    }
}
