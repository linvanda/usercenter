<?php

use \WecarSwoole\Client\Http\Component\WecarWithNoZipHttpRequestAssembler;

/**
 * 喂车内部子系统 api 定义
 */
return [
    'config' => [
        'http' => [
            'request_assembler' => WecarWithNoZipHttpRequestAssembler::class,
        ]
    ],
    // api 定义
    'api' => [
        'sms.send' => [
            'server' => 'DX',
            'path' => 'v1.0/sms/send',
            'method' => 'POST'
        ],
        'test.go' => [
            'server' => 'http://localhost:9502',
            'path' => '/v1/test/go',
            'method' => 'GET'
        ],
    ]
];
