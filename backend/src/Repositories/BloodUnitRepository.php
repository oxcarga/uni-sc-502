<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class BloodUnitRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function countAvailable(int $centerId, string $bloodType): int
    {
        $query = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM blood_units
             WHERE center_id = :center_id AND blood_type = :blood_type AND status = \'available\''
        );
        $query->execute([
            'center_id' => $centerId,
            'blood_type' => $bloodType,
        ]);

        return (int) ($query->fetch()['total'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lockAvailable(int $centerId, string $bloodType, int $limit): array
    {
        $limit = max(1, $limit);
        $query = $this->pdo->prepare(
            "SELECT id, code, donation_id, center_id, blood_type, status, collected_at, expires_at
             FROM blood_units
             WHERE center_id = :center_id AND blood_type = :blood_type AND status = 'available'
             ORDER BY expires_at ASC, id ASC
             LIMIT {$limit}
             FOR UPDATE"
        );
        $query->execute([
            'center_id' => $centerId,
            'blood_type' => $bloodType,
        ]);

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['donation_id'] = (int) $row['donation_id'];
            $row['center_id'] = (int) $row['center_id'];

            return $row;
        }, $query->fetchAll());
    }

    /** @param list<int> $ids */
    public function markAssigned(array $ids): void
    {
        if ($ids === []) {
            throw new PDOException('No hay unidades para asignar.');
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $query = $this->pdo->prepare(
            "UPDATE blood_units SET status = 'assigned' WHERE id IN ({$placeholders}) AND status = 'available'"
        );
        $query->execute(array_values($ids));
        if ($query->rowCount() !== count($ids)) {
            throw new PDOException('No se pudieron asignar todas las unidades.');
        }
    }
}
