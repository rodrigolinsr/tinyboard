<?php

declare(strict_types=1);

namespace App\Application\Actions\ApiKeys;

use App\Application\Actions\Action;
use App\Infrastructure\Security\ApiKeyService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;

class CreateApiKeyAction extends Action
{
    private ApiKeyService $service;

    public function __construct(ApiKeyService $service, \Psr\Log\LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->service = $service;
    }

    protected function action(): Response
    {
        $userId = $this->request->getAttribute('user_id');
        if (!$userId) {
            throw new HttpUnauthorizedException($this->request, 'Missing user.');
        }

        $data = $this->getFormData();
        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            throw new HttpBadRequestException($this->request, 'Label is required.');
        }

        $result = $this->service->generate((int) $userId, $label);
        return $this->respondWithData(['key' => $result], 201);
    }
}
