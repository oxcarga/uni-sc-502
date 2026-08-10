<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class AppointmentRepository
{
    private const SELECT = 'a.id, a.code, a.donor_id, a.center_id, a.scheduled_at, a.status, a.notes,
        a.created_at, a.updated_at,
        c.name AS center_name, c.code AS center_code, c.address AS center_address,
        u.first_name AS donor_first_name, u.last_name AS donor_last_name, u.email AS donor_email,
        dp.blood_type AS donor_blood_type';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByDonor(int $donorId): array
    {
        $sql = 'SELECT ' . self::SELECT . '
            FROM appointments a
            JOIN donation_centers c ON c.id = a.center_id
            JOIN users u ON u.id = a.donor_id
            LEFT JOIN donor_profiles dp ON dp.user_id = a.donor_id
            WHERE a.donor_id = :donor_id
            ORDER BY a.scheduled_at DESC';

        $query = $this->pdo->prepare($sql);
        $query->execute(['donor_id' => $donorId]);

        return array_map(fn (array $row): array => $this->normalize($row), $query->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function findByCenter(int $centerId): array
    {
        $sql = 'SELECT ' . self::SELECT . '
            FROM appointments a
            JOIN donation_centers c ON c.id = a.center_id
            JOIN users u ON u.id = a.donor_id
            LEFT JOIN donor_profiles dp ON dp.user_id = a.donor_id
            WHERE a.center_id = :center_id
            ORDER BY a.scheduled_at ASC';

        $query = $this->pdo->prepare($sql);
        $query->execute(['center_id' => $centerId]);

        return array_map(fn (array $row): array => $this->normalize($row), $query->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT ' . self::SELECT . '
            FROM appointments a
            JOIN donation_centers c ON c.id = a.center_id
            JOIN users u ON u.id = a.donor_id
            LEFT JOIN donor_profiles dp ON dp.user_id = a.donor_id
            WHERE a.id = :id';

        $query = $this->pdo->prepare($sql);
        $query->execute(['id' => $id]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, code, donor_id, center_id, scheduled_at, status, notes
             FROM appointments WHERE id = :id FOR UPDATE'
        );
        $query->execute(['id' => $id]);
        $row = $query->fetch();
        if (!$row) {
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['donor_id'] = (int) $row['donor_id'];
        $row['center_id'] = (int) $row['center_id'];

        return $row;
    }

    public function countForCenterOnDate(int $centerId, string $dateYmd): int
    {
        $query = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM appointments
             WHERE center_id = :center_id
               AND DATE(scheduled_at) = :day
               AND status IN ('pending', 'confirmed')"
        );
        $query->execute(['center_id' => $centerId, 'day' => $dateYmd]);
        $row = $query->fetch();

        return (int) ($row['total'] ?? 0);
    }

    public function hasOpenFutureForDonor(int $donorId): bool
    {
        $query = $this->pdo->prepare(
            "SELECT id FROM appointments
             WHERE donor_id = :donor_id
               AND status IN ('pending', 'confirmed')
               AND scheduled_at >= NOW()
             LIMIT 1"
        );
        $query->execute(['donor_id' => $donorId]);

        return (bool) $query->fetch();
    }

    public function create(int $donorId, int $centerId, string $scheduledAt, ?string $notes): array
    {
        $code = $this->generateCode();
        $query = $this->pdo->prepare(
            'INSERT INTO appointments (code, donor_id, center_id, scheduled_at, status, notes)
             VALUES (:code, :donor_id, :center_id, :scheduled_at, :status, :notes)'
        );
        $query->execute([
            'code' => $code,
            'donor_id' => $donorId,
            'center_id' => $centerId,
            'scheduled_at' => $scheduledAt,
            'status' => 'confirmed',
            'notes' => $notes,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $created = $this->findById($id);
        if ($created === null) {
            throw new PDOException('No se pudo crear la cita.');
        }

        return $created;
    }

    public function updateStatus(int $id, string $status): ?array
    {
        $query = $this->pdo->prepare(
            'UPDATE appointments SET status = :status WHERE id = :id'
        );
        $query->execute(['status' => $status, 'id' => $id]);

        return $this->findById($id);
    }

    private function generateCode(): string
    {
        return 'CT-' . strtoupper(bin2hex(random_bytes(3)));
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['donor_id'] = (int) $row['donor_id'];
        $row['center_id'] = (int) $row['center_id'];

        return $row;
    }
}
