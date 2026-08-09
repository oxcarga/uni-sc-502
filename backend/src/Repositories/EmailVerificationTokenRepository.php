<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class EmailVerificationTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function invalidatePendingForUser(int $userId): void
    {
        $query = $this->pdo->prepare(
            'UPDATE email_verification_tokens
             SET used_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND used_at IS NULL'
        );
        $query->execute(['user_id' => $userId]);
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): void
    {
        $query = $this->pdo->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $query->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * @return array{id:int,user_id:int,token_hash:string,expires_at:string,used_at:?string}|null
     */
    public function findByTokenHash(string $tokenHash): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, user_id, token_hash, expires_at, used_at
             FROM email_verification_tokens
             WHERE token_hash = :token_hash'
        );
        $query->execute(['token_hash' => $tokenHash]);
        $row = $query->fetch();

        return $row ?: null;
    }

    public function markUsed(int $tokenId): void
    {
        $query = $this->pdo->prepare(
            'UPDATE email_verification_tokens
             SET used_at = CURRENT_TIMESTAMP
             WHERE id = :id AND used_at IS NULL'
        );
        $query->execute(['id' => $tokenId]);
    }
}
