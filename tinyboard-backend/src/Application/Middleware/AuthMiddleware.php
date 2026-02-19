<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Settings\SettingsInterface;
use App\Infrastructure\Security\ApiKeyService;
use App\Infrastructure\Security\HostMatcher;
use App\Infrastructure\Security\SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;

class AuthMiddleware implements Middleware
{
    private SettingsInterface $settings;
    private ApiKeyService $apiKeyService;
    private SessionManager $sessionManager;
    private HostMatcher $hostMatcher;

    public function __construct(
        SettingsInterface $settings,
        ApiKeyService $apiKeyService,
        SessionManager $sessionManager,
        HostMatcher $hostMatcher
    ) {
        $this->settings = $settings;
        $this->apiKeyService = $apiKeyService;
        $this->sessionManager = $sessionManager;
        $this->hostMatcher = $hostMatcher;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $internalKey = $request->getHeaderLine('X-Internal-API-Key');
        $externalKey = $request->getHeaderLine('X-API-Key');
        $sessionToken = $request->getHeaderLine('X-Session-Token');
        $security = $this->settings->get('security');

        if ($internalKey !== '') {
            $host = $this->resolveHost($request);
            if (!$this->hostMatcher->matches($host, (string) $security['internalAllowedHosts'])) {
                throw new HttpUnauthorizedException($request, 'Internal API key not allowed from this host.');
            }
            if (!hash_equals((string) $security['internalApiKey'], $internalKey)) {
                throw new HttpUnauthorizedException($request, 'Invalid internal API key.');
            }
            return $handler->handle($request
                ->withAttribute('auth_type', 'internal')
                ->withAttribute('user_id', null)
                ->withAttribute('api_key_id', null));
        }

        if ($sessionToken !== '') {
            $userId = $this->sessionManager->validate($sessionToken);
            if ($userId === null) {
                throw new HttpUnauthorizedException($request, 'Invalid session token.');
            }
            return $handler->handle($request
                ->withAttribute('auth_type', 'session')
                ->withAttribute('user_id', $userId)
                ->withAttribute('api_key_id', null));
        }

        if ($externalKey !== '') {
            $keyRecord = $this->apiKeyService->findKeyRecordByKey($externalKey);
            if ($keyRecord === null) {
                throw new HttpUnauthorizedException($request, 'Invalid API key.');
            }
            return $handler->handle($request
                ->withAttribute('auth_type', 'api_key')
                ->withAttribute('user_id', $keyRecord['user_id'])
                ->withAttribute('api_key_id', $keyRecord['id']));
        }

        throw new HttpUnauthorizedException($request, 'Missing authentication.');
    }

    private function resolveHost(Request $request): string
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin !== '') {
            $host = parse_url($origin, PHP_URL_HOST);
            if (is_string($host)) {
                return $host;
            }
        }

        $hostHeader = $request->getHeaderLine('Host');
        if ($hostHeader !== '') {
            return explode(':', $hostHeader)[0];
        }

        return '';
    }
}
