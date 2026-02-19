<?php

declare(strict_types=1);

namespace App\Application\Actions\Profile;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Security\PasswordHasher;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class ChangePasswordAction extends Action
{
    private UserRepository $users;
    private PasswordHasher $hasher;

    public function __construct(UserRepository $users, PasswordHasher $hasher, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->users = $users;
        $this->hasher = $hasher;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }

        $data = $this->getFormData();
        $currentPassword = (string) ($data['currentPassword'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['passwordConfirm'] ?? '');

        if ($currentPassword === '' || $password === '' || $passwordConfirm === '') {
            throw new HttpBadRequestException($this->request, 'Missing required fields.');
        }

        if ($password !== $passwordConfirm) {
            throw new HttpBadRequestException($this->request, 'Password confirmation does not match.');
        }

        $user = $this->users->findWithPasswordById((int) $userId);
        if (!$user || !$this->hasher->verify($currentPassword, $user['password_hash'])) {
            throw new HttpBadRequestException($this->request, 'Current password is incorrect.');
        }

        $updated = $this->users->updatePassword((int) $userId, $this->hasher->hash($password));
        if (!$updated) {
            throw new HttpBadRequestException($this->request, 'Unable to update password.');
        }

        return $this->respondWithData(['ok' => true]);
    }
}
