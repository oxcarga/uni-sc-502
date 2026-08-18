<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class AlertRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByCenter(int $centerId, ?string $status = null): array
    {
        $sql = 'SELECT a.id, a.center_id, a.request_id, a.blood_type, a.priority,
                       a.status, a.message, a.resolved_at, a.created_at, a.updated_at,
                       r.code AS request_code
                FROM alerts a
                LEFT JOIN requests r ON r.id = a.request_id
                WHERE a.center_id = :center_id';
        $params = ['center_id' => $centerId];
        if ($status !== null && $status !== '') {
            $sql .= ' AND a.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY FIELD(a.status, \'active\', \'resolved\'), a.created_at DESC, a.id DESC';

        $query = $this->pdo->prepare($sql);
        $query->execute($params);

        return array_map([$this, 'normalize'], $query->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, center_id, request_id, blood_type, priority, status,
                    message, resolved_at, created_at, updated_at
             FROM alerts WHERE id = :id'
        );
        $query->execute(['id' => $id]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function findActiveByCenterBloodType(int $centerId, string $bloodType): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, center_id, request_id, blood_type, priority, status,
                    message, resolved_at, created_at, updated_at
             FROM alerts
             WHERE center_id = :center_id AND blood_type = :blood_type AND status = \'active\'
             ORDER BY id DESC
             LIMIT 1'
        );
        $query->execute([
            'center_id' => $centerId,
            'blood_type' => $bloodType,
        ]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function countActive(): int
    {
    $query = $this->pdo->query(
        "SELECT COUNT(*) FROM alerts WHERE status = 'active'"
    );

    return (int) $query->fetchColumn();
    }

    public function create(
        int $centerId,
        string $bloodType,
        string $message,
        string $priority = 'critical',
        ?int $requestId = null
    ): array {
        $query = $this->pdo->prepare(
            'INSERT INTO alerts (center_id, request_id, blood_type, priority, status, message)
             VALUES (:center_id, :request_id, :blood_type, :priority, \'active\', :message)'
        );
        $query->execute([
            'center_id' => $centerId,
            'request_id' => $requestId,
            'blood_type' => $bloodType,
            'priority' => $priority,
            'message' => $message,
        ]);
        $created = $this->findById((int) $this->pdo->lastInsertId());
        if ($created === null) {
            throw new PDOException('No se pudo crear la alerta.');
        }

        return $created;
    }

    public function resolve(int $id): array
    {
        $query = $this->pdo->prepare(
            'UPDATE alerts
             SET status = \'resolved\', resolved_at = NOW()
             WHERE id = :id AND status = \'active\''
        );
        $query->execute(['id' => $id]);
        $updated = $this->findById($id);
        if ($updated === null) {
            throw new PDOException('Alerta no encontrada.');
        }

        return $updated;
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['center_id'] = (int) $row['center_id'];
        $row['request_id'] = $row['request_id'] !== null ? (int) $row['request_id'] : null;

        return $row;
    }
}
