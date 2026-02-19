<?php

declare(strict_types=1);

namespace App\Application\Actions\Tasks;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\BoardRepository;
use App\Infrastructure\Repositories\TaskRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteTaskAction extends Action
{
    private TaskRepository $tasks;
    private BoardRepository $boards;

    public function __construct(TaskRepository $tasks, BoardRepository $boards, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->tasks = $tasks;
        $this->boards = $boards;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }

        $boardId = (int) $this->resolveArg('boardId');
        $board = $this->boards->findById($boardId, (int) $userId);
        if (!$board) {
            throw new HttpNotFoundException($this->request, 'Board not found.');
        }
        $taskId = (int) $this->resolveArg('id');

        $deleted = $this->tasks->delete($taskId, $boardId);
        if (!$deleted) {
            throw new HttpNotFoundException($this->request, 'Task not found.');
        }

        return $this->respondWithData(['ok' => true]);
    }
}
