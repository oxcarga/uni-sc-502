<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class RequestRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByCenter(int $centerId): array
    {
        $query = $this->pdo->prepare(
            'SELECT r.id, r.code, r.institution_id, r.center_id, r.blood_type, r.quantity,
                    r.priority, r.status, r.notes, r.requested_at, r.completed_at,
                    r.created_at, r.updated_at,
                    i.name AS institution_name, i.contact_name AS institution_contact_name,
                    i.contact_phone AS institution_contact_phone
             FROM requests r
             JOIN medical_institutions i ON i.id = r.institution_id
             WHERE r.center_id = :center_id
             ORDER BY
               FIELD(r.status, \'pending\', \'assigned\', \'in_transit\', \'completed\', \'cancelled\'),
               FIELD(r.priority, \'critical\', \'normal\', \'low\'),
               r.requested_at DESC, r.id DESC'
        );
        $query->execute(['center_id' => $centerId]);

        return array_map([$this, 'normalize'], $query->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT r.id, r.code, r.institution_id, r.center_id, r.blood_type, r.quantity,
                    r.priority, r.status, r.notes, r.requested_at, r.completed_at,
                    r.created_at, r.updated_at,
                    i.name AS institution_name, i.contact_name AS institution_contact_name,
                    i.contact_phone AS institution_contact_phone
             FROM requests r
             JOIN medical_institutions i ON i.id = r.institution_id
             WHERE r.id = :id'
        );
        $query->execute(['id' => $id]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, code, institution_id, center_id, blood_type, quantity,
                    priority, status, notes, requested_at, completed_at
             FROM requests
             WHERE id = :id
             FOR UPDATE'
        );
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['institution_id'] = (int) $row['institution_id'];
        $row['center_id'] = $row['center_id'] !== null ? (int) $row['center_id'] : null;
        $row['quantity'] = (int) $row['quantity'];

        return $row;
    }

    public function countPending(): int
    {
    $query = $this->pdo->query(
        "SELECT COUNT(*) FROM requests WHERE status = 'pending'"
    );

    return (int) $query->fetchColumn();
    }

    public function markAssigned(int $id, ?string $completedAt = null): void
    {
        $query = $this->pdo->prepare(
            'UPDATE requests
             SET status = :status, completed_at = :completed_at
             WHERE id = :id'
        );
        $query->execute([
            'status' => 'assigned',
            'completed_at' => $completedAt,
            'id' => $id,
        ]);
        if ($query->rowCount() === 0) {
            throw new PDOException('No se pudo actualizar la solicitud.');
        }
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['institution_id'] = (int) $row['institution_id'];
        $row['center_id'] = $row['center_id'] !== null ? (int) $row['center_id'] : null;
        $row['quantity'] = (int) $row['quantity'];

        return $row;
    }
}
