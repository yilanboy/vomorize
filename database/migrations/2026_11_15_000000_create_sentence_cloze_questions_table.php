<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sentence_cloze_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_id')->unique()->constrained('vocabularies')->cascadeOnDelete();
            $table->text('sentence');
            $table->string('blank_word');
            $table->text('translation');
            $table->json('distractors');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_cloze_questions');
    }
};
