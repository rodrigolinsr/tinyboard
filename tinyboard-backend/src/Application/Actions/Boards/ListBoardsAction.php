<?php

declare(strict_types=1);

namespace App\Application\Actions\Boards;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\BoardRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;

class ListBoardsAction extends Action
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

        return $this->respondWithData(['boards' => $this->boards->listByUser((int) $userId)]);
    }
}
