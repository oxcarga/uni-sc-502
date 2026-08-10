<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DonorProfileRepository;
use App\Repositories\NotificationRepository;

/**
 * Emite notificaciones in-app a usuarios relevantes.
 */
class NotificationDispatchService
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly DonorProfileRepository $profiles
    ) {
    }

    /**
     * Alerta crítica nueva → donantes compatibles con notify_blood_match.
     *
     * @return int cantidad creada
     */
    public function notifyCompatibleDonorsOfShortage(string $bloodType, int $alertId, int $units): int
    {
        $donors = $this->profiles->findCompatible($bloodType, eligibleOnly: true, limit: 100);
        $created = 0;
        $title = "Se necesita sangre {$bloodType}";
        $body = "Stock critico en un centro afiliado ({$units} unidades). Si puedes donar, agenda una cita.";

        foreach ($donors as $donor) {
            if (empty($donor['notify_blood_match'])) {
                continue;
            }
            $this->notifications->create(
                (int) $donor['user_id'],
                'shortage_alert',
                $title,
                $body,
                'alert',
                $alertId
            );
            $created++;
        }

        return $created;
    }
}
