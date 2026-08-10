<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class BankProfileRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findCenterIdByUserId(int $userId): ?int
    {
        $query = $this->pdo->prepare(
            'SELECT center_id FROM bank_profiles WHERE user_id = :user_id'
        );
        $query->execute(['user_id' => $userId]);
        $row = $query->fetch();

        return $row ? (int) $row['center_id'] : null;
    }
}
