<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class UserRepository
{
    private const TIPOS_SANGRE = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
    private const ROLES = ['donante', 'banco', 'admin'];
    private const SELECT_PUBLIC =
        'id, nombre, apellido, email, rol, activo, tipo_sangre, creado_el, actualizado_el';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $query = $this->pdo->query(
            'SELECT ' . self::SELECT_PUBLIC . ' FROM usuarios ORDER BY id ASC'
        );

        return $query->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT_PUBLIC . ' FROM usuarios WHERE id = :id'
        );
        $query->execute(['id' => $id]);
        $user = $query->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT ' . self::SELECT_PUBLIC . ', password_hash
             FROM usuarios WHERE email = :email'
        );
        $query->execute(['email' => $email]);
        $user = $query->fetch();

        return $user ?: null;
    }

    public function create(array $data): array
    {
        $query = $this->pdo->prepare(
            'INSERT INTO usuarios (nombre, apellido, email, password_hash, rol, tipo_sangre)
             VALUES (:nombre, :apellido, :email, :password_hash, :rol, :tipo_sangre)'
        );
        $query->execute([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'rol' => $data['rol'] ?? 'donante',
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
        $fields = [
            'nombre = :nombre',
            'apellido = :apellido',
            'email = :email',
            'tipo_sangre = :tipo_sangre',
        ];
        $params = [
            'id' => $id,
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'tipo_sangre' => $data['tipo_sangre'] ?? null,
        ];

        if (isset($data['password_hash'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = $data['password_hash'];
        }

        if (isset($data['rol'])) {
            $fields[] = 'rol = :rol';
            $params['rol'] = $data['rol'];
        }

        if (array_key_exists('activo', $data)) {
            $fields[] = 'activo = :activo';
            $params['activo'] = $data['activo'] ? 1 : 0;
        }

        $query = $this->pdo->prepare(
            'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $query->execute($params);

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

    public static function toPublic(array $user): array
    {
        unset($user['password_hash']);

        if (array_key_exists('activo', $user)) {
            $user['activo'] = (bool) $user['activo'];
        }

        return $user;
    }

    public static function isValidBloodType(?string $bloodType): bool
    {
        if ($bloodType === null || $bloodType === '') {
            return true;
        }

        return in_array($bloodType, self::TIPOS_SANGRE, true);
    }

    public static function isValidRole(?string $role): bool
    {
        if ($role === null || $role === '') {
            return false;
        }

        return in_array($role, self::ROLES, true);
    }
}
