<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditLogRepository;
use App\Repositories\DonationPolicyRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminPolicyController
{
    public function __construct(
        private readonly DonationPolicyRepository $policies,
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
            $list = $this->policies->findAllGlobal();
            $thresholds = $this->policies->inventoryThresholds();

            return JsonResponse::success($response, [
                'policies' => $list,
                'thresholds' => $thresholds,
                'donor_interval_days' => $this->policies->donorIntervalDays(),
                'editable_keys' => DonationPolicyRepository::EDITABLE_KEYS,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar políticas.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar políticas.', 500);
        }
    }

    public function update(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error($response, 'Solo administradores.', 403);
        }

        $body = (array) $request->getParsedBody();
        $values = [];
        foreach (DonationPolicyRepository::EDITABLE_KEYS as $key) {
            if (!array_key_exists($key, $body)) {
                continue;
            }
            $int = (int) $body[$key];
            if ($int <= 0) {
                return JsonResponse::error($response, "Valor inválido para {$key}.", 422);
            }
            $values[$key] = $int;
        }

        if ($values === []) {
            return JsonResponse::error($response, 'No hay políticas para actualizar.', 422);
        }

        $healthy = $values['inventory_healthy_min'] ?? $this->policies->getInt('inventory_healthy_min', 101);
        $moderate = $values['inventory_moderate_min'] ?? $this->policies->getInt('inventory_moderate_min', 50);
        $critical = $values['inventory_critical_max'] ?? $this->policies->getInt('inventory_critical_max', 49);

        if ($critical >= $moderate || $moderate >= $healthy) {
            return JsonResponse::error(
                $response,
                'Debe cumplirse: critical_max < moderate_min < healthy_min.',
                422
            );
        }

        try {
            $list = $this->policies->upsertGlobals($values);
            $serverParams = $request->getServerParams();
            $ip = is_string($serverParams['REMOTE_ADDR'] ?? null) ? $serverParams['REMOTE_ADDR'] : null;
            $this->audit->write(
                (int) $auth['id'],
                'policy.update',
                'donation_policies',
                null,
                json_encode($values, JSON_UNESCAPED_UNICODE),
                $ip
            );

            return JsonResponse::success($response, [
                'policies' => $list,
                'thresholds' => $this->policies->inventoryThresholds(),
                'donor_interval_days' => $this->policies->donorIntervalDays(),
            ], 'Políticas actualizadas.');
        } catch (PDOException $error) {
            $this->logger->error('Error al actualizar políticas.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al actualizar políticas.', 500);
        }
    }
}
