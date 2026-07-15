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
        'id, first_name, last_name, email, role, active, email_confirmed, email_confirmed_at, blood_type, created_at, updated_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $query = $this->pdo->query(
            'SELECT ' . self::SELECT_PUBLIC . ' FROM users ORDER BY id ASC'
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
        $query = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash, role, blood_type, email_confirmed)
             VALUES (:first_name, :last_name, :email, :password_hash, :role, :blood_type, :email_confirmed)'
        );
        $query->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'role' => $data['role'] ?? 'donor',
            'blood_type' => $data['blood_type'] ?? null,
            'email_confirmed' => array_key_exists('email_confirmed', $data)
                ? ((int) (bool) $data['email_confirmed'])
                : 0,
        ]);

        $user = $this->findById((int) $this->pdo->lastInsertId());
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
            'blood_type = :blood_type',
        ];
        $params = [
            'id' => $id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'blood_type' => $data['blood_type'] ?? null,
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
