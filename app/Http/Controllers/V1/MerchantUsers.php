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
     */
    public function bind()
    {

    }
}
