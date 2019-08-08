<?php

namespace App\Http\Controllers\V1;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use WecarSwoole\Container;
use WecarSwoole\Http\Controller;
use WecarSwoole\Mailer;
use WecarSwoole\RedisFactory;

class Test extends Controller
{
    public function index()
    {
//        Container::get(LoggerInterface::class)->emergency("hello test logger ");
    }
}
