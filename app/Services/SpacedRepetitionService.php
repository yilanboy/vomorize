<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SpacedRepetitionService
{
    /**
     * Stage interval mappings in seconds / hours / days.
     */
    public const INTERVALS = [
        1 => 12 * 3600,       // 12 Hours
        2 => 24 * 3600,       // 1 Day
        3 => 2 * 24 * 3600,   // 2 Days
        4 => 4 * 24 * 3600,   // 4 Days
        5 => 7 * 24 * 3600,   // 7 Days
        6 => 15 * 24 * 3600,  // 15 Days
    ];

    /**
     * Penalty intervals for failed attempts.
     */
    public const STAGE_0_FAIL_PENALTY = 12 * 3600; // 12 Hours

    public const LATER_FAIL_PENALTY = 24 * 3600;   // 1 Day

    /**
     * Calculate the new progress state given current stage and session score (0-100).
     *
     * @return array{stage: int, last_score: int, last_reviewed_at: Carbon, next_review_at: Carbon|null, passed: bool}
     */
    public static function calculateNextState(int $currentStage, int $score, ?CarbonInterface $now = null): array
    {
        $now = $now ? Carbon::instance($now)->utc() : Carbon::now('UTC');
        $passed = $score >= 90;

        if ($passed) {
            $newStage = min(6, $currentStage + 1);

            if ($newStage === 6) {
                $nextReviewAt = null; // Completed group has no lock/cooldown
            } else {
                $intervalSeconds = self::INTERVALS[$newStage] ?? self::INTERVALS[1];
                $nextReviewAt = (clone $now)->addSeconds($intervalSeconds);
            }
        } else {
            $newStage = $currentStage;

            if ($currentStage === 6) {
                $nextReviewAt = null; // Stage 6 retests never penalty-lock
            } elseif ($currentStage === 0) {
                $nextReviewAt = (clone $now)->addSeconds(self::STAGE_0_FAIL_PENALTY);
            } else {
                $nextReviewAt = (clone $now)->addSeconds(self::LATER_FAIL_PENALTY);
            }
        }

        return [
            'stage' => $newStage,
            'last_score' => $score,
            'last_reviewed_at' => $now,
            'next_review_at' => $nextReviewAt,
            'passed' => $passed,
        ];
    }

    /**
     * Derive group status string: 'not_started' | 'locked' | 'penalty' | 'ready' | 'completed'
     */
    public static function deriveStatus(int $stage, ?int $lastScore, ?CarbonInterface $nextReviewAt, ?CarbonInterface $now = null): string
    {
        $now = $now ? Carbon::instance($now)->utc() : Carbon::now('UTC');

        if ($stage === 6) {
            return 'completed';
        }

        $isLocked = $nextReviewAt !== null && $nextReviewAt->isAfter($now);

        if ($isLocked) {
            if ($lastScore !== null && $lastScore < 60) {
                return 'penalty';
            }

            return 'locked';
        }

        if ($stage === 0 && $lastScore === null) {
            return 'not_started';
        }

        return 'ready';
    }
}
