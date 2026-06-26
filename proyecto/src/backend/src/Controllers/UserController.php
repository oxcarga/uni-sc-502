<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function __construct(
        private readonly UserRepository $users, 
        private Logger $logger
    ) {
        $this->logger->info('UserController constructor');
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            return JsonResponse::success($response, $this->users->findAll());
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

        return JsonResponse::success($response, $user);
    }

    public function create(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $validation = $this->validateUserData($body);

        if ($validation !== null) {
            $this->logger->warning('Datos de usuario inválidos.', ['validation' => $validation]);
            return JsonResponse::error($response, $validation, 422);
        }

        try {
            $user = $this->users->create([
                'name' => trim((string) $body['name']),
                'email' => strtolower(trim((string) $body['email'])),
                'blood_type' => isset($body['blood_type']) ? trim((string) $body['blood_type']) : null,
            ]);

            return JsonResponse::success($response, $user, 'Usuario creado.', 201);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                $this->logger->warning('El correo electrónico ya está registrado.', ['email' => $body['email']]);
                return JsonResponse::error($response, 'El correo electrónico ya está registrado.', 409);
            }
            $this->logger->error('Error al crear el usuario.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al crear el usuario.', 500);
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
        $validation = $this->validateUserData($body);

        if ($validation !== null) {
            $this->logger->warning('Datos de usuario inválidos.', ['validation' => $validation]);
            return JsonResponse::error($response, $validation, 422);
        }

        try {
            $user = $this->users->update($id, [
                'name' => trim((string) $body['name']),
                'email' => strtolower(trim((string) $body['email'])),
                'blood_type' => isset($body['blood_type']) ? trim((string) $body['blood_type']) : null,
            ]);

            return JsonResponse::success($response, $user, 'Usuario actualizado.');
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                $this->logger->warning('El correo electrónico ya está registrado.', ['email' => $body['email']]);
                return JsonResponse::error($response, 'El correo electrónico ya está registrado.', 409);
            }

            $this->logger->error('Error al actualizar el usuario.', ['error' => $error->getMessage()]);
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

    private function validateUserData(array $body): ?string
    {
        $name = trim((string) ($body['name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $bloodType = isset($body['blood_type']) ? trim((string) $body['blood_type']) : null;

        if ($name === '') {
            return 'El nombre es obligatorio.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'El correo electrónico no es válido.';
        }

        if (!UserRepository::isValidBloodType($bloodType)) {
            return 'El tipo de sangre no es válido.';
        }

        return null;
    }
}
