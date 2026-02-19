<?php

declare(strict_types=1);

namespace App\Application\Actions\Profile;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class UpdateProfileAction extends Action
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

        $data = $this->getFormData();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new HttpBadRequestException($this->request, 'Name is required.');
        }

        $updated = $this->users->updateName((int) $userId, $name);
        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Unable to update profile.');
        }

        return $this->respondWithData(['user' => $updated]);
    }
}
