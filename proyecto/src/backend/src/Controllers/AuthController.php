<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\EmailVerificationService;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class AuthController
{
    private const ERROR_MESSAGE_LOGIN_FAILED = 'Correo o contraseña incorrectos.';
    private const ERROR_MESSAGE_LOGIN_VALIDATION = 'Debes indicar correo y contraseña.';
    private const ERROR_MESSAGE_EMAIL_NOT_CONFIRMED = 'Debes confirmar tu correo antes de iniciar sesión.';
    private const ERROR_MESSAGE_TOKEN_INVALID = 'El enlace de confirmación no es válido o ya fue usado.';
    private const ERROR_MESSAGE_TOKEN_EXPIRED = 'El enlace de confirmación ha expirado. Solicita uno nuevo.';
    private const ERROR_MESSAGE_RESEND_GENERIC = 'Si el correo está registrado y pendiente, enviamos un nuevo enlace.';

    public function __construct(
        private readonly UserRepository $users,
        private readonly EmailVerificationService $emailVerification,
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

            if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
                $this->logger->warning('Intento de login fallido.', ['email' => $email]);
                return JsonResponse::error($response, self::ERROR_MESSAGE_LOGIN_FAILED, 401);
            }

            if (!(bool) $user['active']) {
                $this->logger->warning('Intento de login con cuenta inactiva.', ['email' => $email]);
                return JsonResponse::error($response, self::ERROR_MESSAGE_LOGIN_FAILED, 401);
            }

            if (!(bool) $user['email_confirmed']) {
                $this->logger->warning('Intento de login sin correo confirmado.', ['email' => $email]);
                return JsonResponse::error($response, self::ERROR_MESSAGE_EMAIL_NOT_CONFIRMED, 403);
            }

            return JsonResponse::success(
                $response,
                UserRepository::toSession($user),
                'Sesión iniciada.'
            );
        } catch (PDOException $error) {
            $this->logger->error('Error al iniciar sesión.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al iniciar sesión.', 500);
        }
    }

    public function confirmEmail(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $query = $request->getQueryParams();
        $token = trim((string) ($body['token'] ?? $query['token'] ?? ''));

        if ($token === '') {
            return JsonResponse::error($response, self::ERROR_MESSAGE_TOKEN_INVALID, 400);
        }

        try {
            $session = $this->emailVerification->confirm($token);

            return JsonResponse::success($response, $session, 'Correo confirmado. Sesión iniciada.');
        } catch (RuntimeException $error) {
            $code = $error->getMessage();
            if ($code === 'TOKEN_EXPIRED') {
                return JsonResponse::error($response, self::ERROR_MESSAGE_TOKEN_EXPIRED, 410);
            }

            return JsonResponse::error($response, self::ERROR_MESSAGE_TOKEN_INVALID, 400);
        } catch (PDOException $error) {
            $this->logger->error('Error al confirmar correo.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al confirmar el correo.', 500);
        }
    }

    public function resendConfirmation(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return JsonResponse::error($response, 'El correo electrónico no es válido.', 422);
        }

        try {
            $this->emailVerification->resendIfApplicable($email);
        } catch (PDOException $error) {
            $this->logger->error('Error al reenviar confirmación.', ['error' => $error->getMessage()]);
            // Respuesta genérica: no filtrar existencia del email por errores internos.
        }

        return JsonResponse::success($response, null, self::ERROR_MESSAGE_RESEND_GENERIC);
    }
}
