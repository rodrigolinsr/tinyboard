<?php

declare(strict_types=1);

namespace App\Application\Actions\Boards;

use App\Application\Actions\Action;
use App\Infrastructure\Repositories\BoardRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class CreateBoardAction extends Action
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

        $data = $this->getFormData();
        $name = trim((string) ($data['name'] ?? ''));
        $description = isset($data['description']) ? trim((string) $data['description']) : null;

        if ($name === '') {
            throw new HttpBadRequestException($this->request, 'Board name is required.');
        }

        $board = $this->boards->create((int) $userId, $name, $description);
        return $this->respondWithData(['board' => $board], 201);
    }
}
