<?php

declare(strict_types=1);

namespace Tests\Browser\Concerns;

use App\Models\Group;
use Carbon\CarbonInterface;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

trait InteractsWithGuestProgress
{
    /**
     * Build a guest IndexedDB learning progress payload with sensible defaults.
     *
     * @return array{
     *     group_id: int,
     *     level_id: int,
     *     stage: int,
     *     last_score: int,
     *     last_reviewed_at: string,
     *     next_review_at: string|null
     * }
     */
    protected function guestProgressRecord(
        Group $group,
        int $stage = 1,
        int $lastScore = 90,
        ?CarbonInterface $lastReviewedAt = null,
        ?CarbonInterface $nextReviewAt = null,
    ): array {
        return [
            'group_id' => $group->id,
            'level_id' => $group->level_id,
            'stage' => $stage,
            'last_score' => $lastScore,
            'last_reviewed_at' => ($lastReviewedAt ?? now()->subDay())->toIso8601String(),
            'next_review_at' => $nextReviewAt?->toIso8601String(),
        ];
    }

    /**
     * Persist one or more guest progress records into IndexedDB in a single transaction.
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    protected function seedGuestProgress(
        AwaitableWebpage|PendingAwaitablePage $page,
        array $records,
    ): int {
        $json = json_encode($records, JSON_THROW_ON_ERROR);

        return (int) $page->script("window.__learningProgressDb.saveMany({$json})");
    }

    /**
     * Read a single group's guest learning progress from IndexedDB.
     *
     * @return array<string, mixed>|null
     */
    protected function guestGroupProgress(
        AwaitableWebpage|PendingAwaitablePage $page,
        Group|int $group,
    ): ?array {
        $groupId = $group instanceof Group ? $group->id : $group;

        /** @var array<string, mixed>|null $record */
        $record = $page->script("window.__learningProgressDb.getGroupProgress({$groupId})");

        return $record;
    }

    /**
     * Assert the number of guest progress records currently stored in IndexedDB (auto-retrying).
     */
    protected function assertGuestProgressCount(
        AwaitableWebpage|PendingAwaitablePage $page,
        int $expected,
    ): AwaitableWebpage {
        return $page->assertScript('window.__learningProgressDb.count()', $expected);
    }
}
