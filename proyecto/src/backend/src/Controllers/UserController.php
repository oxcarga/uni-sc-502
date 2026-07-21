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

class UserController
{
    // SQLSTATE de violación de restricción (UNIQUE). PDO lo devuelve como string.
    private const ERROR_CODE_EMAIL_DUPLICATED = '23000';
    private const ERROR_MESSAGE_EMAIL_DUPLICATED = 'El correo electrónico ya está registrado.';
    private const ERROR_MESSAGE_USER_ERROR = 'Error al crear el usuario.';
    private const ERROR_MESSAGE_USER_CREATED =
        'Revisa tu correo para confirmar la cuenta antes de iniciar sesión.';
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepository $users,
        private readonly EmailVerificationService $emailVerification,
        private Logger $logger
    ) {
        $this->logger->info('UserController constructor');
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $users = array_map(
                static fn (array $user): array => UserRepository::toPublic($user),
                $this->users->findAll()
            );

            return JsonResponse::success($response, $users);
        } catch (PDOException $error) {
            $this->logger->error('Error al consultar usuarios.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al consultar usuarios.', 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $user = $this->users->findById($id);

        if ($user === null) {
            $this->logger->warning('Usuario no encontrado.', ['user_id' => $id]);
            return JsonResponse::error($response, 'Usuario no encontrado.', 404);
        }

        return JsonResponse::success($response, UserRepository::toPublic($user));
    }

    public function create(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $validation = $this->validateUserData($body, requirePassword: true);

        if ($validation !== null) {
            $this->logger->warning('Datos de usuario inválidos.', ['validation' => $validation]);
            return JsonResponse::error($response, $validation, 422);
        }

        try {
            $user = $this->users->create([
                'first_name' => trim((string) $body['first_name']),
                'last_name' => trim((string) $body['last_name']),
                'email' => strtolower(trim((string) $body['email'])),
                'password_hash' => password_hash((string) $body['password'], PASSWORD_DEFAULT),
                'role' => 'donor',
                'email_confirmed' => false,
                'blood_type' => isset($body['blood_type']) ? trim((string) $body['blood_type']) : null,
            ]);

            $this->emailVerification->issueAndSend($user);

            return JsonResponse::success(
                $response,
                UserRepository::toPublic($user),
                self::ERROR_MESSAGE_USER_CREATED,
                201
            );
        } catch (PDOException $error) {
            if ($this->isDuplicateEmailError($error)) {
                $this->logger->warning(self::ERROR_MESSAGE_EMAIL_DUPLICATED, ['email' => $body['email']]);
                return JsonResponse::error($response, self::ERROR_MESSAGE_EMAIL_DUPLICATED, 409);
            }
            $this->logger->error(self::ERROR_MESSAGE_USER_ERROR, [
                'error' => $error->getMessage(),
                'code' => $error->getCode(),
                'errorInfo' => $error->errorInfo ?? null,
            ]);
            return JsonResponse::error($response, self::ERROR_MESSAGE_USER_ERROR, 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        if ($this->users->findById($id) === null) {
            $this->logger->warning('Usuario no encontrado.', ['user_id' => $id]);
            return JsonResponse::error($response, 'Usuario no encontrado.', 404);
        }

        $body = (array) $request->getParsedBody();
        $requirePassword = array_key_exists('password', $body) && (string) $body['password'] !== '';
        $validation = $this->validateUserData($body, requirePassword: $requirePassword);

        if ($validation !== null) {
            $this->logger->warning('Datos de usuario inválidos.', ['validation' => $validation]);
            return JsonResponse::error($response, $validation, 422);
        }

        try {
            $payload = [
                'first_name' => trim((string) $body['first_name']),
                'last_name' => trim((string) $body['last_name']),
                'email' => strtolower(trim((string) $body['email'])),
                'blood_type' => isset($body['blood_type']) ? trim((string) $body['blood_type']) : null,
            ];

            if ($requirePassword) {
                $payload['password_hash'] = password_hash((string) $body['password'], PASSWORD_DEFAULT);
            }

            $user = $this->users->update($id, $payload);

            return JsonResponse::success($response, UserRepository::toPublic($user ?? []), 'Usuario actualizado.');
        } catch (PDOException $error) {
            if ($this->isDuplicateEmailError($error)) {
                $this->logger->warning(self::ERROR_MESSAGE_EMAIL_DUPLICATED, ['email' => $body['email']]);
                return JsonResponse::error($response, self::ERROR_MESSAGE_EMAIL_DUPLICATED, 409);
            }

            $this->logger->error('Error al actualizar el usuario.', [
                'error' => $error->getMessage(),
                'code' => $error->getCode(),
                'errorInfo' => $error->errorInfo ?? null,
            ]);
            return JsonResponse::error($response, 'Error al actualizar el usuario.', 500);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        try {
            if (!$this->users->delete($id)) {
                $this->logger->warning('Usuario no encontrado.', ['user_id' => $id]);
                return JsonResponse::error($response, 'Usuario no encontrado.', 404);
            }

            return JsonResponse::success($response, null, 'Usuario eliminado.');
        } catch (PDOException $error) {
            $this->logger->error('Error al eliminar el usuario.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al eliminar el usuario.', 500);
        }
    }

    private function validateUserData(array $body, bool $requirePassword): ?string
    {
        $firstName = trim((string) ($body['first_name'] ?? ''));
        $lastName = trim((string) ($body['last_name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $bloodType = isset($body['blood_type']) ? trim((string) $body['blood_type']) : null;

        if ($firstName === '') {
            return 'El nombre es obligatorio.';
        }

        if ($lastName === '') {
            return 'El apellido es obligatorio.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }

        if ($requirePassword) {
            if ($password === '') {
                return 'La contraseña es obligatoria.';
            }

            if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
                return 'La contraseña debe tener al menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.';
            }
        }

        if (!UserRepository::isValidBloodType($bloodType)) {
            return 'El tipo de sangre no es válido.';
        }

        return null;
    }

    /**
     * PDO/MySQL a veces reporta el duplicado en errorInfo y no en getCode():
     * getCode() puede ser "HY000" mientras errorInfo = ['23000', 1062, '...'].
     */
    private function isDuplicateEmailError(PDOException $error): bool
    {
        $sqlState = (string) ($error->errorInfo[0] ?? $error->getCode());
        $driverCode = (int) ($error->errorInfo[1] ?? 0);

        return $sqlState === self::ERROR_CODE_EMAIL_DUPLICATED || $driverCode === 1062;
    }
}
