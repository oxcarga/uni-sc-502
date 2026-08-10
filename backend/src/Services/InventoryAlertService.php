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
        private readonly DonationPolicyRepository $policies,
        private readonly NotificationDispatchService $notifications
    ) {
    }

    /**
     * @return array{action: 'created'|'resolved'|'none', alert: ?array<string, mixed>, notifications_sent: int}
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
                return ['action' => 'none', 'alert' => $active, 'notifications_sent' => 0];
            }
            $alert = $this->alerts->create(
                $centerId,
                $bloodType,
                "Stock critico de {$bloodType} ({$units} unidades)",
                'critical',
                $requestId
            );
            $sent = $this->notifications->notifyCompatibleDonorsOfShortage(
                $bloodType,
                (int) $alert['id'],
                $units
            );

            return ['action' => 'created', 'alert' => $alert, 'notifications_sent' => $sent];
        }

        if ($active !== null) {
            $resolved = $this->alerts->resolve((int) $active['id']);

            return ['action' => 'resolved', 'alert' => $resolved, 'notifications_sent' => 0];
        }

        return ['action' => 'none', 'alert' => null, 'notifications_sent' => 0];
    }
}
