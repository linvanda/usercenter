<?php

use WecarSwoole\Util\Mock;
use WecarSwoole\Client\Config\HttpConfig;
use WecarSwoole\Client\Contract\IHttpRequestBean;
use Swoole\Coroutine as Co;

$mock = new Mock();

return [
    /**
     * 完整返回格式(完整格式必须至少同时有 http_code 和 body)：
     *      [
     *          'http_code' => 200, // http code
     *          'body' => ... // http body，数组或者字符串，或者其他实现了 __toString() 的对象
     *          'headers' => [], // http 响应头
     *          'activate' => 1, // 激活，0表示不再使用该 mock 数据，将请求真实数据
     *      ]
     * 注意：如果直接返回数组，则多次使用的是同一份模拟数据，如果想每次都随机生成不同的，需要使用匿名函数
     *
     */
    'weiche:oil.info' => function (HttpConfig $config, IHttpRequestBean $request) use ($mock) {
        // 此处模拟响应延迟
        Co::sleep(2);

        return [
            'http_code' => 200,
            'body' => [
                'status' => 200,
                'data' => [
                    'id' => $mock->number('100-10000'),
                    'name' => $request->getParams()['name'],
                    'age' => $mock->number('10-20'),
                ]
            ],
            'activate' => true
        ];
    },
    // 直接返回数据
    'weiche:user.add' => [
        'status' => 200,
        'msg' => 'ok',
        'data' => [
            'uid' => 123122
        ]
    ]
];
