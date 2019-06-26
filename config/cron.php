<?php

/**
 * 定时任务配置
 */

return [
    // 定时任务项目名，同名的多台服务器只会有一台启动定时任务，请务必给不同项目起不同的名字，否则会相互影响
    'name' => 'usercenter-abj39877df',
    // crontab 需要 redis 或者 ip
    'redis' => 'main',
    'ip' => [],
    'tasks' => [
    ]
];
