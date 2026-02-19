<?php

declare(strict_types=1);

namespace App\Application\Actions\Tasks;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\BoardRepository;
use App\Infrastructure\Repositories\TaskRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class CreateTaskAction extends Action
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
        $data = $this->getFormData();
        $title = trim((string) ($data['title'] ?? ''));
        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        $status = (string) ($data['status'] ?? 'todo');
        $position = (int) ($data['position'] ?? 0);

        if ($title === '') {
            throw new HttpBadRequestException($this->request, 'Task title is required.');
        }

        if (!in_array($status, ['todo', 'in_progress', 'completed', 'blocked'], true)) {
            throw new HttpBadRequestException($this->request, 'Invalid task status.');
        }

        $task = $this->tasks->create($boardId, $title, $description, $status, $position);
        return $this->respondWithData(['task' => $task], 201);
    }
}
