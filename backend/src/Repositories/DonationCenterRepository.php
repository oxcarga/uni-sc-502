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

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM donation_centers')->fetchColumn();
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

    public function findByCode(string $code): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT . ' FROM donation_centers WHERE code = :code'
        );
        $query->execute(['code' => $code]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function generateCode(): string
    {
        $n = (int) $this->pdo->query(
            'SELECT COALESCE(MAX(id), 0) FROM donation_centers'
        )->fetchColumn();

        do {
            $n++;
            $code = sprintf('BK-%03d', $n);
        } while ($this->findByCode($code) !== null);

        return $code;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): array
    {
        $params = $this->bindParams($data);
        $query = $this->pdo->prepare(
            'INSERT INTO donation_centers (
                code, name, description, address, province, canton, region,
                lat, lng, contact_name, contact_phone, contact_email,
                open_time, close_time, open_days, daily_capacity, process_minutes,
                accept_walk_ins, active
            ) VALUES (
                :code, :name, :description, :address, :province, :canton, :region,
                :lat, :lng, :contact_name, :contact_phone, :contact_email,
                :open_time, :close_time, :open_days, :daily_capacity, :process_minutes,
                :accept_walk_ins, :active
            )'
        );
        $query->execute($params);

        return $this->findById((int) $this->pdo->lastInsertId(), activeOnly: false) ?? [];
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?array
    {
        $params = $this->bindParams($data);
        $params['id'] = $id;
        $query = $this->pdo->prepare(
            'UPDATE donation_centers SET
                code = :code,
                name = :name,
                description = :description,
                address = :address,
                province = :province,
                canton = :canton,
                region = :region,
                lat = :lat,
                lng = :lng,
                contact_name = :contact_name,
                contact_phone = :contact_phone,
                contact_email = :contact_email,
                open_time = :open_time,
                close_time = :close_time,
                open_days = :open_days,
                daily_capacity = :daily_capacity,
                process_minutes = :process_minutes,
                accept_walk_ins = :accept_walk_ins,
                active = :active
             WHERE id = :id'
        );
        $query->execute($params);

        return $this->findById($id, activeOnly: false);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function bindParams(array $data): array
    {
        return [
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'address' => $data['address'],
            'province' => $data['province'] ?? null,
            'canton' => $data['canton'] ?? null,
            'region' => $data['region'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lng' => $data['lng'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'open_time' => $data['open_time'] ?? null,
            'close_time' => $data['close_time'] ?? null,
            'open_days' => $data['open_days'] ?? null,
            'daily_capacity' => $data['daily_capacity'] ?? null,
            'process_minutes' => $data['process_minutes'] ?? null,
            'accept_walk_ins' => !empty($data['accept_walk_ins']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
        ];
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
