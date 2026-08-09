<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DonorProfileRepository;
use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DonorProfileController
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepository $users,
        private readonly DonorProfileRepository $profiles,
        private Logger $logger
    ) {
    }

    public function show(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden consultar este perfil.', 403);
        }

        try {
            $payload = $this->buildPayload((int) $auth['id']);
            return JsonResponse::success($response, $payload);
        } catch (PDOException $error) {
            $this->logger->error('Error al consultar perfil donante.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al consultar el perfil.', 500);
        }
    }

    public function update(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden actualizar este perfil.', 403);
        }

        $userId = (int) $auth['id'];
        $body = (array) $request->getParsedBody();
        $validation = $this->validateUpdate($body);
        if ($validation !== null) {
            return JsonResponse::error($response, $validation, 422);
        }

        try {
            $this->pdoUpdate($userId, $body);
            $payload = $this->buildPayload($userId);

            return JsonResponse::success($response, $payload, 'Perfil actualizado.');
        } catch (PDOException $error) {
            $this->logger->error('Error al actualizar perfil donante.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al actualizar el perfil.', 500);
        }
    }

    /** @param array<string, mixed> $body */
    private function pdoUpdate(int $userId, array $body): void
    {
        $firstName = trim((string) ($body['first_name'] ?? ''));
        $lastName = trim((string) ($body['last_name'] ?? ''));
        $password = (string) ($body['password'] ?? $body['new_password'] ?? '');

        $userFields = [];
        if ($firstName !== '') {
            $userFields['first_name'] = $firstName;
        }
        if ($lastName !== '') {
            $userFields['last_name'] = $lastName;
        }
        if ($password !== '') {
            $userFields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($userFields !== []) {
            $this->users->updateAccount($userId, $userFields);
        }

        $bloodType = array_key_exists('blood_type', $body)
            ? trim((string) $body['blood_type'])
            : null;

        $profileData = [
            'blood_type' => ($bloodType === '' || $bloodType === null) ? null : $bloodType,
            'birth_date' => $this->nullableDate($body['birth_date'] ?? null),
            'phone' => $this->nullableString($body['phone'] ?? null),
            'province' => $this->nullableString($body['province'] ?? null),
            'canton' => $this->nullableString($body['canton'] ?? null),
            'address' => $this->nullableString($body['address'] ?? null),
            'medical_history' => $this->nullableString($body['medical_history'] ?? null),
            'notify_nearby' => $this->boolFromBody($body, 'notify_nearby', true),
            'notify_appointments' => $this->boolFromBody($body, 'notify_appointments', true),
            'notify_blood_match' => $this->boolFromBody($body, 'notify_blood_match', true),
        ];

        // Solo actualizar campos enviados explícitamente (excepto prefs que usan default si faltan)
        $toUpdate = [];
        foreach (['blood_type', 'birth_date', 'phone', 'province', 'canton', 'address', 'medical_history'] as $key) {
            if (array_key_exists($key, $body)) {
                $toUpdate[$key] = $profileData[$key];
            }
        }
        foreach (['notify_nearby', 'notify_appointments', 'notify_blood_match'] as $key) {
            if (array_key_exists($key, $body)) {
                $toUpdate[$key] = (int) $profileData[$key];
            }
        }

        if ($toUpdate !== []) {
            $this->profiles->update($userId, $toUpdate);
        } else {
            $this->profiles->ensureForUser($userId);
        }
    }

    private function buildPayload(int $userId): array
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new PDOException('Usuario no encontrado.');
        }

        $profile = $this->profiles->ensureForUser($userId);

        return [
            'user' => UserRepository::toPublic($user),
            'profile' => $profile,
        ];
    }

    /** @param array<string, mixed> $body */
    private function validateUpdate(array $body): ?string
    {
        if (isset($body['first_name']) && trim((string) $body['first_name']) === '') {
            return 'El nombre no puede estar vacío.';
        }
        if (isset($body['last_name']) && trim((string) $body['last_name']) === '') {
            return 'El apellido no puede estar vacío.';
        }

        if (array_key_exists('blood_type', $body)) {
            $bloodType = trim((string) $body['blood_type']);
            if (!DonorProfileRepository::isValidBloodType($bloodType === '' ? null : $bloodType)) {
                return 'Tipo de sangre inválido.';
            }
        }

        if (array_key_exists('birth_date', $body) && $body['birth_date'] !== null && $body['birth_date'] !== '') {
            $date = (string) $body['birth_date'];
            if (!$this->isValidDate($date)) {
                return 'La fecha de nacimiento no es válida.';
            }
        }

        $password = (string) ($body['password'] ?? $body['new_password'] ?? '');
        $confirm = (string) ($body['password_confirm'] ?? $body['confirm_password'] ?? '');
        if ($password !== '' || $confirm !== '') {
            if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
                return 'La contraseña debe tener al menos 8 caracteres.';
            }
            if ($confirm !== '' && $password !== $confirm) {
                return 'Las contraseñas no coinciden.';
            }
        }

        return null;
    }

    /** @param array<string, mixed> $body */
    private function boolFromBody(array $body, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $body)) {
            return $default;
        }
        $value = $body[$key];
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = trim((string) $value);

        return $this->isValidDate($date) ? $date : null;
    }

    private function isValidDate(string $date): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $dt !== false && $dt->format('Y-m-d') === $date;
    }
}
