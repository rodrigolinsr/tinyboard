<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Security\PasswordHasher;
use App\Infrastructure\Security\SessionManager;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;

class LoginAction extends Action
{
    private UserRepository $users;
    private PasswordHasher $hasher;
    private SessionManager $sessions;

    public function __construct(UserRepository $users, PasswordHasher $hasher, SessionManager $sessions, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->users = $users;
        $this->hasher = $hasher;
        $this->sessions = $sessions;
    }

    protected function action(): Response
    {
        $data = $this->getFormData();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        $user = $this->users->findByEmail($email);
        if (!$user || !$this->hasher->verify($password, $user['password_hash'])) {
            throw new HttpUnauthorizedException($this->request, 'Invalid credentials.');
        }

        $token = $this->sessions->create((int) $user['id']);
        $safeUser = $this->users->findById((int) $user['id']);

        return $this->respondWithData([
            'token' => $token,
            'user' => $safeUser,
        ]);
    }
}
