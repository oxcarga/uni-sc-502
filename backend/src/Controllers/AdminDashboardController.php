<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AlertRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\RequestRepository;
use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminDashboardController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly DonationCenterRepository $centers,
        private readonly AlertRepository $alerts,
        private readonly RequestRepository $requests,
        private Logger $logger
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');

        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        if (($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        try {
            return JsonResponse::success($response, [
                'banks' => $this->centers->countAll(),
                'donors' => $this->users->countByRole('donor'),
                'active_alerts' => $this->alerts->countActive(),
                'pending_requests' => $this->requests->countPending(),
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al consultar dashboard admin.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al consultar el resumen.', 500);
        }
    }
}
