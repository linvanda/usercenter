<?php

return [
    'SERVER_NAME' => "usercenter",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9502,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER,
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 1,
            'task_worker_num' => 1,
            'reload_async' => true,
            'task_enable_coroutine' => true,
            'max_wait_time' => 5,
            'pid_file' => \WecarSwoole\Util\File::join(EASYSWOOLE_ROOT, 'storage/temp/master.pid')
        ],
    ],
    'TEMP_DIR' => 'storage/temp',
    'LOG_DIR' => 'storage/logs',
    'CONSOLE' => [
        'ENABLE' => true,
        'LISTEN_ADDRESS' => '127.0.0.1',
        'HOST' => '127.0.0.1',
        'PORT' => 9500,
        'USER' => 'root',
        'PASSWORD' => '123456'
    ],
    'DISPLAY_ERROR' => true,
    'PHAR' => [
        'EXCLUDE' => ['.idea', 'log', 'temp', 'easyswoole', 'easyswoole.install']
    ]
];
