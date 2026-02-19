<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Security\PasswordHasher;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class RegisterAction extends Action
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
        $data = $this->getFormData();
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirm = (string) ($data['passwordConfirm'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            throw new HttpBadRequestException($this->request, 'Missing required fields.');
        }
        if ($password !== $passwordConfirm) {
            throw new HttpBadRequestException($this->request, 'Password confirmation does not match.');
        }

        if ($this->users->findByEmail($email)) {
            throw new HttpBadRequestException($this->request, 'Email already registered.');
        }

        $userId = $this->users->create($name, $email, $this->hasher->hash($password));
        $user = $this->users->findById($userId);

        return $this->respondWithData(['user' => $user], 201);
    }
}
