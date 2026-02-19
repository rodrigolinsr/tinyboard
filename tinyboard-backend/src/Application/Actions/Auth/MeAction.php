<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;

class MeAction extends Action
{
    private UserRepository $users;

    public function __construct(UserRepository $users, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->users = $users;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }
        $authType = $this->request->getAttribute('auth_type');
        $apiKeyId = $this->request->getAttribute('api_key_id');

        $user = $this->users->findById((int) $userId);
        $apiKey = null;

        if ($authType === 'api_key' && $apiKeyId) {
            $apiKey = $this->users->findApiKeyById((int) $apiKeyId);
        }

        return $this->respondWithData([
            'user' => $user,
            'auth' => [
                'type' => $authType,
                'api_key' => $apiKey,
            ],
        ]);
    }
}
