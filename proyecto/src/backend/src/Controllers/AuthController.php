<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private const ERROR_MESSAGE_LOGIN_FAILED = 'Correo o contraseña incorrectos.';
    private const ERROR_MESSAGE_LOGIN_VALIDATION = 'Debes indicar correo y contraseña.';

    public function __construct(
        private readonly UserRepository $users,
        private Logger $logger
    ) {
    }

    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return JsonResponse::error($response, self::ERROR_MESSAGE_LOGIN_VALIDATION, 422);
        }

        try {
            $user = $this->users->findByEmail($email);

            if (
                $user === null
                || !(bool) $user['activo']
                || !password_verify($password, (string) $user['password_hash'])
            ) {
                $this->logger->warning('Intento de login fallido.', ['email' => $email]);
                return JsonResponse::error($response, self::ERROR_MESSAGE_LOGIN_FAILED, 401);
            }

            $publicUser = UserRepository::toPublic($user);

            return JsonResponse::success($response, [
                'id' => $publicUser['id'],
                'nombre' => $publicUser['nombre'],
                'apellido' => $publicUser['apellido'],
                'email' => $publicUser['email'],
                'rol' => $publicUser['rol'],
            ], 'Sesión iniciada.');
        } catch (PDOException $error) {
            $this->logger->error('Error al iniciar sesión.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al iniciar sesión.', 500);
        }
    }
}
