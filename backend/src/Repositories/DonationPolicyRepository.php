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
}
