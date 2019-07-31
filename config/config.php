<?php

use function WecarSwoole\Config\apollo;

$baseConfig = [
    'app_name' => '用户系统',
    // 应用标识
    'app_flag' => 'YH',
    'app_id' => 10017,
    'server' => [
        'modules' => apollo('fw.modules'),
        'app_ids' => apollo('fw.appids'),
    ],
    // 邮件。可以配多个
    'mailer' => [
        'default' => [
            'host' => 'smtp.exmail.qq.com',
            'username' => 'robot@weicheche.cn',
            'password' => 'Chechewei123'
        ]
    ],
    // 并发锁配置
    'concurrent_locker' => [
        'onoff' => 'on',
        'redis' => 'main'
    ],
    // 请求日志配置。默认是关闭的，如果项目需要开启，则自行修改为 on
    'request_log' => [
        'onoff' => 'off',
        // 记录哪些请求类型的日志
        'methods' => ['POST', 'GET', 'PUT', 'DELETE']
    ],
    /**
     * 数据库配置建议以数据库名作为 key
     * 如果没有读写分离，则可不分 read, write，直接在里面写配置信息
     */
    'mysql' => [
        'weicheche' => [
            // 读库使用二维数组配置，以支持多个读库
            'read' => [
                [
                    'host' => apollo('fw.mysql.weicheche.ro', 'weicheche_read.host'),
                    'port' => apollo('fw.mysql.weicheche.ro', 'weicheche.port'),
                    'user' => apollo('fw.mysql.weicheche.ro', 'weicheche_read.username'),
                    'password' => apollo('fw.mysql.weicheche.ro', 'weicheche_read.password'),
                    'database' => apollo('fw.mysql.weicheche.ro', 'weicheche_read.dbname'),
                    'charset' => apollo('fw.mysql.weicheche.ro', 'weicheche_read.charset'),
                ]
            ],
            // 仅支持一个写库
            'write' => [
                'host' => apollo('fw.mysql.weicheche.rw', 'weicheche.host'),
                'port' => apollo('fw.mysql.weicheche.rw', 'weicheche.port'),
                'user' => apollo('fw.mysql.weicheche.rw', 'weicheche.username'),
                'password' => apollo('fw.mysql.weicheche.rw', 'weicheche.password'),
                'database' => apollo('fw.mysql.weicheche.rw', 'weicheche.dbname'),
                'charset' => apollo('fw.mysql.weicheche.rw', 'weicheche.charset'),
            ],
            // 连接池配置
            'pool' => [
                'size' => 15
            ]
        ],
    ],
    'redis' => [
        'main' => [
            'host' => apollo('fw.redis.01', 'redis.host'),
            'port' => apollo('fw.redis.01', 'redis.port'),
            'auth' => apollo('fw.redis.01', 'redis.auth'),
        ],
        'cache' => [
            'host' => apollo('fw.redis.01', 'redis.host'),
            'port' => apollo('fw.redis.01', 'redis.port'),
            'auth' => apollo('fw.redis.01', 'redis.auth'),
        ],
    ],
];

return array_merge(
    $baseConfig,
    ['logger' => include_once __DIR__ . '/logger.php'],
    ['api' => require_once __DIR__ . '/api/api.php'],
    ['subscriber' => require_once __DIR__ . '/subscriber/subscriber.php'],
    require_once __DIR__ . '/env/' . ENVIRON . '.php'
);
