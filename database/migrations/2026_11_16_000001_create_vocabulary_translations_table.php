<?php

declare(strict_types=1);

use App\Enums\Locale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vocabulary_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vocabulary_id')->constrained('vocabularies')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->text('definition');
            $table->text('example_translation');
            $table->timestamps();

            $table->unique(['vocabulary_id', 'locale']);
            $table->index(['locale', 'vocabulary_id']);
        });

        $now = now()->toDateTimeString();
        $defaultLocale = Locale::default()->value;

        DB::table('vocabularies')
            ->orderBy('id')
            ->select(['id', 'definition', 'example_translation'])
            ->chunkById(500, function (Collection $vocabularies) use ($now, $defaultLocale): void {
                $rows = $vocabularies
                    ->map(fn (object $vocab): array => [
                        'vocabulary_id' => $vocab->id,
                        'locale' => $defaultLocale,
                        'definition' => $vocab->definition,
                        'example_translation' => $vocab->example_translation,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($rows !== []) {
                    DB::table('vocabulary_translations')->insert($rows);
                }
            });

        Schema::table('vocabularies', function (Blueprint $table): void {
            $table->dropColumn(['definition', 'example_translation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vocabularies', function (Blueprint $table): void {
            $table->text('definition')->after('pronunciation')->default('');
            $table->text('example_translation')->after('example_sentence')->default('');
        });

        $defaultLocale = Locale::default()->value;

        DB::table('vocabulary_translations')
            ->where('locale', $defaultLocale)
            ->orderBy('id')
            ->select(['id', 'vocabulary_id', 'definition', 'example_translation'])
            ->chunkById(500, function (Collection $translations): void {
                foreach ($translations as $translation) {
                    DB::table('vocabularies')
                        ->where('id', $translation->vocabulary_id)
                        ->update([
                            'definition' => $translation->definition,
                            'example_translation' => $translation->example_translation,
                        ]);
                }
            });

        Schema::dropIfExists('vocabulary_translations');
    }
};
