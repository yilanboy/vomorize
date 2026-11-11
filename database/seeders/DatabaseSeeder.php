<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LevelSeeder::class,
            VocabularySeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $user = User::query()->firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => bcrypt('password'),
                ]
            );

            $user->learningProgress()->upsert([
                [
                    'level_id' => 1,
                    'group_id' => 1,
                    'stage' => 1,
                    'last_score' => 90,
                    'last_reviewed_at' => now()->subDay(),
                    'next_review_at' => now(),
                ],
                [
                    'level_id' => 1,
                    'group_id' => 2,
                    'stage' => 2,
                    'last_score' => 80,
                    'last_reviewed_at' => now()->subDays(2),
                    'next_review_at' => now()->addDay(),
                ],
            ], ['user_id', 'group_id']);
        }
    }
}
