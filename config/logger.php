<?php

use WecarSwoole\Util\File;

use function WecarSwoole\Config\apollo;

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
            'to' => json_decode(apollo('application', 'logger.emails'), true) ?: []
        ],
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/error.log'),
    ],
    'emergency' => [
        'mailer' => [
            'driver' => 'default',
            'subject' => '喂车告警',
            'to' => json_decode(apollo('application', 'logger.emails'), true) ?: []
        ],
        'file' => File::join(EASYSWOOLE_ROOT, 'storage/logs/error.log'),
        'sms' => json_decode(apollo('application', 'logger.mobiles'), true) ?: []
    ],
];
