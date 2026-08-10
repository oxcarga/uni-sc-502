<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditLogRepository;
use App\Repositories\BankProfileRepository;
use App\Repositories\BloodUnitRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\RequestRepository;
use App\Services\InventoryAlertService;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequestController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly RequestRepository $requests,
        private readonly BloodUnitRepository $bloodUnits,
        private readonly InventoryRepository $inventory,
        private readonly BankProfileRepository $bankProfiles,
        private readonly DonationCenterRepository $centers,
        private readonly InventoryAlertService $alertSync,
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
        if (!in_array($auth['role'] ?? '', ['bank', 'admin'], true)) {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        try {
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null) {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $list = $this->requests->findByCenter($centerId);

            return JsonResponse::success($response, [
                'center_id' => $centerId,
                'requests' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar solicitudes.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar solicitudes.', 500);
        }
    }

    public function assign(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }
        $role = (string) ($auth['role'] ?? '');
        if (!in_array($role, ['bank', 'admin'], true)) {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Solicitud no encontrada.', 404);
        }

        try {
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null && $role === 'bank') {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $this->pdo->beginTransaction();

            $locked = $this->requests->findByIdForUpdate($id);
            if ($locked === null) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'Solicitud no encontrada.', 404);
            }

            if ($locked['center_id'] === null) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'La solicitud no tiene centro asignado.', 422);
            }

            if ($role === 'bank' && (int) $locked['center_id'] !== $centerId) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'La solicitud no pertenece a tu centro.', 403);
            }

            $targetCenter = (int) $locked['center_id'];
            if ($locked['status'] !== 'pending') {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'Solo se pueden asignar solicitudes pendientes.', 422);
            }

            $quantity = (int) $locked['quantity'];
            $bloodType = (string) $locked['blood_type'];
            $units = $this->bloodUnits->lockAvailable($targetCenter, $bloodType, $quantity);
            if (count($units) < $quantity) {
                $this->pdo->rollBack();
                return JsonResponse::error(
                    $response,
                    "No hay suficientes unidades disponibles de {$bloodType} (se necesitan {$quantity}).",
                    422
                );
            }

            $unitIds = array_map(static fn (array $u): int => (int) $u['id'], $units);
            $this->bloodUnits->markAssigned($unitIds);

            $stock = $this->inventory->applyChange(
                $targetCenter,
                $bloodType,
                $quantity,
                -$quantity,
                'assignment',
                (int) $auth['id'],
                'Asignación a solicitud ' . ($locked['code'] ?? "#{$id}"),
                null,
                (int) $unitIds[0],
                $id
            );

            $completedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            $this->requests->markAssigned($id, $completedAt);

            $alertSync = $this->alertSync->syncForBloodType(
                $targetCenter,
                $bloodType,
                (int) $stock['inventory']['units'],
                $id
            );

            $serverParams = $request->getServerParams();
            $ip = is_string($serverParams['REMOTE_ADDR'] ?? null) ? $serverParams['REMOTE_ADDR'] : null;
            $this->audit->write(
                (int) $auth['id'],
                'request.assign',
                'request',
                $id,
                json_encode([
                    'code' => $locked['code'] ?? null,
                    'blood_type' => $bloodType,
                    'quantity' => $quantity,
                ], JSON_UNESCAPED_UNICODE),
                $ip
            );

            $this->pdo->commit();

            $fresh = $this->requests->findById($id);

            return JsonResponse::success($response, [
                'request' => $fresh,
                'assigned_units' => array_map(static fn (array $u): array => [
                    'id' => (int) $u['id'],
                    'code' => $u['code'],
                    'blood_type' => $u['blood_type'],
                    'status' => 'assigned',
                ], $units),
                'inventory' => $stock['inventory'],
                'movement_id' => $stock['movement_id'],
                'alert_sync' => $alertSync,
            ], 'Unidades asignadas a la solicitud.');
        } catch (PDOException $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $msg = $error->getMessage();
            if (str_contains($msg, 'Stock insuficiente') || str_contains($msg, 'unidades')) {
                return JsonResponse::error($response, $msg, 422);
            }
            $this->logger->error('Error al asignar solicitud.', ['error' => $msg]);
            return JsonResponse::error($response, 'Error al asignar la solicitud.', 500);
        }
    }

    /** @param array<string, mixed> $auth */
    private function resolveCenterId(array $auth, Request $request): ?int
    {
        $role = (string) ($auth['role'] ?? '');
        if ($role === 'bank') {
            return $this->bankProfiles->findCenterIdByUserId((int) $auth['id']);
        }

        if ($role === 'admin') {
            $centerId = (int) ($request->getQueryParams()['center_id'] ?? ($request->getParsedBody()['center_id'] ?? 0));
            if ($centerId > 0) {
                return $centerId;
            }
            $centers = $this->centers->findAll(activeOnly: true);

            return isset($centers[0]['id']) ? (int) $centers[0]['id'] : null;
        }

        return null;
    }
}
