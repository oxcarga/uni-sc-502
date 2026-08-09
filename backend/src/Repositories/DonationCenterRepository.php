<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class DonationCenterRepository
{
    private const SELECT = 'id, code, name, description, address, province, canton, region,
        lat, lng, contact_name, contact_phone, contact_email,
        open_time, close_time, open_days, daily_capacity, process_minutes,
        accept_walk_ins, active, created_at, updated_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findAll(bool $activeOnly = true): array
    {
        $sql = 'SELECT ' . self::SELECT . ' FROM donation_centers';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY name ASC';

        $query = $this->pdo->query($sql);
        $rows = $query->fetchAll();

        return array_map(fn (array $row): array => $this->normalize($row), $rows);
    }

    public function findById(int $id, bool $activeOnly = true): ?array
    {
        $sql = 'SELECT ' . self::SELECT . ' FROM donation_centers WHERE id = :id';
        if ($activeOnly) {
            $sql .= ' AND active = 1';
        }

        $query = $this->pdo->prepare($sql);
        $query->execute(['id' => $id]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['accept_walk_ins'] = (bool) $row['accept_walk_ins'];
        $row['active'] = (bool) $row['active'];
        if ($row['lat'] !== null) {
            $row['lat'] = (float) $row['lat'];
        }
        if ($row['lng'] !== null) {
            $row['lng'] = (float) $row['lng'];
        }
        if ($row['daily_capacity'] !== null) {
            $row['daily_capacity'] = (int) $row['daily_capacity'];
        }
        if ($row['process_minutes'] !== null) {
            $row['process_minutes'] = (int) $row['process_minutes'];
        }

        return $row;
    }
}
