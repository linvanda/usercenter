<?php

namespace App\Http\Middlewares;

use App\Domain\User\IUserRepository;
use WecarSwoole\Container;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use WecarSwoole\Middleware\IRouteMiddleware;

class SessionContextMiddleware implements IRouteMiddleware
{
    /**
     * 抛出异常或者返回 Response 对象则终止请求执行
     * @param Request $request
     * @param Response $response
     */
    public function handle(Request $request, Response $response)
    {
        $repos = Container::make(IUserRepository::class);
        $result = $repos->getById(93);
//         var_export($result);
    }
}
