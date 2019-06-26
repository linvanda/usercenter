<?php

use WecarSwoole\Util\File;

return [
    'debug' => [
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/info.log'),
    ],
    'info' => [
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/info.log'),
    ],
    'warning' => [
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/warning.log'),
    ],
    'error' => [
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/error.log'),
    ],
    'critical' => [
        'mailer' => [
            'driver' => 'default',
            'subject' => '喂车告警',
            'to' => [
                'songlin.zhang@weicheche.cn' => '张松林',
//                'binghua.zhou@weicheche.cn' => '周炳华',
//                'xiong.luo@weicheche.cn' => '罗雄',
//                'pingping.yan@weicheche.cn' => '颜平平',
            ]
        ],
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/error.log'),
    ],
    'emergency' => [
        'mailer' => [
            'driver' => 'default',
            'subject' => '喂车告警',
            'to' => [
                'songlin.zhang@weicheche.cn' => '张松林',
//                'binghua.zhou@weicheche.cn' => '周炳华',
//                'xiong.luo@weicheche.cn' => '罗雄',
//                'pingping.yan@weicheche.cn' => '颜平平',
            ]
        ],
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/error.log'),
        'sms' => [
            '18588495955' => '张松林',
//            '15019451216' => '罗雄',
//            '15616689842' => '颜平平',
//            '18129970536' => '周炳华',
        ]
    ],
];
