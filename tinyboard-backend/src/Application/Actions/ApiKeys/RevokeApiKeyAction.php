<?php

declare(strict_types=1);

namespace App\Application\Actions\ApiKeys;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\ApiKeyRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class RevokeApiKeyAction extends Action
{
    private ApiKeyRepository $keys;

    public function __construct(ApiKeyRepository $keys, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->keys = $keys;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }

        $keyId = (int) $this->resolveArg('id');
        $revoked = $this->keys->revoke($keyId, (int) $userId);
        if (!$revoked) {
            throw new HttpNotFoundException($this->request, 'API key not found.');
        }

        return $this->respondWithData(['ok' => true]);
    }
}
