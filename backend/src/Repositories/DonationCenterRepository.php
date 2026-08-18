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

        return array_map(
            fn (array $row): array => $this->normalize($row),
            $rows
        );
    }

    public function findById(int $id, bool $activeOnly = true): ?array
    {
        $sql = 'SELECT ' . self::SELECT . ' FROM donation_centers WHERE id = :id';

        if ($activeOnly) {
            $sql .= ' AND active = 1';
        }

        $query = $this->pdo->prepare($sql);
        $query->execute([
            'id' => $id,
        ]);

        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): array
    {
        $query = $this->pdo->prepare(
            'INSERT INTO donation_centers (
                code,
                name,
                description,
                address,
                province,
                canton,
                region,
                contact_name,
                contact_phone,
                contact_email,
                active
            ) VALUES (
                :code,
                :name,
                :description,
                :address,
                :province,
                :canton,
                :region,
                :contact_name,
                :contact_phone,
                :contact_email,
                :active
            )'
        );

        $query->execute([
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'],
            'province' => $data['province'] ?? null,
            'canton' => $data['canton'] ?? null,
            'region' => $data['region'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'active' => !isset($data['active']) || $data['active'] ? 1 : 0,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return $this->findById($id, activeOnly: false) ?? [];
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?array
    {
        $query = $this->pdo->prepare(
            'UPDATE donation_centers
             SET
                code = :code,
                name = :name,
                description = :description,
                address = :address,
                province = :province,
                canton = :canton,
                region = :region,
                contact_name = :contact_name,
                contact_phone = :contact_phone,
                contact_email = :contact_email,
                active = :active
             WHERE id = :id'
        );

        $query->execute([
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'],
            'province' => $data['province'] ?? null,
            'canton' => $data['canton'] ?? null,
            'region' => $data['region'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'active' => !empty($data['active']) ? 1 : 0,
            'id' => $id,
        ]);

        return $this->findById($id, activeOnly: false);
    }

    public function updateActive(int $id, bool $active): ?array
    {
        $query = $this->pdo->prepare(
            'UPDATE donation_centers
             SET active = :active
             WHERE id = :id'
        );

        $query->execute([
            'active' => $active ? 1 : 0,
            'id' => $id,
        ]);

        return $this->findById($id, activeOnly: false);
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