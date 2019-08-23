<?php

namespace App\Http\Controllers\V1;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use WecarSwoole\Client\API;
use WecarSwoole\Container;
use WecarSwoole\Exceptions\Exception;
use WecarSwoole\Http\Controller;
use WecarSwoole\Mailer;
use WecarSwoole\RedisFactory;

class Test extends Controller
{
    public function index()
    {
        var_export(swoole_get_local_ip());
    }

    public function go()
    {
        Container::get(LoggerInterface::class)->info("come here");
    }
}
