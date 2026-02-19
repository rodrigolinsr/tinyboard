<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\Migrator;
use App\Infrastructure\Security\ApiKeyService;
use App\Infrastructure\Security\PasswordHasher;
use App\Infrastructure\Security\SessionManager;
use App\Infrastructure\Security\HostMatcher;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        Connection::class => \DI\autowire(Connection::class),
        Migrator::class => \DI\autowire(Migrator::class),
        ApiKeyService::class => \DI\autowire(ApiKeyService::class),
        PasswordHasher::class => \DI\autowire(PasswordHasher::class),
        SessionManager::class => \DI\autowire(SessionManager::class),
        HostMatcher::class => \DI\autowire(HostMatcher::class),
    ]);
};
