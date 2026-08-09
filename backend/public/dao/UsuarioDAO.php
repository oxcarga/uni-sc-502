<?php

declare(strict_types=1);

class UsuarioDAO
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?Usuario
    {
        $query = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, password_hash, active, email_confirmed
             FROM users
             WHERE email = ?'
        );
        $query->execute([$email]);
        $row = $query->fetch();

        if ($row === false) {
            return null;
        }

        return Usuario::fromRow($row);
    }
}
