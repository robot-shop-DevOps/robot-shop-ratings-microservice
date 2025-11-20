<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use robotshop\ratings\Kernel;
use Symfony\Component\HttpFoundation\Request;

$config = require __DIR__ . '/config/config.php';

$kernel = new Kernel($config['app_env'], $config['debug']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);