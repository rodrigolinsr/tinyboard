<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Infrastructure\Security\SessionManager;
use Psr\Http\Message\ResponseInterface as Response;

class LogoutAction extends Action
{
    private SessionManager $sessions;

    public function __construct(SessionManager $sessions, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->sessions = $sessions;
    }

    protected function action(): Response
    {
        $token = $this->request->getHeaderLine('X-Session-Token');
        if ($token !== '') {
            $this->sessions->revoke($token);
        }

        return $this->respondWithData(['ok' => true]);
    }
}
