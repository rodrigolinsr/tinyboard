<?php

declare(strict_types=1);

use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Repositories\BoardRepository;
use App\Infrastructure\Repositories\TaskRepository;
use App\Infrastructure\Repositories\CommentRepository;
use App\Infrastructure\Repositories\ApiKeyRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(UserRepository::class),
        BoardRepository::class => \DI\autowire(BoardRepository::class),
        TaskRepository::class => \DI\autowire(TaskRepository::class),
        CommentRepository::class => \DI\autowire(CommentRepository::class),
        ApiKeyRepository::class => \DI\autowire(ApiKeyRepository::class),
    ]);
};
