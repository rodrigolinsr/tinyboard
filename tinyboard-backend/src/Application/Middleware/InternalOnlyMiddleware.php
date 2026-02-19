<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpForbiddenException;

class InternalOnlyMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authType = $request->getAttribute('auth_type');
        if ($authType !== 'internal') {
            throw new HttpForbiddenException($request, 'Internal access required.');
        }

        return $handler->handle($request);
    }
}
