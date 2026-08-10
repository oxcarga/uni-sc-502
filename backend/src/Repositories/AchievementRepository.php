<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AchievementRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findActiveCatalog(): array
    {
        $query = $this->pdo->query(
            'SELECT id, code, name, description, criteria_type, criteria_value, active, created_at
             FROM achievements
             WHERE active = 1
             ORDER BY criteria_value ASC, id ASC'
        );

        return array_map([$this, 'normalizeAchievement'], $query->fetchAll());
    }

    /** @return array<int, array<string, mixed>> keyed by achievement_id */
    public function findByUserIndexed(int $userId): array
    {
        $query = $this->pdo->prepare(
            'SELECT id, user_id, achievement_id, progress, unlocked_at, created_at, updated_at
             FROM donor_achievements
             WHERE user_id = :user_id'
        );
        $query->execute(['user_id' => $userId]);
        $indexed = [];
        foreach ($query->fetchAll() as $row) {
            $row = $this->normalizeDonorAchievement($row);
            $indexed[(int) $row['achievement_id']] = $row;
        }

        return $indexed;
    }

    public function upsertProgress(
        int $userId,
        int $achievementId,
        int $progress,
        ?string $unlockedAt
    ): void {
        $existing = $this->pdo->prepare(
            'SELECT id, unlocked_at FROM donor_achievements
             WHERE user_id = :user_id AND achievement_id = :achievement_id
             LIMIT 1'
        );
        $existing->execute([
            'user_id' => $userId,
            'achievement_id' => $achievementId,
        ]);
        $row = $existing->fetch();

        if ($row) {
            // No pisar unlocked_at si ya estaba desbloqueado.
            $sql = 'UPDATE donor_achievements
                    SET progress = :progress';
            $params = [
                'progress' => $progress,
                'id' => (int) $row['id'],
            ];
            if ($unlockedAt !== null && $row['unlocked_at'] === null) {
                $sql .= ', unlocked_at = :unlocked_at';
                $params['unlocked_at'] = $unlockedAt;
            }
            $sql .= ' WHERE id = :id';
            $update = $this->pdo->prepare($sql);
            $update->execute($params);
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO donor_achievements (user_id, achievement_id, progress, unlocked_at)
             VALUES (:user_id, :achievement_id, :progress, :unlocked_at)'
        );
        $insert->execute([
            'user_id' => $userId,
            'achievement_id' => $achievementId,
            'progress' => $progress,
            'unlocked_at' => $unlockedAt,
        ]);
    }

    /** @param array<string, mixed> $row */
    private function normalizeAchievement(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['criteria_value'] = (int) $row['criteria_value'];
        $row['active'] = (bool) $row['active'];

        return $row;
    }

    /** @param array<string, mixed> $row */
    private function normalizeDonorAchievement(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        $row['achievement_id'] = (int) $row['achievement_id'];
        $row['progress'] = (int) $row['progress'];
        $row['unlocked'] = $row['unlocked_at'] !== null;

        return $row;
    }
}
