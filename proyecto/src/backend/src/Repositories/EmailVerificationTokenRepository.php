<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class EmailVerificationTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function invalidatePendingForUser(int $usuarioId): void
    {
        $query = $this->pdo->prepare(
            'UPDATE tokens_verificacion_correo
             SET usado_el = CURRENT_TIMESTAMP
             WHERE usuario_id = :usuario_id AND usado_el IS NULL'
        );
        $query->execute(['usuario_id' => $usuarioId]);
    }

    public function create(int $usuarioId, string $tokenHash, string $expiraEl): void
    {
        $query = $this->pdo->prepare(
            'INSERT INTO tokens_verificacion_correo (usuario_id, token_hash, expira_el)
             VALUES (:usuario_id, :token_hash, :expira_el)'
        );
        $query->execute([
            'usuario_id' => $usuarioId,
            'token_hash' => $tokenHash,
            'expira_el' => $expiraEl,
        ]);
    }

    /**
     * @return array{id:int,usuario_id:int,token_hash:string,expira_el:string,usado_el:?string}|null
     */
    public function findByTokenHash(string $tokenHash): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, usuario_id, token_hash, expira_el, usado_el
             FROM tokens_verificacion_correo
             WHERE token_hash = :token_hash'
        );
        $query->execute(['token_hash' => $tokenHash]);
        $row = $query->fetch();

        return $row ?: null;
    }

    public function markUsed(int $tokenId): void
    {
        $query = $this->pdo->prepare(
            'UPDATE tokens_verificacion_correo
             SET usado_el = CURRENT_TIMESTAMP
             WHERE id = :id AND usado_el IS NULL'
        );
        $query->execute(['id' => $tokenId]);
    }
}
