<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DonationCenterRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CenterController
{
    public function __construct(
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
        $activeOnly = $role !== 'admin';

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
}
