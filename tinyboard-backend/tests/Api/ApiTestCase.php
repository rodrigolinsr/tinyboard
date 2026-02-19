<?php

declare(strict_types=1);

namespace Tests\Api;

use Slim\App;
use Slim\Psr7\Factory\StreamFactory;
use Tests\TestCase;

class ApiTestCase extends TestCase
{
    private ?App $app = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->getAppInstance();
    }

    protected function request(
        string $method,
        string $path,
        array $payload = [],
        array $headers = [],
        array $serverParams = []
    ): array {
        $app = $this->app ?? $this->getAppInstance();
        $serverParams = array_merge(['HTTP_HOST' => 'app.localhost'], $serverParams);
        $request = $this->createRequest($method, $path, $headers, [], $serverParams);

        if ($payload !== []) {
            $stream = (new StreamFactory())->createStream(json_encode($payload));
            $request = $request->withBody($stream)->withParsedBody($payload);
        }

        $response = $app->handle($request);
        $body = (string) $response->getBody();

        return [
            'status' => $response->getStatusCode(),
            'body' => $body ? json_decode($body, true) : null,
        ];
    }

    protected function withJsonHeaders(array $headers = []): array
    {
        return array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);
    }

    protected function withInternalAuthHeaders(): array
    {
        return $this->withJsonHeaders([
            'X-Internal-API-Key' => 'change-me',
        ]);
    }

    protected function withInternalHostHeaders(array $headers, string $host): array
    {
        return array_merge($headers, ['Host' => $host]);
    }
}
