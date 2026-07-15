<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use App\Support\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Exige sesión de servidor válida y adjunta el usuario al request.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = Session::userId();
        if ($userId === null) {
            return JsonResponse::error(
                (new ResponseFactory())->createResponse(),
                'No autenticado.',
                401
            );
        }

        $user = $this->users->findById($userId);
        if ($user === null || !(bool) $user['active'] || !(bool) $user['email_confirmed']) {
            Session::logout();

            return JsonResponse::error(
                (new ResponseFactory())->createResponse(),
                'No autenticado.',
                401
            );
        }

        return $handler->handle(
            $request->withAttribute('auth_user', UserRepository::toSession($user))
        );
    }
}
