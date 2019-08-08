<?php

namespace App\Http\Controllers\V1;

use App\Domain\User\MergeService;
use WecarSwoole\Container;
use WecarSwoole\Http\Controller;

/**
 * 合并
 * Class Merge
 * @package App\Http\Controllers\V1
 */
class Merge extends Controller
{
    protected function validateRules(): array
    {
        return [
            'mergeUsers' => [
                'target_uid' => ['required'],
                'abandon_uid' => ['required']
            ]
        ];
    }

    /**
     * @throws \App\Exceptions\InvalidMergeException
     * @throws \Throwable
     * @throws \WecarSwoole\Exceptions\Exception
     * @throws \WecarSwoole\Exceptions\InvalidOperationException
     */
    public function mergeUsers()
    {
        Container::get(MergeService::class)->merge(
            $this->params('target_uid'),
            $this->params('abandon_uid'),
            true,
            true
        );
        $this->return();
    }
}
