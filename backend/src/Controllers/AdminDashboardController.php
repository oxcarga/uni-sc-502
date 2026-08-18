<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AlertRepository;
use App\Repositories\RequestRepository;
use App\Repositories\UserRepository;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminDashboardController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AlertRepository $alerts,
        private readonly RequestRepository $requests
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

        $users = $this->users->findAll();

        $banks = count(array_filter(
            $users,
            static fn (array $user): bool => ($user['role'] ?? '') === 'bank'
        ));

        $donors = count(array_filter(
            $users,
            static fn (array $user): bool => ($user['role'] ?? '') === 'donor'
        ));

        return JsonResponse::success($response, [
            'banks' => $banks,
            'donors' => $donors,
            'active_alerts' => $this->alerts->countActive(),
            'pending_requests' => $this->requests->countPending(),
        ]);
    }
}