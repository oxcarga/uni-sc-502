<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class UserRepository
{
    private const TIPOS_SANGRE = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $query = $this->pdo->query(
            'SELECT id, nombre, email, tipo_sangre, creado_el, actualizado_el FROM usuarios ORDER BY id ASC'
        );

        return $query->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, nombre, email, tipo_sangre, creado_el, actualizado_el FROM usuarios WHERE id = :id'
        );
        $query->execute(['id' => $id]);
        $user = $query->fetch();

        return $user ?: null;
    }

    public function create(array $data): array
    {
        $query = $this->pdo->prepare(
            'INSERT INTO usuarios (nombre, email, tipo_sangre) VALUES (:nombre, :email, :tipo_sangre)'
        );
        $query->execute([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
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
            'UPDATE usuarios SET nombre = :nombre, email = :email, tipo_sangre = :tipo_sangre WHERE id = :id'
        );
        $query->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
        ]);

        if ($query->rowCount() === 0 && $this->findById($id) === null) {
            return null;
        }

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $query = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $query->execute(['id' => $id]);

        return $query->rowCount() > 0;
    }

    public static function isValidBloodType(?string $bloodType): bool
    {
        if ($bloodType === null || $bloodType === '') {
            return true;
        }

        return in_array($bloodType, self::TIPOS_SANGRE, true);
    }
}
