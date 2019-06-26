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
        'oil.info' => [
            'server' => 'OL',
            'path' => '/stationoil/getStationOilPriceInfoV2',
            'method' => 'POST'
        ],
        'user.add' => [
            'server' => 'http://localhost:9501',
            'path' => '/v1/users',
            'method' => 'POST'
        ],
        'sms.send' => [
            'server' => 'DX',
            'path' => 'v1.0/sms/send',
            'method' => 'POST'
        ]
    ]
];
