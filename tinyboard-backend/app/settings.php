<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    $getEnv = function (string $key, $default = null) {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    };

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () use ($getEnv) {
            return new Settings([
                'displayErrorDetails' => $getEnv('APP_DEBUG', 'true') === 'true',
                'logError'            => true,
                'logErrorDetails'     => true,
                'logger' => [
                    'name' => 'my-board',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'db' => [
                    'driver' => 'sqlite',
                    'database' => $getEnv('DB_PATH', __DIR__ . '/../var/database.sqlite'),
                ],
                'security' => [
                    'internalApiKey' => $getEnv('INTERNAL_API_KEY', 'change-me'),
                    'internalAllowedHosts' => $getEnv('INTERNAL_API_ALLOWED_HOSTS', '*.localhost,localhost,127.0.0.1'),
                    'sessionTtlHours' => (int) $getEnv('SESSION_TTL_HOURS', '720'),
                ],
                'cors' => [
                    'allowedOrigins' => $getEnv('CORS_ALLOWED_ORIGINS', '*'),
                    'allowedHeaders' => 'Content-Type, X-API-Key, X-Internal-API-Key, X-Session-Token',
                    'allowedMethods' => 'GET, POST, PATCH, DELETE, OPTIONS',
                ],
            ]);
        }
    ]);
};
