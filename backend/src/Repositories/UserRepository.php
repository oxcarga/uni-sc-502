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

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $query = $this->pdo->query(
            'SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.role,
                u.active,
                u.email_confirmed,
                u.email_confirmed_at,
                u.created_at,
                u.updated_at,
                dp.blood_type,
                dp.phone,
                dp.province,
                dp.canton,
                dp.eligible,
                dp.last_donation_at
            FROM users u
            LEFT JOIN donor_profiles dp ON dp.user_id = u.id
            ORDER BY u.id ASC'
        );

        return $query->fetchAll();
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

        $this->pdo->beginTransaction();

        try {
            $query = $this->pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, role, email_confirmed)
                 VALUES (:first_name, :last_name, :email, :password_hash, :role, :email_confirmed)'
            );
            $query->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'role' => $role,
                'email_confirmed' => array_key_exists('email_confirmed', $data)
                    ? ((int) (bool) $data['email_confirmed'])
                    : 0,
            ]);

            $userId = (int) $this->pdo->lastInsertId();

            if ($role === 'donor') {
                $profile = $this->pdo->prepare(
                    'INSERT INTO donor_profiles (user_id, blood_type)
                     VALUES (:user_id, :blood_type)'
                );
                $profile->execute([
                    'user_id' => $userId,
                    'blood_type' => $bloodType !== '' ? $bloodType : null,
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
