<?php

declare(strict_types=1);

namespace App\Application\Actions\ApiKeys;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\ApiKeyRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;

class ListApiKeysAction extends Action
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

        return $this->respondWithData(['keys' => $this->keys->listByUser((int) $userId)]);
    }
}
