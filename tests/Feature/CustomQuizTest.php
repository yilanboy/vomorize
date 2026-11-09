<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest accessing custom quiz setup page receives null counts', function () {
    $response = $this->get(route('quiz.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('quiz/Index')
        ->where('passedGroupCount', null)
        ->where('unlockedWordCount', null)
    );
});

test('member with no passed groups receives 0 unlocked words on setup page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('quiz.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('quiz/Index')
        ->where('passedGroupCount', 0)
        ->where('unlockedWordCount', 0)
    );
});

test('member with passed groups receives correct unlocked word count', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $groups = Group::factory()->count(3)->create(['level_id' => $level->id]);

    // 2 groups passed stage 1, 1 group still at stage 0
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[1]->id,
        'stage' => 3,
    ]);
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $groups[2]->id,
        'stage' => 0,
    ]);

    $response = $this->actingAs($user)->get(route('quiz.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('quiz/Index')
        ->where('passedGroupCount', 2)
        ->where('unlockedWordCount', 20)
    );
});

test('get request to quiz play without session config redirects to quiz setup', function () {
    $this->get(route('quiz.play'))->assertRedirect(route('quiz.index'));
});

test('custom quiz rejects invalid question count', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('quiz.start'), [
        'question_count' => 15,
    ]);

    $response->assertSessionHasErrors('question_count');
});

test('custom quiz redirects to setup when member has insufficient unlocked words', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    // 1 group passed = 10 words, but requests 20 questions
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $response = $this->actingAs($user)->post(route('quiz.start'), [
        'question_count' => 20,
    ]);

    $response->assertRedirect(route('quiz.index'));
});

test('member can start custom quiz and receives session with same-group distractors', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $groups = Group::factory()->count(2)->create(['level_id' => $level->id]);

    foreach ($groups as $group) {
        Vocabulary::factory()->count(10)->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'level_id' => $level->id,
            'group_id' => $group->id,
            'stage' => 1,
        ]);
    }

    $startResponse = $this->actingAs($user)->post(route('quiz.start'), [
        'question_count' => 10,
    ]);

    $startResponse->assertRedirect(route('quiz.play'));

    $response = $this->actingAs($user)->get(route('quiz.play'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('quiz/Show')
        ->where('questionCount', 10)
        ->has('session.steps', 10)
        ->has('session.correct_audio_url')
        ->has('session.wrong_audio_url')
    );

    // Verify properties of steps
    $steps = $response->viewData('page')['props']['session']['steps'];
    expect($steps)->toHaveCount(10);

    $testedWordIds = [];
    foreach ($steps as $step) {
        $quiz = $step['quiz'];
        expect($quiz['options'])->toHaveCount(4);

        $targetWordId = $step['vocabulary']['id'];
        expect($testedWordIds)->not->toContain($targetWordId);
        $testedWordIds[] = $targetWordId;
        expect($quiz['correct_option_id'])->toBe($targetWordId);

        // Verify distractors come from the same group
        $targetGroupId = $step['vocabulary']['group_id'];
        $distractorVocabularies = Vocabulary::whereIn('id', array_column($quiz['options'], 'id'))->get();
        expect($distractorVocabularies)->toHaveCount(4);
        foreach ($distractorVocabularies as $distractor) {
            expect($distractor->group_id)->toBe($targetGroupId);
        }
    }
});

test('guest can start custom quiz by providing passed_group_ids', function () {
    $level = Level::factory()->create();
    $groups = Group::factory()->count(2)->create(['level_id' => $level->id]);

    foreach ($groups as $group) {
        Vocabulary::factory()->count(10)->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);
    }

    $startResponse = $this->post(route('quiz.start'), [
        'question_count' => 10,
        'passed_group_ids' => $groups->pluck('id')->all(),
    ]);

    $startResponse->assertRedirect(route('quiz.play'));

    $response = $this->get(route('quiz.play'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('quiz/Show')
        ->where('questionCount', 10)
        ->has('session.steps', 10)
    );
});

test('completing custom quiz updates daily streak for logged in user and redirects back', function () {
    Carbon::setTestNow('2026-11-09 10:00:00');

    $user = User::factory()->create([
        'current_streak' => 0,
        'longest_streak' => 0,
        'last_studied_date' => null,
    ]);

    $response = $this->actingAs($user)
        ->from(route('quiz.play'))
        ->post(route('quiz.complete'));

    $response->assertRedirect(route('quiz.play'));

    $user->refresh();
    expect($user->current_streak)->toBe(1)
        ->and($user->longest_streak)->toBe(1)
        ->and($user->last_studied_date?->toDateString())->toBe('2026-11-09');

    // Guest completing also redirects back without errors
    $guestResponse = $this->from(route('quiz.play'))->post(route('quiz.complete'));
    $guestResponse->assertRedirect(route('quiz.play'));
});
