<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class NotificationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $query = $this->pdo->prepare(
            "SELECT id, user_id, type, title, body, related_type, related_id, read_at, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}"
        );
        $query->execute(['user_id' => $userId]);

        return array_map([$this, 'normalize'], $query->fetchAll());
    }

    public function countUnread(int $userId): int
    {
        $query = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM notifications
             WHERE user_id = :user_id AND read_at IS NULL'
        );
        $query->execute(['user_id' => $userId]);

        return (int) ($query->fetch()['total'] ?? 0);
    }

    public function findByIdForUser(int $id, int $userId): ?array
    {
        $query = $this->pdo->prepare(
            'SELECT id, user_id, type, title, body, related_type, related_id, read_at, created_at
             FROM notifications
             WHERE id = :id AND user_id = :user_id'
        );
        $query->execute(['id' => $id, 'user_id' => $userId]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function create(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): array {
        $query = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, body, related_type, related_id)
             VALUES (:user_id, :type, :title, :body, :related_type, :related_id)'
        );
        $query->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
        $created = $this->findByIdForUser((int) $this->pdo->lastInsertId(), $userId);
        if ($created === null) {
            throw new PDOException('No se pudo crear la notificación.');
        }

        return $created;
    }

    public function markRead(int $id, int $userId): array
    {
        $query = $this->pdo->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, NOW())
             WHERE id = :id AND user_id = :user_id'
        );
        $query->execute(['id' => $id, 'user_id' => $userId]);
        $row = $this->findByIdForUser($id, $userId);
        if ($row === null) {
            throw new PDOException('Notificación no encontrada.');
        }

        return $row;
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        $row['related_id'] = $row['related_id'] !== null ? (int) $row['related_id'] : null;
        $row['unread'] = $row['read_at'] === null;

        return $row;
    }
}
