<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware implements Middleware
{
    private SettingsInterface $settings;

    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $cors = $this->settings->get('cors');
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = (string) ($cors['allowedOrigins'] ?? '*');
        $allowOrigin = $this->resolveOrigin($origin, $allowedOrigins);
        $allowHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        if ($allowHeaders === '') {
            $allowHeaders = (string) ($cors['allowedHeaders'] ?? 'Content-Type');
        }

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = new SlimResponse(204);
            return $this->applyCorsHeaders($response, $allowOrigin, $allowHeaders, (string) $cors['allowedMethods']);
        }

        $response = $handler->handle($request);

        return $this->applyCorsHeaders(
            $response,
            $allowOrigin,
            $allowHeaders,
            (string) $cors['allowedMethods']
        );
    }

    private function resolveOrigin(string $origin, string $allowedOrigins): string
    {
        if ($allowedOrigins === '*' || $origin === '') {
            return $allowedOrigins;
        }

        $allowed = array_filter(array_map('trim', explode(',', $allowedOrigins)));
        if (in_array($origin, $allowed, true)) {
            return $origin;
        }

        return $allowedOrigins;
    }

    private function applyCorsHeaders(Response $response, string $origin, string $headers, string $methods): Response
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', $headers)
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Vary', 'Origin');
    }
}
