<?php

declare(strict_types=1);

use DI\Container;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\PsrLogMessageProcessor;

return function (Container $container) {
    $logger = new Logger('[API]');
    
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Level::Debug));
    $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
    
    // $logger->pushProcessor(new WebProcessor());
    // $logger->pushProcessor(new MemoryUsageProcessor());
    // $logger->pushProcessor(new PsrLogMessageProcessor());
    // $logger->pushHandler(new StreamHandler('php://stderr', Level::Error));

    $logger->info('Iniciando aplicación...');

    // Inyeccion del logger en el contenedor (SLIM Framework)
    $container->set(LoggerInterface::class, fn () => $logger);
};