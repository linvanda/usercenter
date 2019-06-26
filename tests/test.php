#!/usr/bin/env php
<?php

use EasySwoole\EasySwoole\Config;
use WecarSwoole\Util\File;

defined('ENVIRON') or define('ENVIRON', 'dev');
defined('IN_PHAR') or define('IN_PHAR', boolval(\Phar::running(false)));
defined('RUNNING_ROOT') or define('RUNNING_ROOT', realpath(getcwd()));
defined('EASYSWOOLE_ROOT') or define('EASYSWOOLE_ROOT', IN_PHAR ? \Phar::running() : realpath(getcwd()) . '/..');

$file = EASYSWOOLE_ROOT.'/vendor/autoload.php';
if (file_exists($file)) {
    require $file;
}else{
    die("include composer autoload.php fail\n");
}

Config::getInstance()->loadFile(File::join(EASYSWOOLE_ROOT, 'config/config.php'), true);

interface IA
{

}
class A implements IA
{

}

class B
{
    public function __construct(IA $a)
    {
    }
}

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(\WecarSwoole\Util\File::join(EASYSWOOLE_ROOT, 'config/di/di.php'));
$di = $builder->build();

$di->set(IA::class, \DI\create(A::class));

$b = $di->get(B::class);
