<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BankProfileRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\DonorProfileRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BankDonorController
{
    public function __construct(
        private readonly DonorProfileRepository $profiles,
        private readonly BankProfileRepository $bankProfiles,
        private readonly DonationCenterRepository $centers,
        private Logger $logger
    ) {
    }

    public function compatible(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }
        if (!in_array($auth['role'] ?? '', ['bank', 'admin'], true)) {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        $params = $request->getQueryParams();
        $bloodType = trim((string) ($params['blood_type'] ?? ''));
        if (!DonorProfileRepository::isValidBloodType($bloodType) || $bloodType === '') {
            return JsonResponse::error($response, 'Parámetro blood_type inválido.', 422);
        }

        $eligibleOnly = !isset($params['eligible']) || $params['eligible'] !== '0';
        $limit = (int) ($params['limit'] ?? 50);

        try {
            // Valida que el banco tenga centro (contexto operativo).
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null) {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $donors = $this->profiles->findCompatible($bloodType, $eligibleOnly, $limit);

            return JsonResponse::success($response, [
                'center_id' => $centerId,
                'blood_type' => $bloodType,
                'eligible_only' => $eligibleOnly,
                'donors' => $donors,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar donantes compatibles.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar donantes compatibles.', 500);
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
            $centerId = (int) ($request->getQueryParams()['center_id'] ?? 0);
            if ($centerId > 0) {
                return $centerId;
            }
            $centers = $this->centers->findAll(activeOnly: true);

            return isset($centers[0]['id']) ? (int) $centers[0]['id'] : null;
        }

        return null;
    }
}
