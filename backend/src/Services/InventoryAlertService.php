<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertRepository;
use App\Repositories\DonationPolicyRepository;

/**
 * Crea/resuelve alertas de stock crítico de forma síncrona tras cambios de inventario.
 */
class InventoryAlertService
{
    public function __construct(
        private readonly AlertRepository $alerts,
        private readonly DonationPolicyRepository $policies
    ) {
    }

    /**
     * @return array{action: 'created'|'resolved'|'none', alert: ?array<string, mixed>}
     */
    public function syncForBloodType(
        int $centerId,
        string $bloodType,
        int $units,
        ?int $requestId = null
    ): array {
        $thresholds = $this->policies->inventoryThresholds();
        $level = $this->policies->inventoryLevel($units, $thresholds);
        $active = $this->alerts->findActiveByCenterBloodType($centerId, $bloodType);

        if ($level === 'critical') {
            if ($active !== null) {
                return ['action' => 'none', 'alert' => $active];
            }
            $alert = $this->alerts->create(
                $centerId,
                $bloodType,
                "Stock crítico de {$bloodType} ({$units} unidades)",
                'critical',
                $requestId
            );

            return ['action' => 'created', 'alert' => $alert];
        }

        if ($active !== null) {
            $resolved = $this->alerts->resolve((int) $active['id']);

            return ['action' => 'resolved', 'alert' => $resolved];
        }

        return ['action' => 'none', 'alert' => null];
    }
}
