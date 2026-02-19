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

class UpdateTaskAction extends Action
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
        $data = $this->getFormData();
        $payload = [];

        if (isset($data['title'])) {
            $title = trim((string) $data['title']);
            if ($title === '') {
                throw new HttpBadRequestException($this->request, 'Task title is required.');
            }
            $payload['title'] = $title;
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'] !== null ? trim((string) $data['description']) : null;
        }

        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, ['todo', 'in_progress', 'completed', 'blocked'], true)) {
                throw new HttpBadRequestException($this->request, 'Invalid task status.');
            }
            $payload['status'] = $status;
        }

        if (isset($data['position'])) {
            $payload['position'] = (int) $data['position'];
        }

        $task = $this->tasks->update($taskId, $boardId, $payload);
        if (!$task) {
            throw new HttpNotFoundException($this->request, 'Task not found.');
        }

        return $this->respondWithData(['task' => $task]);
    }
}
