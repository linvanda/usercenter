<?php

namespace App\Cron;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class Test extends AbstractCronTask
{

    public static function getRule(): string
    {
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return 'test-cron';
    }

    public static function run(\swoole_server $server, int $taskId, int $fromWorkerId, $flags = null)
    {
        echo "cron testaas4\n";
    }
}
