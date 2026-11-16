<?php

declare(strict_types=1);

use App\Enums\Locale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('level_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('name');
            $table->text('description');
            $table->timestamps();

            $table->unique(['level_id', 'locale']);
            $table->index(['locale', 'level_id']);
        });

        $now = now()->toDateTimeString();
        $defaultLocale = Locale::default()->value;

        $rows = DB::table('levels')
            ->orderBy('id')
            ->get(['id', 'name', 'description'])
            ->map(fn (object $level): array => [
                'level_id' => $level->id,
                'locale' => $defaultLocale,
                'name' => $level->name,
                'description' => $level->description,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('level_translations')->insert($rows);
        }

        Schema::table('levels', function (Blueprint $table): void {
            $table->dropColumn(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table): void {
            $table->string('name')->after('id')->default('');
            $table->text('description')->after('name')->default('');
        });

        $defaultLocale = Locale::default()->value;

        $translations = DB::table('level_translations')
            ->where('locale', $defaultLocale)
            ->get(['level_id', 'name', 'description']);

        foreach ($translations as $translation) {
            DB::table('levels')
                ->where('id', $translation->level_id)
                ->update([
                    'name' => $translation->name,
                    'description' => $translation->description,
                ]);
        }

        Schema::dropIfExists('level_translations');
    }
};
