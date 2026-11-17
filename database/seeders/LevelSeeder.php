<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Locale;
use App\Models\Level;
use App\Models\LevelTranslation;
use Illuminate\Database\Seeder;
use RuntimeException;

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
            throw new RuntimeException("Missing base level data file: {$baseFile}");
        }

        /** @var array<int, array{name: string, description: string}> $baseLevels */
        $baseLevels = require $baseFile;
        $expectedLevelIds = array_keys($baseLevels);

        $levelRows = [];

        foreach ($expectedLevelIds as $id) {
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
                throw new RuntimeException("Missing level translation file for locale [{$locale}]: {$file}");
            }

            /** @var array<int, array{name: string, description: string}> $levels */
            $levels = require $file;

            foreach ($expectedLevelIds as $id) {
                if (! isset($levels[$id]) || trim($levels[$id]['name']) === '' || trim($levels[$id]['description']) === '') {
                    throw new RuntimeException("Missing or empty level translation for level [{$id}] in locale [{$locale}].");
                }

                $translationRows[] = [
                    'level_id' => $id,
                    'locale' => $locale,
                    'name' => $levels[$id]['name'],
                    'description' => $levels[$id]['description'],
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
