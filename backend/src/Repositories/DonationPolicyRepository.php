<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class DonationPolicyRepository
{
    public const DEFAULT_DONOR_INTERVAL_DAYS = 56;

    /** Claves editables por admin (globales). */
    public const EDITABLE_KEYS = [
        'inventory_healthy_min',
        'inventory_moderate_min',
        'inventory_critical_max',
        'donor_interval_days',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findAllGlobal(): array
    {
        $query = $this->pdo->query(
            'SELECT id, center_id, key_name, value_text, description, active, created_at, updated_at
             FROM donation_policies
             WHERE center_id IS NULL
             ORDER BY key_name ASC'
        );

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['center_id'] = $row['center_id'] !== null ? (int) $row['center_id'] : null;
            $row['active'] = (bool) $row['active'];

            return $row;
        }, $query->fetchAll());
    }

    /**
     * @param array<string, int|string> $values key_name => value
     * @return list<array<string, mixed>>
     */
    public function upsertGlobals(array $values): array
    {
        foreach ($values as $key => $value) {
            if (!in_array($key, self::EDITABLE_KEYS, true)) {
                continue;
            }
            $text = (string) (int) $value;
            if ((int) $text <= 0) {
                continue;
            }

            $find = $this->pdo->prepare(
                'SELECT id FROM donation_policies
                 WHERE center_id IS NULL AND key_name = :key
                 LIMIT 1'
            );
            $find->execute(['key' => $key]);
            $existing = $find->fetch();

            if ($existing) {
                $update = $this->pdo->prepare(
                    'UPDATE donation_policies
                     SET value_text = :value, active = 1
                     WHERE id = :id'
                );
                $update->execute([
                    'value' => $text,
                    'id' => (int) $existing['id'],
                ]);
            } else {
                $insert = $this->pdo->prepare(
                    'INSERT INTO donation_policies (center_id, key_name, value_text, description, active)
                     VALUES (NULL, :key, :value, NULL, 1)'
                );
                $insert->execute([
                    'key' => $key,
                    'value' => $text,
                ]);
            }
        }

        return $this->findAllGlobal();
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
