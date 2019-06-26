<?php

namespace App\Http\Controllers\V1;

use WecarSwoole\Http\Controller;

/**
 * 合并
 * Class Merge
 * @package App\Http\Controllers\V1
 */
class Merge extends Controller
{
    public function mergeUsers()
    {
        $this->return([json_encode(basename($this->request()->getRequestTarget()))]);
    }
}