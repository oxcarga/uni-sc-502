<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BankProfileRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\DonationPolicyRepository;
use App\Repositories\InventoryRepository;
use App\Services\InventoryAlertService;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InventoryController
{
    public function __construct(
        private readonly InventoryRepository $inventory,
        private readonly BankProfileRepository $bankProfiles,
        private readonly DonationCenterRepository $centers,
        private readonly DonationPolicyRepository $policies,
        private readonly InventoryAlertService $alertSync,
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

            $thresholds = $this->policies->inventoryThresholds();
            $items = [];
            foreach ($this->inventory->findByCenter($centerId) as $row) {
                $units = (int) $row['units'];
                $level = $this->policies->inventoryLevel($units, $thresholds);
                $items[] = [
                    ...$row,
                    'level' => $level,
                ];
            }

            return JsonResponse::success($response, [
                'center_id' => $centerId,
                'thresholds' => $thresholds,
                'items' => $items,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar inventario.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar inventario.', 500);
        }
    }

    public function movements(Request $request, Response $response): Response
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

            $limit = (int) ($request->getQueryParams()['limit'] ?? 50);
            $list = $this->inventory->findMovementsByCenter($centerId, $limit);

            return JsonResponse::success($response, [
                'center_id' => $centerId,
                'movements' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar movimientos.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar movimientos.', 500);
        }
    }

    public function receipt(Request $request, Response $response): Response
    {
        return $this->mutate($request, $response, 'receipt');
    }

    public function adjustment(Request $request, Response $response): Response
    {
        return $this->mutate($request, $response, 'adjustment');
    }

    private function mutate(Request $request, Response $response, string $kind): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }
        if (!in_array($auth['role'] ?? '', ['bank', 'admin'], true)) {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        $body = (array) $request->getParsedBody();
        $bloodType = trim((string) ($body['blood_type'] ?? ''));
        $quantity = (int) ($body['quantity'] ?? 0);
        $detail = trim((string) ($body['detail'] ?? ''));
        $detail = $detail === '' ? null : $detail;

        if (!in_array($bloodType, InventoryRepository::BLOOD_TYPES, true)) {
            return JsonResponse::error($response, 'Tipo de sangre inválido.', 422);
        }
        if ($quantity <= 0) {
            return JsonResponse::error($response, 'La cantidad debe ser mayor que cero.', 422);
        }

        $movementType = 'receipt';
        $signedDelta = $quantity;

        if ($kind === 'adjustment') {
            $mode = trim((string) ($body['mode'] ?? 'add'));
            if (!in_array($mode, ['add', 'subtract', 'discard'], true)) {
                return JsonResponse::error($response, 'mode debe ser add, subtract o discard.', 422);
            }
            if ($mode === 'add') {
                $movementType = 'adjustment';
                $signedDelta = $quantity;
            } elseif ($mode === 'subtract') {
                $movementType = 'adjustment';
                $signedDelta = -$quantity;
            } else {
                $movementType = 'discard';
                $signedDelta = -$quantity;
            }
        }

        try {
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null) {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $result = $this->inventory->applyChange(
                $centerId,
                $bloodType,
                $quantity,
                $signedDelta,
                $movementType,
                (int) $auth['id'],
                $detail
            );

            $thresholds = $this->policies->inventoryThresholds();
            $units = (int) $result['inventory']['units'];
            $alertSync = $this->alertSync->syncForBloodType($centerId, $bloodType, $units);
            $payload = [
                ...$result['inventory'],
                'level' => $this->policies->inventoryLevel($units, $thresholds),
                'movement_id' => $result['movement_id'],
                'alert_sync' => $alertSync,
            ];

            $message = $kind === 'receipt' ? 'Recepción registrada.' : 'Ajuste registrado.';

            return JsonResponse::success($response, $payload, $message, 201);
        } catch (PDOException $error) {
            $msg = $error->getMessage();
            if (str_contains($msg, 'Stock insuficiente') || str_contains($msg, 'cantidad')) {
                return JsonResponse::error($response, $msg, 422);
            }
            $this->logger->error('Error al actualizar inventario.', ['error' => $msg]);
            return JsonResponse::error($response, 'Error al actualizar inventario.', 500);
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
