<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AlertRepository;
use App\Repositories\BankProfileRepository;
use App\Repositories\DonationCenterRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AlertController
{
    public function __construct(
        private readonly AlertRepository $alerts,
        private readonly BankProfileRepository $bankProfiles,
        private readonly DonationCenterRepository $centers,
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

            $status = trim((string) ($request->getQueryParams()['status'] ?? ''));
            $statusFilter = in_array($status, ['active', 'resolved'], true) ? $status : null;
            $list = $this->alerts->findByCenter($centerId, $statusFilter);

            return JsonResponse::success($response, [
                'center_id' => $centerId,
                'alerts' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar alertas.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar alertas.', 500);
        }
    }

    public function resolve(Request $request, Response $response, array $args): Response
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
            return JsonResponse::error($response, 'Alerta no encontrada.', 404);
        }

        try {
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null && $role === 'bank') {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $alert = $this->alerts->findById($id);
            if ($alert === null) {
                return JsonResponse::error($response, 'Alerta no encontrada.', 404);
            }
            if ($role === 'bank' && (int) $alert['center_id'] !== $centerId) {
                return JsonResponse::error($response, 'La alerta no pertenece a tu centro.', 403);
            }
            if ($alert['status'] !== 'active') {
                return JsonResponse::error($response, 'La alerta ya está resuelta.', 422);
            }

            $updated = $this->alerts->resolve($id);

            return JsonResponse::success($response, $updated, 'Alerta resuelta.');
        } catch (PDOException $error) {
            $this->logger->error('Error al resolver alerta.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al resolver la alerta.', 500);
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
