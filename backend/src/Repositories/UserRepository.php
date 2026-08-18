<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class UserRepository
{
    private const BLOOD_TYPES = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
    private const ROLES = ['donor', 'bank', 'admin'];
    private const SELECT_PUBLIC =
        'id, first_name, last_name, email, role, active, email_confirmed, email_confirmed_at, created_at, updated_at';
    private const SELECT_ADMIN_LIST =
        'u.id, u.first_name, u.last_name, u.email, u.role, u.active,
         u.email_confirmed, u.email_confirmed_at, u.created_at, u.updated_at,
         dp.blood_type, dp.phone, dp.province, dp.canton, dp.eligible, dp.last_donation_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $query = $this->pdo->query(
            'SELECT ' . self::SELECT_ADMIN_LIST . '
             FROM users u
             LEFT JOIN donor_profiles dp ON dp.user_id = u.id
             ORDER BY u.id ASC'
        );

        return array_map([$this, 'toAdminListItem'], $query->fetchAll());
    }

    /**
     * Listado para gobierno admin. Filtros opcionales: role, active, q (nombre/email).
     *
     * @param array{role?: string, active?: bool, q?: string} $filters
     * @return list<array<string, mixed>>
     */
    public function findForAdmin(array $filters = []): array
    {
        $sql = 'SELECT ' . self::SELECT_ADMIN_LIST . '
                FROM users u
                LEFT JOIN donor_profiles dp ON dp.user_id = u.id
                WHERE 1 = 1';
        $params = [];

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $sql .= ' AND u.role = :role';
            $params['role'] = $role;
        }

        if (array_key_exists('active', $filters) && is_bool($filters['active'])) {
            $sql .= ' AND u.active = :active';
            $params['active'] = $filters['active'] ? 1 : 0;
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (
                u.first_name LIKE :q
                OR u.last_name LIKE :q
                OR u.email LIKE :q
                OR CONCAT(u.first_name, \' \', u.last_name) LIKE :q
            )';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY u.id ASC';
        $query = $this->pdo->prepare($sql);
        $query->execute($params);

        return array_map([$this, 'toAdminListItem'], $query->fetchAll());
    }

    public function countByRole(string $role): int
    {
        $query = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $query->execute(['role' => $role]);

        return (int) $query->fetchColumn();
    }

    public function countActiveAdmins(): int
    {
        $query = $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1"
        );

        return (int) $query->fetchColumn();
    }

    public function findAdminById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT_ADMIN_LIST . '
             FROM users u
             LEFT JOIN donor_profiles dp ON dp.user_id = u.id
             WHERE u.id = :id'
        );
        $query->execute(['id' => $id]);
        $row = $query->fetch();

        return $row ? self::toAdminListItem($row) : null;
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT_PUBLIC . ' FROM users WHERE id = :id'
        );
        $query->execute(['id' => $id]);
        $user = $query->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT_PUBLIC . ', password_hash
             FROM users WHERE email = :email'
        );
        $query->execute(['email' => $email]);
        $user = $query->fetch();

        return $user ?: null;
    }

    public function create(array $data): array
    {
        $role = $data['role'] ?? 'donor';
        $bloodType = $data['blood_type'] ?? null;
        $phone = $data['phone'] ?? null;
        $emailConfirmed = array_key_exists('email_confirmed', $data)
            ? (bool) $data['email_confirmed']
            : false;

        $this->pdo->beginTransaction();

        try {
            $query = $this->pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, role, email_confirmed, email_confirmed_at)
                 VALUES (:first_name, :last_name, :email, :password_hash, :role, :email_confirmed, :email_confirmed_at)'
            );
            $query->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'role' => $role,
                'email_confirmed' => $emailConfirmed ? 1 : 0,
                'email_confirmed_at' => $emailConfirmed ? date('Y-m-d H:i:s') : null,
            ]);

            $userId = (int) $this->pdo->lastInsertId();

            if ($role === 'donor') {
                $profile = $this->pdo->prepare(
                    'INSERT INTO donor_profiles (user_id, blood_type, phone)
                     VALUES (:user_id, :blood_type, :phone)'
                );
                $profile->execute([
                    'user_id' => $userId,
                    'blood_type' => $bloodType !== null && $bloodType !== '' ? $bloodType : null,
                    'phone' => $phone !== null && $phone !== '' ? $phone : null,
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        $user = $this->findById($userId);
        if ($user === null) {
            throw new PDOException('No se pudo recuperar el usuario creado.');
        }

        return $user;
    }

    public function update(int $id, array $data): ?array
    {
        $fields = [
            'first_name = :first_name',
            'last_name = :last_name',
            'email = :email',
        ];
        $params = [
            'id' => $id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ];

        if (isset($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        if (isset($data['role'])) {
            $fields[] = 'role = :role';
            $params['role'] = $data['role'];
        }

        if (array_key_exists('active', $data)) {
            $fields[] = 'active = :active';
            $params['active'] = $data['active'] ? 1 : 0;
        }

        $query = $this->pdo->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $query->execute($params);

        if ($query->rowCount() === 0 && $this->findById($id) === null) {
            return null;
        }

        return $this->findById($id);
    }

    /**
     * Actualiza solo campos de cuenta presentes en $data
     * (first_name, last_name, password_hash).
     *
     * @param array<string, mixed> $data
     */
    public function updateAccount(int $id, array $data): ?array
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['first_name', 'last_name', 'password_hash'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $data[$key];
        }

        if ($fields === []) {
            return $this->findById($id);
        }

        $query = $this->pdo->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $query->execute($params);

        return $this->findById($id);
    }

    public function markEmailConfirmed(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'UPDATE users
             SET email_confirmed = 1,
                 email_confirmed_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $query->execute(['id' => $id]);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $query = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $query->execute(['id' => $id]);

        return $query->rowCount() > 0;
    }

    public static function toPublic(array $user): array
    {
        unset($user['password_hash']);

        if (array_key_exists('active', $user)) {
            $user['active'] = (bool) $user['active'];
        }

        if (array_key_exists('email_confirmed', $user)) {
            $user['email_confirmed'] = (bool) $user['email_confirmed'];
        }

        return $user;
    }

    /** @param array<string, mixed> $user */
    public static function toAdminListItem(array $user): array
    {
        $public = self::toPublic($user);
        if (array_key_exists('eligible', $public)) {
            $public['eligible'] = (bool) $public['eligible'];
        }

        return $public;
    }

    public static function toSession(array $user): array
    {
        $public = self::toPublic($user);

        return [
            'id' => $public['id'],
            'first_name' => $public['first_name'],
            'last_name' => $public['last_name'],
            'email' => $public['email'],
            'role' => $public['role'],
        ];
    }

    public static function isValidBloodType(?string $bloodType): bool
    {
        if ($bloodType === null || $bloodType === '') {
            return true;
        }

        return in_array($bloodType, self::BLOOD_TYPES, true);
    }

    public static function isValidRole(?string $role): bool
    {
        if ($role === null || $role === '') {
            return false;
        }

        return in_array($role, self::ROLES, true);
    }
}
