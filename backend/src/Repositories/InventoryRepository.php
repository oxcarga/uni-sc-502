<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class InventoryRepository
{
    public const BLOOD_TYPES = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByCenter(int $centerId): array
    {
        $query = $this->pdo->prepare(
            'SELECT id, center_id, blood_type, units, updated_at
             FROM inventory
             WHERE center_id = :center_id'
        );
        $query->execute(['center_id' => $centerId]);
        $byType = [];
        foreach ($query->fetchAll() as $row) {
            $byType[$row['blood_type']] = [
                'id' => (int) $row['id'],
                'center_id' => (int) $row['center_id'],
                'blood_type' => $row['blood_type'],
                'units' => (int) $row['units'],
                'updated_at' => $row['updated_at'],
            ];
        }

        $result = [];
        foreach (self::BLOOD_TYPES as $type) {
            $result[] = $byType[$type] ?? [
                'id' => null,
                'center_id' => $centerId,
                'blood_type' => $type,
                'units' => 0,
                'updated_at' => null,
            ];
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    public function findMovementsByCenter(int $centerId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $query = $this->pdo->prepare(
            "SELECT m.id, m.center_id, m.type, m.blood_type, m.quantity,
                    m.donation_id, m.request_id, m.blood_unit_id, m.user_id,
                    m.detail, m.created_at,
                    u.first_name AS user_first_name, u.last_name AS user_last_name
             FROM inventory_movements m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.center_id = :center_id
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT {$limit}"
        );
        $query->execute(['center_id' => $centerId]);

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['center_id'] = (int) $row['center_id'];
            $row['quantity'] = (int) $row['quantity'];
            $row['donation_id'] = $row['donation_id'] !== null ? (int) $row['donation_id'] : null;
            $row['request_id'] = $row['request_id'] !== null ? (int) $row['request_id'] : null;
            $row['blood_unit_id'] = $row['blood_unit_id'] !== null ? (int) $row['blood_unit_id'] : null;
            $row['user_id'] = $row['user_id'] !== null ? (int) $row['user_id'] : null;

            return $row;
        }, $query->fetchAll());
    }

    /**
     * Aplica delta al stock y registra movimiento en la misma transacción externa o interna.
     *
     * @param 'receipt'|'assignment'|'adjustment'|'discard' $movementType
     * @return array{inventory: array<string, mixed>, movement_id: int}
     */
    public function applyChange(
        int $centerId,
        string $bloodType,
        int $quantity,
        int $signedDelta,
        string $movementType,
        ?int $userId = null,
        ?string $detail = null,
        ?int $donationId = null,
        ?int $bloodUnitId = null,
        ?int $requestId = null
    ): array {
        if ($quantity <= 0) {
            throw new PDOException('La cantidad debe ser mayor que cero.');
        }
        if (!in_array($bloodType, self::BLOOD_TYPES, true)) {
            throw new PDOException('Tipo de sangre inválido.');
        }
        if (!in_array($movementType, ['receipt', 'assignment', 'adjustment', 'discard'], true)) {
            throw new PDOException('Tipo de movimiento inválido.');
        }

        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $lock = $this->pdo->prepare(
                'SELECT id, units FROM inventory
                 WHERE center_id = :center_id AND blood_type = :blood_type
                 FOR UPDATE'
            );
            $lock->execute(['center_id' => $centerId, 'blood_type' => $bloodType]);
            $row = $lock->fetch();

            if ($row) {
                $newUnits = (int) $row['units'] + $signedDelta;
                if ($newUnits < 0) {
                    throw new PDOException('Stock insuficiente para esta operación.');
                }
                $update = $this->pdo->prepare(
                    'UPDATE inventory SET units = :units WHERE id = :id'
                );
                $update->execute(['units' => $newUnits, 'id' => (int) $row['id']]);
                $inventoryId = (int) $row['id'];
            } else {
                if ($signedDelta < 0) {
                    throw new PDOException('Stock insuficiente para esta operación.');
                }
                $insert = $this->pdo->prepare(
                    'INSERT INTO inventory (center_id, blood_type, units)
                     VALUES (:center_id, :blood_type, :units)'
                );
                $insert->execute([
                    'center_id' => $centerId,
                    'blood_type' => $bloodType,
                    'units' => $signedDelta,
                ]);
                $inventoryId = (int) $this->pdo->lastInsertId();
                $newUnits = $signedDelta;
            }

            $move = $this->pdo->prepare(
                'INSERT INTO inventory_movements
                    (center_id, type, blood_type, quantity, donation_id, request_id,
                     blood_unit_id, user_id, detail)
                 VALUES
                    (:center_id, :type, :blood_type, :quantity, :donation_id, :request_id,
                     :blood_unit_id, :user_id, :detail)'
            );
            $move->execute([
                'center_id' => $centerId,
                'type' => $movementType,
                'blood_type' => $bloodType,
                'quantity' => $quantity,
                'donation_id' => $donationId,
                'request_id' => $requestId,
                'blood_unit_id' => $bloodUnitId,
                'user_id' => $userId,
                'detail' => $detail,
            ]);
            $movementId = (int) $this->pdo->lastInsertId();

            if ($ownTransaction) {
                $this->pdo->commit();
            }

            return [
                'inventory' => [
                    'id' => $inventoryId,
                    'center_id' => $centerId,
                    'blood_type' => $bloodType,
                    'units' => $newUnits,
                ],
                'movement_id' => $movementId,
            ];
        } catch (\Throwable $error) {
            if ($ownTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
