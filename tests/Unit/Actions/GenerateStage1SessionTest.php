<?php

use App\Actions\GenerateStage1Session;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('generates exactly 30 steps with 10 intros and 20 quizzes for a 10-vocabulary group', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $action = app(GenerateStage1Session::class);
    $result = $action->handle($group);

    expect($result)->toHaveKeys(['steps', 'correct_audio_url', 'wrong_audio_url'])
        ->and($result['steps'])->toHaveCount(30)
        ->and(collect($result['steps'])->where('kind', 'intro'))->toHaveCount(10)
        ->and(collect($result['steps'])->where('kind', 'quiz'))->toHaveCount(20);
});

test('every vocabulary is introduced once in ascending id order and tested twice with different question types', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $vocabs = Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ])->sortBy('id')->values();

    $action = app(GenerateStage1Session::class);
    $result = $action->handle($group);

    $intros = collect($result['steps'])->where('kind', 'intro')->values();
    expect($intros)->toHaveCount(10);

    // Intros must follow vocabulary ID order
    foreach ($vocabs as $index => $vocab) {
        expect($intros[$index]['vocabulary']['id'])->toBe($vocab->id);
    }

    // Quizzes: each vocabulary must be tested exactly twice, with distinct quiz types
    $quizzes = collect($result['steps'])->where('kind', 'quiz')->values();
    expect($quizzes)->toHaveCount(20);

    $groupedByVocab = $quizzes->groupBy('vocabulary.id');
    expect($groupedByVocab)->toHaveCount(10);

    foreach ($groupedByVocab as $vocabId => $wordQuizzes) {
        expect($wordQuizzes)->toHaveCount(2);

        $type1 = $wordQuizzes[0]['quiz']['quiz_type'];
        $type2 = $wordQuizzes[1]['quiz']['quiz_type'];
        expect($type1)->not->toBe($type2);
    }
});

test('each quiz question has 4 options with counterpart fields and distractors from the same group', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $vocabs = Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);
    $allVocabIds = $vocabs->pluck('id')->all();

    $action = app(GenerateStage1Session::class);
    $result = $action->handle($group);

    $quizzes = collect($result['steps'])->where('kind', 'quiz')->values();

    foreach ($quizzes as $quizStep) {
        $quiz = $quizStep['quiz'];
        $targetId = $quizStep['vocabulary']['id'];

        expect($quiz['correct_option_id'])->toBe($targetId)
            ->and($quiz['options'])->toHaveCount(4);

        $optionIds = collect($quiz['options'])->pluck('id')->all();
        expect($optionIds)->toContain($targetId)
            ->and(count(array_unique($optionIds)))->toBe(4);

        // Every option ID must be from the same group's vocabularies
        foreach ($optionIds as $optId) {
            expect($allVocabIds)->toContain($optId);
        }

        // Counterpart fields check
        foreach ($quiz['options'] as $option) {
            expect($option)->toHaveKeys(['id', 'text', 'part_of_speech', 'word', 'definition'])
                ->and($option['text'])->not->toBeEmpty();
        }
    }
});
