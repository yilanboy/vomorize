<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningProgress>
 */
class LearningProgressFactory extends Factory
{
    protected $model = LearningProgress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level_id' => Level::factory(),
            'group_id' => Group::factory(),
            'stage' => 1,
            'last_score' => 100,
            'last_reviewed_at' => now(),
            'next_review_at' => now()->addDay(),
        ];
    }

    public function readyForReview(int $stage = 1, int $lastScore = 85): static
    {
        return $this->state(fn (): array => [
            'stage' => $stage,
            'last_score' => $lastScore,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subMinute(),
        ]);
    }
}
