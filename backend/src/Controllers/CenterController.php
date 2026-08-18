<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditLogRepository;
use App\Repositories\BankProfileRepository;
use App\Repositories\DonationCenterRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CenterController
{
    private const ERROR_CODE_DUPLICATE = '23000';

    /** @var list<string> */
    private const ADMIN_WRITABLE = [
        'code', 'name', 'description', 'address', 'province', 'canton', 'region',
        'lat', 'lng', 'contact_name', 'contact_phone', 'contact_email',
        'open_time', 'close_time', 'open_days', 'daily_capacity', 'process_minutes',
        'accept_walk_ins', 'active',
    ];

    /** @var list<string> */
    private const BANK_WRITABLE = [
        'name', 'description', 'address', 'province', 'canton', 'region',
        'lat', 'lng', 'contact_name', 'contact_phone', 'contact_email',
        'open_time', 'close_time', 'open_days', 'daily_capacity', 'process_minutes',
        'accept_walk_ins',
    ];

    public function __construct(
        private readonly DonationCenterRepository $centers,
        private readonly BankProfileRepository $bankProfiles,
        private readonly AuditLogRepository $audit,
        private Logger $logger
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $role = (string) ($auth['role'] ?? '');
        $includeInactive = $role === 'admin'
            && (($request->getQueryParams()['all'] ?? '') === '1'
                || ($request->getQueryParams()['include_inactive'] ?? '') === '1');

        try {
            $list = $this->centers->findAll(activeOnly: !$includeInactive);
            return JsonResponse::success($response, $list);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar centros.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar centros.', 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Centro no encontrado.', 404);
        }

        $role = (string) ($auth['role'] ?? '');
        $ownCenterId = $role === 'bank'
            ? $this->bankProfiles->findCenterIdByUserId((int) $auth['id'])
            : null;
        $activeOnly = $role !== 'admin' && $ownCenterId !== $id;

        try {
            $center = $this->centers->findById($id, activeOnly: $activeOnly);
            if ($center === null) {
                return JsonResponse::error($response, 'Centro no encontrado.', 404);
            }

            return JsonResponse::success($response, $center);
        } catch (PDOException $error) {
            $this->logger->error('Error al consultar centro.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al consultar el centro.', 500);
        }
    }

    public function mine(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'bank') {
            return JsonResponse::error($response, 'Solo el personal del banco.', 403);
        }

        try {
            $centerId = $this->bankProfiles->findCenterIdByUserId((int) $auth['id']);
            if ($centerId === null) {
                return JsonResponse::error($response, 'No hay un centro asociado a esta cuenta.', 404);
            }

            $center = $this->centers->findById($centerId, activeOnly: false);
            if ($center === null) {
                return JsonResponse::error($response, 'Centro no encontrado.', 404);
            }

            return JsonResponse::success($response, $center);
        } catch (PDOException $error) {
            $this->logger->error('Error al consultar el centro del banco.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al consultar el centro.', 500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error($response, 'Solo administradores.', 403);
        }

        $body = (array) $request->getParsedBody();
        $parsed = $this->parseWritable($body, self::ADMIN_WRITABLE);
        if (isset($parsed['error'])) {
            return JsonResponse::error($response, (string) $parsed['error'], 422);
        }

        /** @var array<string, mixed> $fields */
        $fields = $parsed['fields'];
        $name = trim((string) ($fields['name'] ?? ''));
        $address = trim((string) ($fields['address'] ?? ''));
        if ($name === '' || $address === '') {
            return JsonResponse::error($response, 'Indica nombre y dirección del centro.', 422);
        }

        if (mb_strlen($name) > 150 || mb_strlen($address) > 255) {
            return JsonResponse::error($response, 'Nombre o dirección demasiado largos.', 422);
        }

        $fields['name'] = $name;
        $fields['address'] = $address;
        $fields['accept_walk_ins'] = $fields['accept_walk_ins'] ?? true;
        $fields['active'] = $fields['active'] ?? true;

        $code = isset($fields['code']) ? trim((string) $fields['code']) : '';
        $fields['code'] = $code !== '' ? $code : $this->centers->generateCode();

        try {
            $center = $this->centers->create($fields);
            $this->audit->write(
                (int) $auth['id'],
                'center.create',
                'center',
                (int) $center['id'],
                json_encode([
                    'name' => $center['name'],
                    'code' => $center['code'],
                    'active' => $center['active'],
                ], JSON_UNESCAPED_UNICODE),
                $this->clientIp($request)
            );

            return JsonResponse::success($response, $center, 'Centro creado.', 201);
        } catch (PDOException $error) {
            if ($this->isDuplicateCodeError($error)) {
                return JsonResponse::error($response, 'Ya existe un centro con ese código.', 409);
            }
            $this->logger->error('Error al crear centro.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al crear el centro.', 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $role = (string) ($auth['role'] ?? '');
        if ($role !== 'admin' && $role !== 'bank') {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Centro no encontrado.', 404);
        }

        try {
            $existing = $this->centers->findById($id, activeOnly: false);
            if ($existing === null) {
                return JsonResponse::error($response, 'Centro no encontrado.', 404);
            }

            if ($role === 'bank') {
                $ownCenterId = $this->bankProfiles->findCenterIdByUserId((int) $auth['id']);
                if ($ownCenterId === null || $ownCenterId !== $id) {
                    return JsonResponse::error($response, 'Solo puedes editar tu propio centro.', 403);
                }
            }

            $allowed = $role === 'admin' ? self::ADMIN_WRITABLE : self::BANK_WRITABLE;
            $body = (array) $request->getParsedBody();
            $parsed = $this->parseWritable($body, $allowed);
            if (isset($parsed['error'])) {
                return JsonResponse::error($response, (string) $parsed['error'], 422);
            }

            /** @var array<string, mixed> $patch */
            $patch = $parsed['fields'];
            if ($patch === []) {
                return JsonResponse::error($response, 'Indica al menos un campo para actualizar.', 422);
            }

            $merged = array_merge($existing, $patch);
            $name = trim((string) ($merged['name'] ?? ''));
            $address = trim((string) ($merged['address'] ?? ''));
            if ($name === '' || $address === '') {
                return JsonResponse::error($response, 'Indica nombre y dirección del centro.', 422);
            }
            if (mb_strlen($name) > 150 || mb_strlen($address) > 255) {
                return JsonResponse::error($response, 'Nombre o dirección demasiado largos.', 422);
            }

            $merged['name'] = $name;
            $merged['address'] = $address;

            if (array_key_exists('code', $patch)) {
                $code = trim((string) ($merged['code'] ?? ''));
                $merged['code'] = $code !== '' ? $code : null;
            }

            $wasActive = (bool) $existing['active'];
            $willBeActive = (bool) $merged['active'];

            $updated = $this->centers->update($id, $merged);
            if ($updated === null) {
                return JsonResponse::error($response, 'Centro no encontrado.', 404);
            }

            $actorId = (int) $auth['id'];
            $ip = $this->clientIp($request);
            $nonActivePatch = $patch;
            unset($nonActivePatch['active']);

            if ($nonActivePatch !== []) {
                $this->audit->write(
                    $actorId,
                    'center.update',
                    'center',
                    $id,
                    json_encode([
                        'name' => $updated['name'],
                        'fields' => array_keys($nonActivePatch),
                    ], JSON_UNESCAPED_UNICODE),
                    $ip
                );
            }

            if (array_key_exists('active', $patch) && $wasActive !== $willBeActive) {
                $this->audit->write(
                    $actorId,
                    $willBeActive ? 'center.activate' : 'center.deactivate',
                    'center',
                    $id,
                    json_encode([
                        'name' => $updated['name'],
                        'active' => $willBeActive,
                    ], JSON_UNESCAPED_UNICODE),
                    $ip
                );
            }

            return JsonResponse::success($response, $updated, 'Centro actualizado.');
        } catch (PDOException $error) {
            if ($this->isDuplicateCodeError($error)) {
                return JsonResponse::error($response, 'Ya existe un centro con ese código.', 409);
            }
            $this->logger->error('Error al actualizar centro.', [
                'center_id' => $id,
                'error' => $error->getMessage(),
            ]);
            return JsonResponse::error($response, 'Error al actualizar el centro.', 500);
        }
    }

    /**
     * @param array<string, mixed> $body
     * @param list<string> $allowed
     * @return array{fields?: array<string, mixed>, error?: string}
     */
    private function parseWritable(array $body, array $allowed): array
    {
        $fields = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $body)) {
                continue;
            }

            $value = $body[$key];

            if (in_array($key, ['active', 'accept_walk_ins'], true)) {
                $bool = $this->parseOptionalBool($value);
                if ($bool === null) {
                    return ['error' => "El valor de {$key} no es válido."];
                }
                $fields[$key] = $bool;
                continue;
            }

            if (in_array($key, ['lat', 'lng'], true)) {
                if ($value === null || $value === '') {
                    $fields[$key] = null;
                    continue;
                }
                if (!is_numeric($value)) {
                    return ['error' => "El valor de {$key} no es válido."];
                }
                $number = (float) $value;
                if ($key === 'lat' && ($number < -90 || $number > 90)) {
                    return ['error' => 'La latitud no es válida.'];
                }
                if ($key === 'lng' && ($number < -180 || $number > 180)) {
                    return ['error' => 'La longitud no es válida.'];
                }
                $fields[$key] = $number;
                continue;
            }

            if (in_array($key, ['daily_capacity', 'process_minutes'], true)) {
                if ($value === null || $value === '') {
                    $fields[$key] = null;
                    continue;
                }
                if (!is_numeric($value) || (int) $value < 0) {
                    return ['error' => "El valor de {$key} no es válido."];
                }
                $fields[$key] = (int) $value;
                continue;
            }

            if (in_array($key, ['open_time', 'close_time'], true)) {
                $time = $this->normalizeTime($value);
                if ($time === false) {
                    return ['error' => 'El horario no es válido.'];
                }
                $fields[$key] = $time;
                continue;
            }

            if ($key === 'contact_email') {
                $email = is_string($value) ? trim($value) : '';
                if ($email === '') {
                    $fields[$key] = null;
                    continue;
                }
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    return ['error' => 'El correo del centro no es válido.'];
                }
                $fields[$key] = $email;
                continue;
            }

            if ($key === 'code') {
                $code = is_string($value) || is_numeric($value) ? trim((string) $value) : '';
                if ($code === '') {
                    $fields[$key] = null;
                    continue;
                }
                if (strlen($code) > 20 || preg_match('/^[A-Za-z0-9_-]+$/', $code) !== 1) {
                    return ['error' => 'El código del centro no es válido.'];
                }
                $fields[$key] = $code;
                continue;
            }

            if (!is_string($value) && $value !== null) {
                if (is_numeric($value)) {
                    $value = (string) $value;
                } else {
                    return ['error' => "El valor de {$key} no es válido."];
                }
            }

            $text = $value === null ? '' : trim((string) $value);
            $fields[$key] = $text === '' ? null : $text;
        }

        return ['fields' => $fields];
    }

    private function normalizeTime(mixed $value): string|false|null
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        $raw = trim((string) $value);
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $raw, $matches) !== 1) {
            return false;
        }

        $seconds = $matches[3] ?? '00';

        return sprintf('%s:%s:%s', $matches[1], $matches[2], $seconds);
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

    private function isDuplicateCodeError(PDOException $error): bool
    {
        $sqlState = (string) ($error->errorInfo[0] ?? $error->getCode());
        $driverCode = (int) ($error->errorInfo[1] ?? 0);

        return $sqlState === self::ERROR_CODE_DUPLICATE || $driverCode === 1062;
    }

    private function clientIp(Request $request): ?string
    {
        $serverParams = $request->getServerParams();

        return is_string($serverParams['REMOTE_ADDR'] ?? null)
            ? $serverParams['REMOTE_ADDR']
            : null;
    }
}
