<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class DonationPolicyRepository
{
    public const DEFAULT_DONOR_INTERVAL_DAYS = 56;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getInt(string $key, int $default): int
    {
        $query = $this->pdo->prepare(
            'SELECT value_text FROM donation_policies
             WHERE key_name = :key AND active = 1 AND center_id IS NULL
             LIMIT 1'
        );
        $query->execute(['key' => $key]);
        $row = $query->fetch();
        if (!$row) {
            return $default;
        }

        $value = (int) $row['value_text'];

        return $value > 0 ? $value : $default;
    }

    public function donorIntervalDays(): int
    {
        return $this->getInt('donor_interval_days', self::DEFAULT_DONOR_INTERVAL_DAYS);
    }

    /**
     * Umbrales de inventario (plan: saludable >100, moderado 50–100, crítico <50).
     *
     * @return array{healthy_min: int, moderate_min: int, critical_max: int}
     */
    public function inventoryThresholds(): array
    {
        return [
            'healthy_min' => $this->getInt('inventory_healthy_min', 101),
            'moderate_min' => $this->getInt('inventory_moderate_min', 50),
            'critical_max' => $this->getInt('inventory_critical_max', 49),
        ];
    }

    /** @return 'healthy'|'moderate'|'critical' */
    public function inventoryLevel(int $units, ?array $thresholds = null): string
    {
        $t = $thresholds ?? $this->inventoryThresholds();
        if ($units <= (int) $t['critical_max']) {
            return 'critical';
        }
        if ($units < (int) $t['healthy_min']) {
            return 'moderate';
        }

        return 'healthy';
    }
}
