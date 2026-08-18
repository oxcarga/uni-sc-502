<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminUserController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogRepository $audit,
        private Logger $logger
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error($response, 'Solo administradores.', 403);
        }

        $params = $request->getQueryParams();
        $role = trim((string) ($params['role'] ?? ''));
        if ($role !== '' && !UserRepository::isValidRole($role)) {
            return JsonResponse::error($response, 'El rol no es válido.', 422);
        }

        $filters = [];
        if ($role !== '') {
            $filters['role'] = $role;
        }

        $active = $this->parseOptionalBool($params['active'] ?? null);
        if ($active !== null) {
            $filters['active'] = $active;
        }

        $search = trim((string) ($params['q'] ?? ''));
        if ($search !== '') {
            $filters['q'] = $search;
        }

        try {
            $list = $this->users->findForAdmin($filters);

            return JsonResponse::success($response, [
                'users' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar usuarios admin.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar usuarios.', 500);
        }
    }

    public function patch(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error($response, 'Solo administradores.', 403);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Usuario no encontrado.', 404);
        }

        $target = $this->users->findById($id);
        if ($target === null) {
            return JsonResponse::error($response, 'Usuario no encontrado.', 404);
        }

        $body = (array) $request->getParsedBody();
        $hasActive = array_key_exists('active', $body);
        $hasRole = array_key_exists('role', $body);

        if (!$hasActive && !$hasRole) {
            return JsonResponse::error($response, 'Indica active y/o role.', 422);
        }

        $newActive = $hasActive ? $this->parseOptionalBool($body['active']) : null;
        if ($hasActive && $newActive === null) {
            return JsonResponse::error($response, 'El valor de active no es válido.', 422);
        }

        $newRole = null;
        if ($hasRole) {
            $newRole = trim((string) $body['role']);
            if (!UserRepository::isValidRole($newRole)) {
                return JsonResponse::error($response, 'El rol no es válido.', 422);
            }
        }

        $currentActive = (bool) $target['active'];
        $currentRole = (string) $target['role'];
        $willBeActive = $newActive ?? $currentActive;
        $willBeRole = $newRole ?? $currentRole;

        if ($this->wouldLeaveNoActiveAdmin($currentActive, $currentRole, $willBeActive, $willBeRole)) {
            return JsonResponse::error(
                $response,
                'Debe quedar al menos un administrador activo.',
                422
            );
        }

        $payload = [
            'first_name' => (string) $target['first_name'],
            'last_name' => (string) $target['last_name'],
            'email' => (string) $target['email'],
        ];
        if ($hasActive) {
            $payload['active'] = $willBeActive;
        }
        if ($hasRole) {
            $payload['role'] = $willBeRole;
        }

        try {
            $updated = $this->users->update($id, $payload);
            if ($updated === null) {
                return JsonResponse::error($response, 'Usuario no encontrado.', 404);
            }

            $ip = $this->clientIp($request);
            $actorId = (int) $auth['id'];

            if ($hasActive && $newActive !== $currentActive) {
                $this->audit->write(
                    $actorId,
                    $newActive ? 'user.activate' : 'user.deactivate',
                    'user',
                    $id,
                    json_encode([
                        'email' => $target['email'],
                        'active' => $newActive,
                    ], JSON_UNESCAPED_UNICODE),
                    $ip
                );
            }

            if ($hasRole && $newRole !== $currentRole) {
                $this->audit->write(
                    $actorId,
                    'user.role_change',
                    'user',
                    $id,
                    json_encode([
                        'email' => $target['email'],
                        'from' => $currentRole,
                        'to' => $newRole,
                    ], JSON_UNESCAPED_UNICODE),
                    $ip
                );
            }

            return JsonResponse::success(
                $response,
                $this->users->findAdminById($id) ?? UserRepository::toAdminListItem($updated),
                'Usuario actualizado.'
            );
        } catch (PDOException $error) {
            $this->logger->error('Error al actualizar usuario admin.', [
                'user_id' => $id,
                'error' => $error->getMessage(),
            ]);
            return JsonResponse::error($response, 'Error al actualizar el usuario.', 500);
        }
    }

    private function wouldLeaveNoActiveAdmin(
        bool $currentActive,
        string $currentRole,
        bool $willBeActive,
        string $willBeRole
    ): bool {
        $isActiveAdmin = $currentActive && $currentRole === 'admin';
        $willBeActiveAdmin = $willBeActive && $willBeRole === 'admin';

        if (!$isActiveAdmin || $willBeActiveAdmin) {
            return false;
        }

        return $this->users->countActiveAdmins() <= 1;
    }

    private function parseOptionalBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }
        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return null;
    }

    private function clientIp(Request $request): ?string
    {
        $serverParams = $request->getServerParams();

        return is_string($serverParams['REMOTE_ADDR'] ?? null)
            ? $serverParams['REMOTE_ADDR']
            : null;
    }
}
