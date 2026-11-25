<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use Tests\Browser\Concerns\InteractsWithGuestProgress;
use Tests\Browser\Concerns\PlaysLearningSessions;

uses(
    InteractsWithGuestProgress::class,
    PlaysLearningSessions::class,
);

describe('page loading and leave guards', function () {
    test('stage 1 intro page loads without javascript errors', function () {
        $level = Level::factory()->withTranslation('zh_TW', '基礎入門')->create();
        $group = Group::factory()->withVocabularies()->create(['level_id' => $level->id, 'sequence' => 1]);

        visit("/groups/{$group->id}/intro")
            ->assertSee($group->sequence)
            ->assertNoSmoke();
    });

    test('stage 1 intro page guards against leaving and browser back from the very first step', function () {
        $level = Level::factory()->withTranslation('zh_TW', '基礎入門')->create();
        $group = Group::factory()->withVocabularies()->create(['level_id' => $level->id, 'sequence' => 1]);

        $page = visit("/groups/{$group->id}/intro");

        $page
            ->assertSee('30')
            ->click('button[title="'.__('ui.intro.top_bar.leave').'"]')
            ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
            ->click(__('ui.intro.top_bar.leave_dialog_cancel'))
            ->assertDontSee(__('ui.intro.top_bar.leave_dialog_title'));

        $page->script('window.history.back()');

        $page
            ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
            ->assertNoSmoke();
    });

    test('group show page for passed group loads without javascript errors', function () {
        $user = loginAsUser();
        $group = Group::factory()->withVocabularies()->create();

        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'level_id' => $group->level_id,
            'group_id' => $group->id,
            'stage' => 1,
        ]);

        visit("/groups/{$group->id}")
            ->assertNoSmoke();
    });

    test('stage 2 review quiz page loads without javascript errors and guards against leaving', function () {
        $user = loginAsUser();
        $level = Level::factory()->withTranslation('zh_TW', '基礎入門')->create();
        $group = Group::factory()->withVocabularies()->create(['level_id' => $level->id, 'sequence' => 1]);

        LearningProgress::factory()->readyForReview()->create([
            'user_id' => $user->id,
            'level_id' => $group->level_id,
            'group_id' => $group->id,
        ]);

        visit("/groups/{$group->id}/quiz")
            ->assertSee('20')
            ->click('button[title="'.__('ui.intro.top_bar.leave').'"]')
            ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
            ->click(__('ui.intro.top_bar.leave_dialog_cancel'))
            ->assertDontSee(__('ui.intro.top_bar.leave_dialog_title'))
            ->assertNoSmoke();
    });
});

describe('stage 1 intro and quiz', function () {
    test('guest completes stage 1 intro and quiz with perfect score and persists progress to indexeddb', function () {
        $group = Group::factory()->withVocabularies()->create();

        $page = visit(route('groups.intro', ['group' => $group->id]));
        $page->assertNoSmoke();

        $this->completeStage1Session($page);

        $page
            ->assertSee('100 / 100')
            ->assertSee(__('ui.quiz.result.all_correct'))
            ->assertSee(__('ui.quiz.result.btn_back_level'))
            ->assertDontSee(__('ui.quiz.result.btn_dashboard'))
            ->assertPresent('[data-progress-saved="true"]');

        $record = $this->guestGroupProgress($page, $group);

        expect($record)->not->toBeNull()
            ->toMatchArray(['stage' => 1, 'last_score' => 100, 'level_id' => $group->level_id])
            ->and($record['next_review_at'])->not->toBeNull();

        $this->assertDatabaseCount('learning_progress', 0);

        $page
            ->click(__('ui.quiz.result.btn_done'))
            ->assertPathIs("/groups/{$group->id}")
            ->assertSee(__('ui.group_detail.status.cooldown'))
            ->assertSee(__('ui.group_detail.stage_indicator', ['stage' => 1]))
            ->assertNoSmoke();
    });

    test('member completes stage 1 intro and quiz with perfect score and persists progress to database', function () {
        $user = loginAsUser();
        $group = Group::factory()->withVocabularies()->create();

        $page = visit(route('groups.intro', ['group' => $group->id]));
        $page->assertNoSmoke();

        $this->completeStage1Session($page);

        $page
            ->assertSee('100 / 100')
            ->assertSee(__('ui.quiz.result.all_correct'))
            ->assertSee(__('ui.quiz.result.btn_dashboard'))
            ->assertDontSee(__('ui.quiz.result.btn_back_level'))
            ->assertPresent('[data-progress-saved="true"]');

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'stage' => 1,
            'last_score' => 100,
        ]);

        $progress = LearningProgress::query()
            ->where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->firstOrFail();

        expect($progress->next_review_at)->not->toBeNull()
            ->and($user->fresh()->current_streak)->toBe(1);
    });

    test('stage 1 quiz result score matches partial correct answers, lists mistakes, and shows failed state below 80',
        function () {
            $group = Group::factory()->withVocabularies()->create();

            $page = visit(route('groups.intro', ['group' => $group->id]));
            $page->assertNoSmoke();

            $this->completeStage1Session($page, wrongCount: 6);

            $page
                ->assertSee('70 / 100')
                ->assertSee(__('ui.quiz.result.title_failed'))
                ->assertSee(__('ui.quiz.result.failed_notice'))
                ->assertSee('本次錯誤單字回顧')
                ->assertSee('01')
                ->assertPresent('section[aria-label]')
                ->assertPresent('button[aria-label="'.__('ui.group_detail.play_word_audio').'"]')
                ->assertPresent('button[aria-label="'.__('ui.group_detail.play_sentence_audio').'"]')
                ->assertPresent('[data-progress-saved="true"]');

            expect($this->guestGroupProgress($page, $group))
                ->toMatchArray(['stage' => 0, 'last_score' => 70, 'next_review_at' => null]);
        });

    test('stage 1 quiz shows expected visual feedback for correct and wrong option selections', function () {
        $group = Group::factory()->withVocabularies()->create();

        visit(route('groups.intro', ['group' => $group->id]))
            ->assertNoSmoke()
            ->assertNotPresent('footer')
            ->assertSeeIn('@current-step', '1')
            ->assertEnabled('@continue-button')
            ->click('@continue-button')
            ->assertSeeIn('@current-step', '2')
            ->assertEnabled('@continue-button')
            ->click('@continue-button')
            ->assertSeeIn('@current-step', '3')
            ->assertDisabled('@continue-button')
            ->click('@correct-option')
            ->assertDisabled('@correct-option')
            ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
            ->assertEnabled('@continue-button')
            ->assertAttributeContains('@continue-button', 'class', 'bg-blue-600')
            ->click('@continue-button')
            ->assertSeeIn('@current-step', '4')
            ->assertDisabled('@continue-button')
            ->assertEnabled('@wrong-option')
            ->click('@wrong-option')
            ->assertDisabled('@wrong-option')
            ->assertAttributeContains('@wrong-option', 'class', 'border-red-600')
            ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
            ->assertEnabled('@continue-button')
            ->assertNoSmoke();
    });
});

describe('stage 2+ review quiz', function () {
    test('member completes stage 2 review quiz with perfect score and advances to stage 2 in database', function () {
        $user = loginAsUser();
        $group = Group::factory()->withVocabularies()->create();

        LearningProgress::factory()->readyForReview()->create([
            'user_id' => $user->id,
            'level_id' => $group->level_id,
            'group_id' => $group->id,
        ]);

        $page = visit(route('groups.quiz', ['group' => $group->id]));
        $page->assertNoSmoke();

        $this->completeReviewQuizSession($page);

        $page
            ->assertSee('100 / 100')
            ->assertSee(__('ui.quiz.result.all_correct'))
            ->assertSee(__('ui.quiz.result.btn_dashboard'))
            ->assertPresent('[data-progress-saved="true"]');

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'stage' => 2,
            'last_score' => 100,
        ]);

        $progress = LearningProgress::query()
            ->where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->firstOrFail();

        expect($progress->next_review_at)->not->toBeNull();
    });

    test('member stage 2 review quiz result score matches partial correct count and lists mistakes', function () {
        $user = loginAsUser();
        $group = Group::factory()->withVocabularies()->create();

        LearningProgress::factory()->readyForReview()->create([
            'user_id' => $user->id,
            'level_id' => $group->level_id,
            'group_id' => $group->id,
        ]);

        $page = visit(route('groups.quiz', ['group' => $group->id]));
        $page->assertNoSmoke();

        $this->completeReviewQuizSession($page, wrongCount: 4);

        $page
            ->assertSee('80 / 100')
            ->assertSee(__('ui.quiz.result.title_passed_stage', ['stage' => 2]))
            ->assertSee('本次錯誤單字回顧')
            ->assertSee('01')
            ->assertPresent('section[aria-label]')
            ->assertPresent('button[aria-label="'.__('ui.group_detail.play_word_audio').'"]')
            ->assertPresent('button[aria-label="'.__('ui.group_detail.play_sentence_audio').'"]')
            ->assertPresent('[data-progress-saved="true"]');

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'stage' => 2,
            'last_score' => 80,
        ]);
    });

    test('stage 2 review quiz shows expected visual feedback for correct and wrong option selections', function () {
        $user = loginAsUser();
        $group = Group::factory()->withVocabularies()->create();

        LearningProgress::factory()->readyForReview()->create([
            'user_id' => $user->id,
            'level_id' => $group->level_id,
            'group_id' => $group->id,
        ]);

        visit(route('groups.quiz', ['group' => $group->id]))
            ->assertNoSmoke()
            ->assertNotPresent('footer')
            ->assertSeeIn('@current-step', '1')
            ->assertDisabled('@continue-button')
            ->click('@correct-option')
            ->assertDisabled('@correct-option')
            ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
            ->assertEnabled('@continue-button')
            ->assertAttributeContains('@continue-button', 'class', 'bg-blue-600')
            ->click('@continue-button')
            ->assertSeeIn('@current-step', '2')
            ->assertDisabled('@continue-button')
            ->assertEnabled('@wrong-option')
            ->click('@wrong-option')
            ->assertDisabled('@wrong-option')
            ->assertAttributeContains('@wrong-option', 'class', 'border-red-600')
            ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
            ->assertEnabled('@continue-button')
            ->assertNoSmoke();
    });
});

describe('guest indexeddb progress and login sync', function () {
    test('guest without indexeddb progress visiting group detail or quiz is redirected to intro', function () {
        $group = Group::factory()->withVocabularies()->create();

        visit(route('groups.quiz', ['group' => $group->id]))
            ->assertPathIs("/groups/{$group->id}/intro")
            ->assertNoSmoke();

        visit(route('groups.show', ['group' => $group->id]))
            ->assertPathIs("/groups/{$group->id}/intro")
            ->assertNoSmoke();
    });

    test('guest with cooldown progress in indexeddb visiting quiz is redirected to group detail', function () {
        $group = Group::factory()->withVocabularies()->create();

        $page = visit('/');
        $this->seedGuestProgress($page, [
            $this->guestProgressRecord($group, stage: 1, lastReviewedAt: now()->subHour(),
                nextReviewAt: now()->addHours(11)),
        ]);

        $page->navigate(route('groups.quiz', ['group' => $group->id]))
            ->assertPathIs("/groups/{$group->id}")
            ->assertSee(__('ui.group_detail.status.cooldown'))
            ->assertNoSmoke();
    });

    test('guest with ready stage 1 progress in indexeddb completes stage 2 review quiz and updates indexeddb',
        function () {
            $group = Group::factory()->withVocabularies()->create();

            $page = visit('/');
            $this->seedGuestProgress($page, [
                $this->guestProgressRecord($group, stage: 1, lastScore: 85, nextReviewAt: now()->subMinute()),
            ]);

            $page->navigate(route('groups.quiz', ['group' => $group->id]))
                ->assertPathIs("/groups/{$group->id}/quiz")
                ->assertPresent('[data-progress-ready="true"]')
                ->assertTitleContains(__('ui.quiz.stage_badge', ['stage' => 2]))
                ->assertNoSmoke();

            $this->completeReviewQuizSession($page);

            $page
                ->assertSee('100 / 100')
                ->assertSee(__('ui.quiz.result.all_correct'))
                ->assertSee(__('ui.quiz.result.btn_back_level'))
                ->assertPresent('[data-progress-saved="true"]');

            $record = $this->guestGroupProgress($page, $group);

            expect($record)->not->toBeNull()
                ->toMatchArray(['stage' => 2, 'last_score' => 100])
                ->and($record['next_review_at'])->not->toBeNull();
        });

    test('guest at stage 5 in indexeddb fails stage 6 with score 85 and enters 7-day stepped cooldown', function () {
        $group = Group::factory()->withVocabularies()->create();

        $page = visit('/');
        $this->seedGuestProgress($page, [
            $this->guestProgressRecord($group, stage: 5, lastReviewedAt: now()->subDays(31),
                nextReviewAt: now()->subMinute()),
        ]);

        $page->navigate(route('groups.quiz', ['group' => $group->id]))
            ->assertPathIs("/groups/{$group->id}/quiz")
            ->assertPresent('[data-progress-ready="true"]')
            ->assertTitleContains(__('ui.quiz.stage_badge', ['stage' => 6]))
            ->assertNoSmoke();

        $this->completeReviewQuizSession($page, wrongCount: 3);

        $page
            ->assertSee('85 / 100')
            ->assertSee(__('ui.quiz.result.title_failed'))
            ->assertSee(__('ui.quiz.result.failed_notice_stage', [
                'stage' => 6,
                'threshold' => 90,
                'cooldown' => __('ui.time.days', ['count' => 7]),
            ]))
            ->assertDontSee(__('ui.quiz.result.btn_retry_stage', ['stage' => 6]))
            ->assertPresent('[data-progress-saved="true"]');

        $record = $this->guestGroupProgress($page, $group);

        expect($record)->not->toBeNull()
            ->toMatchArray(['stage' => 5, 'last_score' => 85])
            ->and($record['next_review_at'])->not->toBeNull();
    });

    test('guest with stage 6 mastered progress in indexeddb sees self-test mode on quiz page', function () {
        $group = Group::factory()->withVocabularies()->create();

        $page = visit('/');
        $this->seedGuestProgress($page, [
            $this->guestProgressRecord($group, stage: 6, lastScore: 95),
        ]);

        $page->navigate(route('groups.quiz', ['group' => $group->id]))
            ->assertPathIs("/groups/{$group->id}/quiz")
            ->assertPresent('[data-progress-ready="true"]')
            ->assertTitleContains(__('ui.quiz.self_test_badge'))
            ->assertNoSmoke();
    });

    test('guest indexeddb progress automatically syncs to database after logging in', function () {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        $page = visit('/login');
        $this->seedGuestProgress($page, [
            $this->guestProgressRecord($group, stage: 2, lastReviewedAt: now()->subHours(2),
                nextReviewAt: now()->addHours(22)),
        ]);

        $page
            ->fill('#email', $user->email)
            ->fill('#password', 'password')
            ->click('@login-button')
            ->assertPathIs('/dashboard')
            ->assertNoSmoke();

        $this->assertGuestProgressCount($page, 0);

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'stage' => 2,
            'last_score' => 90,
        ]);
    });

    test('guest indexeddb progress is discarded entirely after logging in when the server rejects it as invalid',
        function () {
            $user = User::factory()->create();
            $level = Level::factory()->create();
            $validGroup = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);
            $invalidGroup = Group::factory()->create(['level_id' => $level->id, 'sequence' => 2]);

            $page = visit('/login');
            $savedCount = $this->seedGuestProgress($page, [
                $this->guestProgressRecord($validGroup, stage: 2, lastReviewedAt: now()->subHours(2),
                    nextReviewAt: now()->addHours(22)),
                $this->guestProgressRecord($invalidGroup, stage: 7, lastReviewedAt: now()->subHours(2)),
            ]);

            expect($savedCount)->toBe(2);

            $page
                ->fill('#email', $user->email)
                ->fill('#password', 'password')
                ->click('@login-button')
                ->assertPathIs('/dashboard')
                ->assertNoSmoke();

            // A single invalid record rejects the whole batch, and the client then discards all local progress.
            $this->assertGuestProgressCount($page, 0);
            $this->assertDatabaseCount('learning_progress', 0);
        });
});
