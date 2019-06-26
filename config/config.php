<?php

$baseConfig = [
    'app_name' => '用户系统',
    // 应用标识
    'app_flag' => 'YH',
    'logger' => include_once __DIR__ . '/logger.php',
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
];

return array_merge(
    $baseConfig,
    ['cron_config' => require_once __DIR__ . '/cron.php'],
    require_once __DIR__ . '/env/' . ENVIRON . '.php'
);
