<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locale = config('app.locale', 'zh_TW');
        $file = database_path("data/{$locale}/levels.php");

        if (! file_exists($file)) {
            $file = database_path('data/zh_TW/levels.php');
        }

        /** @var array<int, array{name: string, description: string}> $levels */
        $levels = require $file;

        foreach ($levels as $id => $data) {
            Level::query()->updateOrCreate(
                ['id' => $id],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]
            );
        }
    }
}
