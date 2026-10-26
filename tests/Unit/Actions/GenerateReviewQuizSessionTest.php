<?php

use App\Actions\GenerateReviewQuizSession;
use App\Models\Group;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('generates exactly 20 quiz steps with zero intro steps for a 10-vocabulary group', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $action = app(GenerateReviewQuizSession::class);
    $result = $action->handle($group);

    expect($result)->toHaveKeys(['steps', 'correct_audio_url', 'wrong_audio_url'])
        ->and($result['steps'])->toHaveCount(20)
        ->and(collect($result['steps'])->where('kind', 'intro'))->toHaveCount(0)
        ->and(collect($result['steps'])->where('kind', 'quiz'))->toHaveCount(20);
});

test('every vocabulary is tested twice with different question types and spaced at least 4 indices apart', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $action = app(GenerateReviewQuizSession::class);
    $result = $action->handle($group);

    $steps = collect($result['steps']);
    expect($steps)->toHaveCount(20);

    $groupedByVocab = $steps->groupBy('vocabulary.id');
    expect($groupedByVocab)->toHaveCount(10);

    foreach ($groupedByVocab as $vocabId => $wordQuizzes) {
        expect($wordQuizzes)->toHaveCount(2);

        $first = $wordQuizzes[0];
        $second = $wordQuizzes[1];

        expect($first['quiz']['quiz_type'])->not->toBe($second['quiz']['quiz_type'])
            ->and(abs($second['step_index'] - $first['step_index']))->toBeGreaterThanOrEqual(4);
    }
});

test('each review quiz question has 4 options with counterpart fields and distractors from the same group', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $vocabs = Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);
    $allVocabIds = $vocabs->pluck('id')->all();

    $action = app(GenerateReviewQuizSession::class);
    $result = $action->handle($group);

    foreach ($result['steps'] as $quizStep) {
        $quiz = $quizStep['quiz'];
        $targetId = $quizStep['vocabulary']['id'];

        expect($quiz['correct_option_id'])->toBe($targetId)
            ->and($quiz['options'])->toHaveCount(4);

        $optionIds = collect($quiz['options'])->pluck('id')->all();
        expect($optionIds)->toContain($targetId)
            ->and(count(array_unique($optionIds)))->toBe(4);

        foreach ($optionIds as $optId) {
            expect($allVocabIds)->toContain($optId);
        }

        foreach ($quiz['options'] as $option) {
            expect($option)->toHaveKeys(['id', 'text', 'part_of_speech', 'word', 'definition'])
                ->and($option['text'])->not->toBeEmpty();
        }
    }
});
