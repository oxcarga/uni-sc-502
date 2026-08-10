<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AchievementService;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AchievementController
{
    public function __construct(
        private readonly AchievementService $achievements,
        private Logger $logger
    ) {
    }

    public function indexDonor(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden ver sus logros.', 403);
        }

        try {
            $status = $this->achievements->statusForDonor((int) $auth['id']);

            return JsonResponse::success($response, $status);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar logros.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar logros.', 500);
        }
    }
}
