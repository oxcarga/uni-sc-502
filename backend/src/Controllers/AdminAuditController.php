<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditLogRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminAuditController
{
    public function __construct(
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

        try {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 100);
            $list = $this->audit->findRecent($limit);

            return JsonResponse::success($response, [
                'entries' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar auditoría.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar auditoría.', 500);
        }
    }
}
