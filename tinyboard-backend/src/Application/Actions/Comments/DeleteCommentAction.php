<?php

declare(strict_types=1);

namespace App\Application\Actions\Comments;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\BoardRepository;
use App\Infrastructure\Repositories\TaskRepository;
use App\Infrastructure\Repositories\CommentRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class DeleteCommentAction extends Action
{
    private CommentRepository $comments;
    private TaskRepository $tasks;
    private BoardRepository $boards;

    public function __construct(CommentRepository $comments, TaskRepository $tasks, BoardRepository $boards, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->comments = $comments;
        $this->tasks = $tasks;
        $this->boards = $boards;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }

        $taskId = (int) $this->resolveArg('taskId');
        $boardId = (int) $this->resolveArg('boardId');
        $board = $this->boards->findById($boardId, (int) $userId);
        if (!$board) {
            throw new HttpNotFoundException($this->request, 'Board not found.');
        }
        $task = $this->tasks->findById($taskId, $boardId);
        if (!$task) {
            throw new HttpNotFoundException($this->request, 'Task not found.');
        }
        $commentId = (int) $this->resolveArg('id');

        $deleted = $this->comments->delete($commentId, $taskId);
        if (!$deleted) {
            throw new HttpNotFoundException($this->request, 'Comment not found.');
        }

        return $this->respondWithData(['ok' => true]);
    }
}
