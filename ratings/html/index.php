<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use robotshop\ratings\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

/* -------------------------
   Config
--------------------------*/
$config  = require __DIR__ . '/config/config.php';
$service = 'ratings';

/* -------------------------
   Logger (JSON)
--------------------------*/
$logger = new Logger($service);

$handler = new StreamHandler('php://stdout', Logger::INFO);
$handler->setFormatter(new JsonFormatter());

$logger->pushHandler($handler);

/* -------------------------
   Global error handling
--------------------------*/
set_exception_handler(function (Throwable $e) use ($logger) {
    $logger->error('unhandled exception', [
        'service'   => 'ratings',
        'exception' => get_class($e),
        'message'   => $e->getMessage(),
        'file'      => $e->getFile(),
        'line'      => $e->getLine(),
    ]);

    http_response_code(500);
    echo 'Internal Server Error';
});

set_error_handler(function ($severity, $message, $file, $line) use ($logger) {
    $logger->error('php error', [
        'service'  => 'ratings',
        'severity' => $severity,
        'message'  => $message,
        'file'     => $file,
        'line'     => $line,
    ]);
});

/* -------------------------
   Startup log
--------------------------*/
$logger->info('service starting', [
    'service' => $service,
    'env'     => $config['app_env'],
]);

/* -------------------------
   Kernel bootstrap
--------------------------*/
$kernel   = new Kernel($config['app_env'], $config['debug'], $logger);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);