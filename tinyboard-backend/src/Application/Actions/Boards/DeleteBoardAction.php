<?php

declare(strict_types=1);

namespace App\Application\Actions\Boards;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\BoardRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteBoardAction extends Action
{
    private BoardRepository $boards;

    public function __construct(BoardRepository $boards, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->boards = $boards;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }

        $boardId = (int) $this->resolveArg('id');
        $deleted = $this->boards->delete($boardId, (int) $userId);
        if (!$deleted) {
            throw new HttpNotFoundException($this->request, 'Board not found.');
        }

        return $this->respondWithData(['ok' => true]);
    }
}
