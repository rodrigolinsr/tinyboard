<?php

declare(strict_types=1);

namespace Tests;

use DI\ContainerBuilder;
use Exception;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;
use App\Infrastructure\Database\Migrator;
use App\Infrastructure\Database\Connection;
use App\Application\Handlers\HttpErrorHandler;
use App\Application\Settings\SettingsInterface;

class TestCase extends PHPUnit_TestCase
{
    use ProphecyTrait;

    /**
     * @return App
     * @throws Exception
     */
    protected function getAppInstance(): App
    {
        // Instantiate PHP-DI ContainerBuilder
        $containerBuilder = new ContainerBuilder();

        // Container intentionally not compiled for tests.

        // Set up settings
        $settings = require __DIR__ . '/../app/settings.php';
        $settings($containerBuilder);

        // Set up dependencies
        $dependencies = require __DIR__ . '/../app/dependencies.php';
        $dependencies($containerBuilder);

        // Set up repositories
        $repositories = require __DIR__ . '/../app/repositories.php';
        $repositories($containerBuilder);

        // Build PHP-DI Container instance
        $container = $containerBuilder->build();

        $this->resetDatabase($container->get(Connection::class));
        $container->get(Migrator::class)->migrate();

        // Instantiate the app
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Register middleware
        $middleware = require __DIR__ . '/../app/middleware.php';
        $middleware($app);

        // Register routes
        $routes = require __DIR__ . '/../app/routes.php';
        $routes($app);

        /** @var SettingsInterface $settings */
        $settings = $container->get(SettingsInterface::class);
        $displayErrorDetails = $settings->get('displayErrorDetails');
        $logError = $settings->get('logError');
        $logErrorDetails = $settings->get('logErrorDetails');

        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();

        $responseFactory = $app->getResponseFactory();
        $errorHandler = new HttpErrorHandler($app->getCallableResolver(), $responseFactory);
        $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        return $app;
    }

    private function resetDatabase(Connection $connection): void
    {
        $pdo = $connection->pdo();
        $pdo->exec('DROP TABLE IF EXISTS comments');
        $pdo->exec('DROP TABLE IF EXISTS tasks');
        $pdo->exec('DROP TABLE IF EXISTS boards');
        $pdo->exec('DROP TABLE IF EXISTS api_keys');
        $pdo->exec('DROP TABLE IF EXISTS sessions');
        $pdo->exec('DROP TABLE IF EXISTS users');
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $headers
     * @param array  $cookies
     * @param array  $serverParams
     * @return Request
     */
    protected function createRequest(
        string $method,
        string $path,
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $cookies = [],
        array $serverParams = []
    ): Request {
        $uri = new Uri('', '', 80, $path);
        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        $h = new Headers();
        foreach ($headers as $name => $value) {
            $h->addHeader($name, $value);
        }

        return new SlimRequest($method, $uri, $h, $cookies, $serverParams, $stream);
    }
}
