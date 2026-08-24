<?php

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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'level_id' => Level::factory(),
            'group_id' => Group::factory(),
            'stage' => 1,
            'last_score' => 100,
            'last_reviewed_at' => now(),
            'next_review_at' => now()->addHours(12),
        ];
    }
}
