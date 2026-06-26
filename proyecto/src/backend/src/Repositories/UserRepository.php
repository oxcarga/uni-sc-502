<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class UserRepository
{
    private const BLOOD_TYPES = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $query = $this->pdo->query(
            'SELECT id, name, email, blood_type, created_at FROM users ORDER BY id ASC'
        );

        return $query->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, name, email, blood_type, created_at FROM users WHERE id = :id'
        );
        $query->execute(['id' => $id]);
        $user = $query->fetch();

        return $user ?: null;
    }

    public function create(array $data): array
    {
        $query = $this->pdo->prepare(
            'INSERT INTO users (name, email, blood_type) VALUES (:name, :email, :blood_type)'
        );
        $query->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'blood_type' => $data['blood_type'] ?? null,
        ]);

        $user = $this->findById((int) $this->pdo->lastInsertId());
        if ($user === null) {
            throw new PDOException('No se pudo recuperar el usuario creado.');
        }

        return $user;
    }

    public function update(int $id, array $data): ?array
    {
        $query = $this->pdo->prepare(
            'UPDATE users SET name = :name, email = :email, blood_type = :blood_type WHERE id = :id'
        );
        $query->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'blood_type' => $data['blood_type'] ?? null,
        ]);

        if ($query->rowCount() === 0 && $this->findById($id) === null) {
            return null;
        }

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $query = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $query->execute(['id' => $id]);

        return $query->rowCount() > 0;
    }

    public static function isValidBloodType(?string $bloodType): bool
    {
        if ($bloodType === null || $bloodType === '') {
            return true;
        }

        return in_array($bloodType, self::BLOOD_TYPES, true);
    }
}
