<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Application\Actions\Auth\RegisterAction;
use App\Application\Actions\Auth\LoginAction;
use App\Application\Actions\Auth\LogoutAction;
use App\Application\Actions\Auth\MeAction;
use App\Application\Actions\Boards\ListBoardsAction;
use App\Application\Actions\Boards\CreateBoardAction;
use App\Application\Actions\Boards\UpdateBoardAction;
use App\Application\Actions\Boards\DeleteBoardAction;
use App\Application\Actions\Tasks\ListTasksAction;
use App\Application\Actions\Tasks\CreateTaskAction;
use App\Application\Actions\Tasks\UpdateTaskAction;
use App\Application\Actions\Tasks\DeleteTaskAction;
use App\Application\Actions\Comments\ListCommentsAction;
use App\Application\Actions\Comments\CreateCommentAction;
use App\Application\Actions\Comments\DeleteCommentAction;
use App\Application\Actions\ApiKeys\ListApiKeysAction;
use App\Application\Actions\ApiKeys\CreateApiKeyAction;
use App\Application\Actions\ApiKeys\RevokeApiKeyAction;
use App\Application\Actions\Profile\ChangePasswordAction;
use App\Application\Actions\Profile\UpdateProfileAction;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Middleware\InternalOnlyMiddleware;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->get('/docs', function (Request $request, Response $response) {
        $contents = file_get_contents(__DIR__ . '/../public/docs/index.html');
        $response->getBody()->write($contents);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->group('/auth', function (Group $group) {
        $group->post('/register', RegisterAction::class);
        $group->post('/login', LoginAction::class);
        $group->post('/logout', LogoutAction::class);
        $group->get('/me', MeAction::class);
    })->add(InternalOnlyMiddleware::class)->add(AuthMiddleware::class);

    $app->group('/boards', function (Group $group) {
        $group->get('', ListBoardsAction::class);
        $group->post('', CreateBoardAction::class);
        $group->patch('/{id}', UpdateBoardAction::class);
        $group->delete('/{id}', DeleteBoardAction::class);

        $group->get('/{boardId}/tasks', ListTasksAction::class);
        $group->post('/{boardId}/tasks', CreateTaskAction::class);
        $group->patch('/{boardId}/tasks/{id}', UpdateTaskAction::class);
        $group->delete('/{boardId}/tasks/{id}', DeleteTaskAction::class);

        $group->get('/{boardId}/tasks/{taskId}/comments', ListCommentsAction::class);
        $group->post('/{boardId}/tasks/{taskId}/comments', CreateCommentAction::class);
        $group->delete('/{boardId}/tasks/{taskId}/comments/{id}', DeleteCommentAction::class);
    })->add(AuthMiddleware::class);

    $app->group('/profile', function (Group $group) {
        $group->get('/api-keys', ListApiKeysAction::class);
        $group->post('/api-keys', CreateApiKeyAction::class);
        $group->delete('/api-keys/{id}', RevokeApiKeyAction::class);
        $group->patch('', UpdateProfileAction::class);
        $group->patch('/password', ChangePasswordAction::class);
    })->add(AuthMiddleware::class);
};
