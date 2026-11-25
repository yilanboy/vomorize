<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

readonly class GetDashboardKpis
{
    public function __construct(
        private readonly GetUserStreak $getUserStreak,
    ) {}

    /**
     * @param  Collection<int, LearningProgress>  $userProgress
     * @return array{
     *     streak: array{
     *         current: int,
     *         longest: int,
     *         is_checked_in_today: bool,
     *     },
     *     learning_words: array{
     *         words_count: int,
     *         groups_count: int,
     *     },
     *     mastered_words: array{
     *         words_count: int,
     *         groups_count: int,
     *     },
     * }
     */
    public function handle(
        User $user,
        Collection $userProgress,
    ): array {
        $learningGroupsCount = 0;
        $masteredGroupsCount = 0;

        foreach ($userProgress as $progress) {
            $stage = $progress->stage;

            if ($stage >= 6) {
                $masteredGroupsCount++;
            } elseif ($stage >= 1) {
                $learningGroupsCount++;
            }
        }

        return [
            'streak' => $this->getUserStreak->handle($user),
            'learning_words' => [
                'words_count' => $learningGroupsCount * Group::VOCABULARY_PER_GROUP,
                'groups_count' => $learningGroupsCount,
            ],
            'mastered_words' => [
                'words_count' => $masteredGroupsCount * Group::VOCABULARY_PER_GROUP,
                'groups_count' => $masteredGroupsCount,
            ],
        ];
    }
}
