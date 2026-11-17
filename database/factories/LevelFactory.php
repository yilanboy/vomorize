<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Locale;
use App\Models\Level;
use App\Models\LevelTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        return [];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Level $level): void {
            foreach (Locale::values() as $locale) {
                if ($level->translations()->where('locale', $locale)->doesntExist()) {
                    LevelTranslation::factory()->create([
                        'level_id' => $level->id,
                        'locale' => $locale,
                    ]);
                }
            }
        });
    }

    public function withTranslation(
        string $locale = 'zh_TW',
        ?string $name = null,
        ?string $description = null,
    ): static {
        return $this->afterCreating(function (Level $level) use ($locale, $name, $description): void {
            LevelTranslation::query()->updateOrCreate(
                [
                    'level_id' => $level->id,
                    'locale' => $locale,
                ],
                [
                    'name' => $name ?? $this->faker->randomElement([
                        '基礎入門',
                        '初級進階',
                        '中級應用',
                        '中高級加強',
                        '高級深造',
                        '大師精通',
                        '額外補充',
                    ]),
                    'description' => $description ?? $this->faker->sentence(),
                ],
            );
        });
    }
}
