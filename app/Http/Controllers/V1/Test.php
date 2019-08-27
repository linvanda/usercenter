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
        $this->return("123");
    }

    public function go()
    {
        Container::get(LoggerInterface::class)->info("come here");
    }

    public function sa()
    {
        Container::get(LoggerInterface::class)->critical("text err");
    }
}
