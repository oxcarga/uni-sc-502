<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class DonorProfileRepository
{
    public const BLOOD_TYPES = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    private const SELECT = 'user_id, blood_type, birth_date, phone, province, canton, address,
        medical_history, eligible, last_donation_at,
        notify_nearby, notify_appointments, notify_blood_match,
        created_at, updated_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUserId(int $userId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT . ' FROM donor_profiles WHERE user_id = :user_id'
        );
        $query->execute(['user_id' => $userId]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    /** Crea perfil vacío si el donante aún no tiene fila (edge case). */
    public function ensureForUser(int $userId): array
    {
        $existing = $this->findByUserId($userId);
        if ($existing !== null) {
            return $existing;
        }

        $query = $this->pdo->prepare(
            'INSERT INTO donor_profiles (user_id) VALUES (:user_id)'
        );
        $query->execute(['user_id' => $userId]);

        $created = $this->findByUserId($userId);
        if ($created === null) {
            throw new PDOException('No se pudo crear el perfil del donante.');
        }

        return $created;
    }

    /**
     * @param array<string, mixed> $data Campos de perfil a actualizar
     */
    public function update(int $userId, array $data): array
    {
        $this->ensureForUser($userId);

        $fields = [];
        $params = ['user_id' => $userId];

        $allowed = [
            'blood_type',
            'birth_date',
            'phone',
            'province',
            'canton',
            'address',
            'medical_history',
            'notify_nearby',
            'notify_appointments',
            'notify_blood_match',
        ];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $data[$key];
        }

        if ($fields === []) {
            return $this->ensureForUser($userId);
        }

        $sql = 'UPDATE donor_profiles SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id';
        $query = $this->pdo->prepare($sql);
        $query->execute($params);

        $updated = $this->findByUserId($userId);
        if ($updated === null) {
            throw new PDOException('No se pudo actualizar el perfil del donante.');
        }

        return $updated;
    }

    public function touchLastDonation(int $userId, string $donatedAt): void
    {
        $this->ensureForUser($userId);
        $query = $this->pdo->prepare(
            'UPDATE donor_profiles
             SET last_donation_at = :donated_at, eligible = 0
             WHERE user_id = :user_id'
        );
        $query->execute([
            'donated_at' => $donatedAt,
            'user_id' => $userId,
        ]);
    }

    /**
     * Donantes con el mismo tipo de sangre (exacto) y elegibles por defecto.
     *
     * @return list<array<string, mixed>>
     */
    public function findCompatible(string $bloodType, bool $eligibleOnly = true, int $limit = 50): array
    {
        if (!in_array($bloodType, self::BLOOD_TYPES, true)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $sql = 'SELECT u.id AS user_id, u.first_name, u.last_name, u.email,
                       dp.blood_type, dp.phone, dp.province, dp.canton,
                       dp.eligible, dp.last_donation_at,
                       dp.notify_blood_match
                FROM donor_profiles dp
                JOIN users u ON u.id = dp.user_id
                WHERE dp.blood_type = :blood_type
                  AND u.role = \'donor\'
                  AND u.active = 1';
        if ($eligibleOnly) {
            $sql .= ' AND dp.eligible = 1';
        }
        $sql .= " ORDER BY dp.last_donation_at ASC, u.last_name ASC, u.first_name ASC LIMIT {$limit}";

        $query = $this->pdo->prepare($sql);
        $query->execute(['blood_type' => $bloodType]);

        return array_map(static function (array $row): array {
            $row['user_id'] = (int) $row['user_id'];
            $row['eligible'] = (bool) $row['eligible'];
            $row['notify_blood_match'] = (bool) $row['notify_blood_match'];

            return $row;
        }, $query->fetchAll());
    }

    public static function isValidBloodType(?string $bloodType): bool
    {
        if ($bloodType === null || $bloodType === '') {
            return true;
        }

        return in_array($bloodType, self::BLOOD_TYPES, true);
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['user_id'] = (int) $row['user_id'];
        $row['eligible'] = (bool) $row['eligible'];
        $row['notify_nearby'] = (bool) $row['notify_nearby'];
        $row['notify_appointments'] = (bool) $row['notify_appointments'];
        $row['notify_blood_match'] = (bool) $row['notify_blood_match'];

        return $row;
    }
}
