<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AuditLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findRecent(int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        $query = $this->pdo->query(
            "SELECT a.id, a.user_id, a.action, a.entity_type, a.entity_id, a.detail,
                    a.ip_address, a.created_at,
                    u.first_name, u.last_name, u.email
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT {$limit}"
        );

        return array_map([$this, 'normalize'], $query->fetchAll());
    }

    public function write(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $detail = null,
        ?string $ipAddress = null
    ): void {
        $query = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action, entity_type, entity_id, detail, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :detail, :ip_address)'
        );
        $query->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'detail' => $detail,
            'ip_address' => $ipAddress,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $row['entity_id'] = $row['entity_id'] !== null ? (int) $row['entity_id'] : null;

        return $row;
    }
}
