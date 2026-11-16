<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Locale;
use App\Models\Level;
use App\Models\LevelTranslation;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now()->toDateTimeString();
        $baseFile = database_path('data/zh_TW/levels.php');

        if (! file_exists($baseFile)) {
            return;
        }

        /** @var array<int, array{name: string, description: string}> $baseLevels */
        $baseLevels = require $baseFile;

        $levelRows = [];

        foreach (array_keys($baseLevels) as $id) {
            $levelRows[] = [
                'id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($levelRows !== []) {
            Level::upsert(
                $levelRows,
                uniqueBy: ['id'],
                update: ['updated_at'],
            );
        }

        $translationRows = [];

        foreach (Locale::values() as $locale) {
            $file = database_path("data/{$locale}/levels.php");

            if (! file_exists($file)) {
                continue;
            }

            /** @var array<int, array{name: string, description: string}> $levels */
            $levels = require $file;

            foreach ($levels as $id => $data) {
                $translationRows[] = [
                    'level_id' => $id,
                    'locale' => $locale,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($translationRows !== []) {
            LevelTranslation::upsert(
                $translationRows,
                uniqueBy: ['level_id', 'locale'],
                update: ['name', 'description', 'updated_at'],
            );
        }
    }
}
