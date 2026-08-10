<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AchievementRepository;
use App\Repositories\DonationRepository;

class AchievementService
{
    public function __construct(
        private readonly AchievementRepository $achievements,
        private readonly DonationRepository $donations
    ) {
    }

    /**
     * Catálogo + progreso del donante.
     *
     * @return array{donation_count: int, achievements: list<array<string, mixed>>}
     */
    public function statusForDonor(int $donorId): array
    {
        $count = $this->donations->countByDonor($donorId);
        $owned = $this->achievements->findByUserIndexed($donorId);
        $list = [];

        foreach ($this->achievements->findActiveCatalog() as $item) {
            $row = $owned[(int) $item['id']] ?? null;
            $target = (int) $item['criteria_value'];
            $progress = $row ? (int) $row['progress'] : min($count, $target);
            if ($item['criteria_type'] === 'donation_count' && !$row) {
                $progress = min($count, $target);
            }
            $unlocked = $row ? (bool) $row['unlocked'] : ($count >= $target);

            $list[] = [
                'id' => (int) $item['id'],
                'code' => $item['code'],
                'name' => $item['name'],
                'description' => $item['description'],
                'criteria_type' => $item['criteria_type'],
                'criteria_value' => $target,
                'progress' => $progress,
                'unlocked' => $unlocked,
                'unlocked_at' => $row['unlocked_at'] ?? null,
            ];
        }

        return [
            'donation_count' => $count,
            'achievements' => $list,
        ];
    }

    /**
     * Recalcula progreso tras una donación. Idempotente por UNIQUE (user, achievement).
     *
     * @return list<array<string, mixed>> logros recién desbloqueados
     */
    public function evaluateAfterDonation(int $donorId): array
    {
        $count = $this->donations->countByDonor($donorId);
        $owned = $this->achievements->findByUserIndexed($donorId);
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $newlyUnlocked = [];

        foreach ($this->achievements->findActiveCatalog() as $item) {
            if ($item['criteria_type'] !== 'donation_count') {
                continue;
            }

            $achievementId = (int) $item['id'];
            $target = (int) $item['criteria_value'];
            $progress = min($count, $target);
            $wasUnlocked = isset($owned[$achievementId]) && !empty($owned[$achievementId]['unlocked']);
            $shouldUnlock = $count >= $target;
            $unlockedAt = ($shouldUnlock && !$wasUnlocked) ? $now : null;

            $this->achievements->upsertProgress(
                $donorId,
                $achievementId,
                $progress,
                $unlockedAt
            );

            if ($shouldUnlock && !$wasUnlocked) {
                $newlyUnlocked[] = [
                    'id' => $achievementId,
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'unlocked_at' => $now,
                ];
            }
        }

        return $newlyUnlocked;
    }
}
