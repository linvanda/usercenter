<?php

namespace App\Http\Controllers\V1;

use Psr\SimpleCache\CacheInterface;
use WecarSwoole\Container;
use WecarSwoole\Http\Controller;
use WecarSwoole\RedisFactory;

class Test extends Controller
{
    public function index()
    {
        $cache = Container::get(CacheInterface::class);
//        $cache->set("tttsk", ['name']);
        $this->return($cache->get('tttsk'));
    }
}