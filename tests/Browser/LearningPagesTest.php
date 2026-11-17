<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;

if (! function_exists('seedGuestLearningProgress')) {
    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    function seedGuestLearningProgress(object $page, array $records): void
    {
        $json = json_encode($records, JSON_THROW_ON_ERROR);

        $page->script(<<<JS
            async () => {
                const records = {$json};
                await new Promise((resolve, reject) => {
                    const request = window.indexedDB.open('vomorize', 1);
                    request.onupgradeneeded = () => {
                        const db = request.result;
                        if (!db.objectStoreNames.contains('learning_progress')) {
                            const store = db.createObjectStore('learning_progress', { keyPath: 'group_id' });
                            store.createIndex('level_id', 'level_id', { unique: false });
                            store.createIndex('stage', 'stage', { unique: false });
                        }
                    };
                    request.onsuccess = () => {
                        const db = request.result;
                        const transaction = db.transaction('learning_progress', 'readwrite');
                        const store = transaction.objectStore('learning_progress');
                        for (const record of records) {
                            store.put(record);
                        }
                        transaction.oncomplete = () => {
                            db.close();
                            resolve(true);
                        };
                        transaction.onerror = () => reject(transaction.error);
                    };
                    request.onerror = () => reject(request.error);
                });
            }
        JS);
    }
}

if (! function_exists('completeStage1Session')) {
    function completeStage1Session(object $page, int $wrongCount = 0): void
    {
        $introSteps = [1, 2, 5, 6, 11, 12, 17, 18, 23, 24];
        $quizIndex = 0;
        $correctTarget = 20 - $wrongCount;

        $page->assertNotPresent('footer');

        for ($step = 1; $step <= 30; $step++) {
            $page->assertSeeIn('@current-step', (string) $step);

            if (in_array($step, $introSteps, true)) {
                $page->click('@continue-button');
            } else {
                $quizIndex++;
                $selector = $quizIndex <= $correctTarget ? '@correct-option' : '@wrong-option';
                $page->assertEnabled($selector)->click($selector);
            }
        }
    }
}

if (! function_exists('completeReviewQuizSession')) {
    function completeReviewQuizSession(object $page, int $wrongCount = 0): void
    {
        $correctTarget = 20 - $wrongCount;

        $page->assertNotPresent('footer');

        for ($step = 1; $step <= 20; $step++) {
            $selector = $step <= $correctTarget ? '@correct-option' : '@wrong-option';

            $page->assertSeeIn('@current-step', (string) $step)
                ->assertEnabled($selector)
                ->click($selector);
        }
    }
}

if (! function_exists('waitUntilCondition')) {
    function waitUntilCondition(callable $condition, int $timeoutMs = 5000): void
    {
        $deadline = microtime(true) + ($timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            if ($condition()) {
                return;
            }

            usleep(50_000);
        }

        expect($condition())->toBeTrue();
    }
}

test('stage 1 intro page loads without javascript errors', function () {
    $level = Level::factory()->withTranslation('zh_TW', __('ui.dashboard.levels.subtitles.1'))->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    visit("/groups/{$group->id}/intro")
        ->assertSee($group->sequence)
        ->assertNoSmoke();
});

test('stage 1 intro page guards against leaving and browser back from the very first step', function () {
    $level = Level::factory()->withTranslation('zh_TW', __('ui.dashboard.levels.subtitles.1'))->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    $page = visit("/groups/{$group->id}/intro");

    $page
        ->assertSee('30')
        ->click('button[title="'.__('ui.intro.top_bar.leave').'"]')
        ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->click(__('ui.intro.top_bar.leave_dialog_cancel'))
        ->assertDontSee(__('ui.intro.top_bar.leave_dialog_title'));

    $page->script('window.history.back()');

    $page->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->assertNoSmoke();
});

test('group show page for passed group loads without javascript errors', function () {
    $user = loginAsUser();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    visit("/groups/{$group->id}")
        ->assertNoSmoke();
});

test('stage 2 review quiz page loads without javascript errors and guards against leaving', function () {
    $user = loginAsUser();
    $level = Level::factory()->withTranslation('zh_TW', __('ui.dashboard.levels.subtitles.1'))->create();
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()
        ->count(10)
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
        'next_review_at' => now()->subMinute(),
    ]);

    $page = visit("/groups/{$group->id}/quiz");

    $page
        ->assertSee('20')
        ->click('button[title="'.__('ui.intro.top_bar.leave').'"]')
        ->assertSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->click(__('ui.intro.top_bar.leave_dialog_cancel'))
        ->assertDontSee(__('ui.intro.top_bar.leave_dialog_title'))
        ->assertNoSmoke();
});

test('guest completes stage 1 intro and quiz with perfect score and persists progress to indexeddb', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit(route('groups.intro', ['group' => $group->id]));
    $page->assertNoSmoke();

    completeStage1Session($page);

    $page
        ->assertSee('100 / 100')
        ->assertSee(__('ui.quiz.result.all_correct'))
        ->assertSee(__('ui.quiz.result.btn_back_level'))
        ->assertDontSee(__('ui.quiz.result.btn_dashboard'))
        ->assertScript(<<<JS
            async () => {
                const record = await new Promise((resolve) => {
                    const req = window.indexedDB.open('vomorize', 1);
                    req.onsuccess = () => {
                        const db = req.result;
                        const tx = db.transaction('learning_progress', 'readonly');
                        const getReq = tx.objectStore('learning_progress').get({$group->id});
                        getReq.onsuccess = () => resolve(getReq.result ?? null);
                        getReq.onerror = () => resolve(null);
                    };
                    req.onerror = () => resolve(null);
                });
                return record !== null && record.stage === 1 && record.last_score === 100 && record.level_id === {$level->id} && record.next_review_at !== null;
            }
        JS);

    expect(LearningProgress::query()->count())->toBe(0);

    $page
        ->click(__('ui.quiz.result.btn_done'))
        ->assertPathIs("/groups/{$group->id}")
        ->assertSee(__('ui.group_detail.status.cooldown'))
        ->assertSee(__('ui.group_detail.stage_indicator', ['stage' => 1]))
        ->assertNoSmoke();
});

test('member completes stage 1 intro and quiz with perfect score and persists progress to database', function () {
    $user = loginAsUser();
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit(route('groups.intro', ['group' => $group->id]));
    $page->assertNoSmoke();

    completeStage1Session($page);

    $page
        ->assertSee('100 / 100')
        ->assertSee(__('ui.quiz.result.all_correct'))
        ->assertSee(__('ui.quiz.result.btn_dashboard'))
        ->assertDontSee(__('ui.quiz.result.btn_back_level'));

    waitUntilCondition(fn (): bool => LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->where('stage', 1)
        ->exists());

    $progress = LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->firstOrFail();

    expect($progress->stage)->toBe(1)
        ->and($progress->last_score)->toBe(100)
        ->and($progress->next_review_at)->not->toBeNull();

    $user->refresh();
    expect($user->current_streak)->toBe(1);
});

test('stage 1 quiz result score matches partial correct answers, lists mistakes, and shows failed state below 80', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit(route('groups.intro', ['group' => $group->id]));
    $page->assertNoSmoke();

    completeStage1Session($page, wrongCount: 6);

    $page
        ->assertSee('70 / 100')
        ->assertSee(__('ui.quiz.result.title_failed'))
        ->assertSee(__('ui.quiz.result.failed_notice'))
        ->assertSee('本次錯誤單字回顧')
        ->assertScript(<<<JS
            async () => {
                const record = await new Promise((resolve) => {
                    const req = window.indexedDB.open('vomorize', 1);
                    req.onsuccess = () => {
                        const db = req.result;
                        const tx = db.transaction('learning_progress', 'readonly');
                        const getReq = tx.objectStore('learning_progress').get({$group->id});
                        getReq.onsuccess = () => resolve(getReq.result ?? null);
                        getReq.onerror = () => resolve(null);
                    };
                    req.onerror = () => resolve(null);
                });
                return record !== null && record.stage === 0 && record.last_score === 70 && record.next_review_at === null;
            }
        JS);
});

test('stage 1 quiz shows expected visual feedback for correct and wrong option selections', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit(route('groups.intro', ['group' => $group->id]));

    $page->assertNoSmoke()
        ->assertNotPresent('footer')
        ->assertSeeIn('@current-step', '1')
        ->click('@continue-button')
        ->assertSeeIn('@current-step', '2')
        ->click('@continue-button')
        ->assertSeeIn('@current-step', '3')
        ->click('@correct-option')
        ->assertDisabled('@correct-option')
        ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
        ->assertSeeIn('@current-step', '4')
        ->assertEnabled('@wrong-option')
        ->click('@wrong-option')
        ->assertDisabled('@wrong-option')
        ->assertAttributeContains('@wrong-option', 'class', 'border-red-600')
        ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
        ->assertNoSmoke();
});

test('member completes stage 2 review quiz with perfect score and advances to stage 2 in database', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $user = loginAsUser();

    $user->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subMinute(),
    ]);

    $page = visit(route('groups.quiz', [
        'group' => $group->id,
    ]));
    $page->assertNoSmoke();

    completeReviewQuizSession($page);

    $page
        ->assertSee('100 / 100')
        ->assertSee(__('ui.quiz.result.all_correct'))
        ->assertSee(__('ui.quiz.result.btn_dashboard'));

    waitUntilCondition(fn (): bool => LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->where('stage', 2)
        ->exists());

    $progress = LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->firstOrFail();

    expect($progress->stage)->toBe(2)
        ->and($progress->last_score)->toBe(100)
        ->and($progress->next_review_at)->not->toBeNull();
});

test('member stage 2 review quiz result score matches partial correct count and lists mistakes', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $user = loginAsUser();

    $user->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subMinute(),
    ]);

    $page = visit(route('groups.quiz', [
        'group' => $group->id,
    ]));
    $page->assertNoSmoke();

    completeReviewQuizSession($page, wrongCount: 4);

    $page
        ->assertSee('80 / 100')
        ->assertSee(__('ui.quiz.result.title_passed_stage', ['stage' => 2]))
        ->assertSee('本次錯誤單字回顧');

    waitUntilCondition(fn (): bool => LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->where('stage', 2)
        ->exists());

    $progress = LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->firstOrFail();

    expect($progress->last_score)->toBe(80);
});

test('stage 2 review quiz shows expected visual feedback for correct and wrong option selections', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $user = loginAsUser();

    $user->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subMinute(),
    ]);

    $page = visit(route('groups.quiz', [
        'group' => $group->id,
    ]));

    $page->assertNoSmoke()
        ->assertNotPresent('footer')
        ->assertSeeIn('@current-step', '1')
        ->click('@correct-option')
        ->assertDisabled('@correct-option')
        ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
        ->assertSeeIn('@current-step', '2')
        ->assertEnabled('@wrong-option')
        ->click('@wrong-option')
        ->assertDisabled('@wrong-option')
        ->assertAttributeContains('@wrong-option', 'class', 'border-red-600')
        ->assertAttributeContains('@correct-option', 'class', 'border-emerald-600')
        ->assertNoSmoke();
});

test('guest without indexeddb progress visiting group detail or quiz is redirected to intro', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    visit(route('groups.quiz', ['group' => $group->id]))
        ->assertPathIs("/groups/{$group->id}/intro")
        ->assertNoSmoke();

    visit(route('groups.show', ['group' => $group->id]))
        ->assertPathIs("/groups/{$group->id}/intro")
        ->assertNoSmoke();
});

test('guest with cooldown progress in indexeddb visiting quiz is redirected to group detail', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit('/');

    seedGuestLearningProgress($page, [
        [
            'group_id' => $group->id,
            'level_id' => $level->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subHour()->toIso8601String(),
            'next_review_at' => now()->addHours(11)->toIso8601String(),
        ],
    ]);

    $page->navigate(route('groups.quiz', ['group' => $group->id]))
        ->assertPathIs("/groups/{$group->id}")
        ->assertSee(__('ui.group_detail.status.cooldown'))
        ->assertNoSmoke();
});

test('guest with ready stage 1 progress in indexeddb completes stage 2 review quiz and updates indexeddb', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit('/');

    seedGuestLearningProgress($page, [
        [
            'group_id' => $group->id,
            'level_id' => $level->id,
            'stage' => 1,
            'last_score' => 85,
            'last_reviewed_at' => now()->subDay()->toIso8601String(),
            'next_review_at' => now()->subMinute()->toIso8601String(),
        ],
    ]);

    $page->navigate(route('groups.quiz', ['group' => $group->id]))
        ->assertPathIs("/groups/{$group->id}/quiz")
        ->assertPresent('[data-progress-ready="true"]')
        ->assertTitleContains(__('ui.quiz.stage_badge', ['stage' => 2]))
        ->assertNoSmoke();

    completeReviewQuizSession($page);

    $page
        ->assertSee('100 / 100')
        ->assertSee(__('ui.quiz.result.all_correct'))
        ->assertSee(__('ui.quiz.result.btn_back_level'))
        ->assertScript(<<<JS
            async () => {
                const record = await new Promise((resolve) => {
                    const req = window.indexedDB.open('vomorize', 1);
                    req.onsuccess = () => {
                        const db = req.result;
                        const tx = db.transaction('learning_progress', 'readonly');
                        const getReq = tx.objectStore('learning_progress').get({$group->id});
                        getReq.onsuccess = () => resolve(getReq.result ?? null);
                        getReq.onerror = () => resolve(null);
                    };
                    req.onerror = () => resolve(null);
                });
                return record !== null && record.stage === 2 && record.last_score === 100 && record.next_review_at !== null;
            }
        JS);
});

test('guest at stage 5 in indexeddb fails stage 6 with score 85 and retains stage 5', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit('/');

    seedGuestLearningProgress($page, [
        [
            'group_id' => $group->id,
            'level_id' => $level->id,
            'stage' => 5,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDays(8)->toIso8601String(),
            'next_review_at' => now()->subMinute()->toIso8601String(),
        ],
    ]);

    $page->navigate(route('groups.quiz', ['group' => $group->id]))
        ->assertPathIs("/groups/{$group->id}/quiz")
        ->assertPresent('[data-progress-ready="true"]')
        ->assertTitleContains(__('ui.quiz.stage_badge', ['stage' => 6]))
        ->assertNoSmoke();

    completeReviewQuizSession($page, wrongCount: 3);

    $page
        ->assertSee('85 / 100')
        ->assertSee(__('ui.quiz.result.title_failed'))
        ->assertSee(__('ui.quiz.result.failed_notice_stage', ['stage' => 6, 'threshold' => 90]))
        ->assertScript(<<<JS
            async () => {
                const record = await new Promise((resolve) => {
                    const req = window.indexedDB.open('vomorize', 1);
                    req.onsuccess = () => {
                        const db = req.result;
                        const tx = db.transaction('learning_progress', 'readonly');
                        const getReq = tx.objectStore('learning_progress').get({$group->id});
                        getReq.onsuccess = () => resolve(getReq.result ?? null);
                        getReq.onerror = () => resolve(null);
                    };
                    req.onerror = () => resolve(null);
                });
                return record !== null && record.stage === 5 && record.last_score === 85 && record.next_review_at === null;
            }
        JS);
});

test('guest with stage 6 mastered progress in indexeddb sees self-test mode on quiz page', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $page = visit('/');

    seedGuestLearningProgress($page, [
        [
            'group_id' => $group->id,
            'level_id' => $level->id,
            'stage' => 6,
            'last_score' => 95,
            'last_reviewed_at' => now()->subDay()->toIso8601String(),
            'next_review_at' => null,
        ],
    ]);

    $page->navigate(route('groups.quiz', ['group' => $group->id]))
        ->assertPathIs("/groups/{$group->id}/quiz")
        ->assertPresent('[data-progress-ready="true"]')
        ->assertTitleContains(__('ui.quiz.self_test_badge'))
        ->assertNoSmoke();
});

test('guest indexeddb progress automatically syncs to database after logging in', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $page = visit('/login');

    seedGuestLearningProgress($page, [
        [
            'group_id' => $group->id,
            'level_id' => $level->id,
            'stage' => 2,
            'last_score' => 90,
            'last_reviewed_at' => now()->subHours(2)->toIso8601String(),
            'next_review_at' => now()->addHours(22)->toIso8601String(),
        ],
    ]);

    $page
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->assertPathIs('/dashboard')
        ->assertNoSmoke()
        ->assertScript(<<<'JS'
            async () => {
                const count = await new Promise((resolve) => {
                    const req = window.indexedDB.open('vomorize', 1);
                    req.onsuccess = () => {
                        const db = req.result;
                        const tx = db.transaction('learning_progress', 'readonly');
                        const countReq = tx.objectStore('learning_progress').count();
                        countReq.onsuccess = () => resolve(countReq.result);
                        countReq.onerror = () => resolve(-1);
                    };
                    req.onerror = () => resolve(-1);
                });
                return count === 0;
            }
        JS);

    waitUntilCondition(fn (): bool => LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->exists());

    $progress = LearningProgress::query()
        ->where('user_id', $user->id)
        ->where('group_id', $group->id)
        ->firstOrFail();

    expect($progress->stage)->toBe(2)
        ->and($progress->last_score)->toBe(90);
});

test('stage 1 intro card hides keyboard shortcut hint on mobile and shows it on desktop', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    visit(route('groups.intro', ['group' => $group->id]))
        ->on()
        ->mobile()
        ->assertSee(__('ui.intro.card.badge'))
        ->assertDontSee(__('ui.intro.card.keyboard_hint'))
        ->assertNoSmoke();

    visit(route('groups.intro', ['group' => $group->id]))
        ->on()
        ->desktop()
        ->assertSee(__('ui.intro.card.badge'))
        ->assertSee(__('ui.intro.card.keyboard_hint'))
        ->assertNoSmoke();
});

test('stage 1 and stage 2 quiz cards hide keyboard hints on mobile', function () {
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    // Stage 1 quiz card (guest)
    visit(route('groups.intro', ['group' => $group->id]))
        ->on()
        ->mobile()
        ->assertNotPresent('footer')
        ->assertSeeIn('@current-step', '1')
        ->click('@continue-button')
        ->assertSeeIn('@current-step', '2')
        ->click('@continue-button')
        ->assertSeeIn('@current-step', '3')
        ->assertDontSee(__('ui.quiz.options.keyboard_hint'))
        ->assertNoSmoke();

    visit(route('groups.intro', ['group' => $group->id]))
        ->on()
        ->desktop()
        ->assertNotPresent('footer')
        ->assertSeeIn('@current-step', '1')
        ->click('@continue-button')
        ->assertSeeIn('@current-step', '2')
        ->click('@continue-button')
        ->assertSeeIn('@current-step', '3')
        ->assertSee(__('ui.quiz.options.keyboard_hint'))
        ->assertNoSmoke();

    // Stage 2 quiz card (authenticated)
    $user = loginAsUser();

    $user->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subMinute(),
    ]);

    visit(route('groups.quiz', ['group' => $group->id]))
        ->on()
        ->mobile()
        ->assertDontSee(__('ui.quiz.options.keyboard_hint'))
        ->assertNoSmoke();

    visit(route('groups.quiz', ['group' => $group->id]))
        ->on()
        ->desktop()
        ->assertSee(__('ui.quiz.options.keyboard_hint'))
        ->assertNoSmoke();
});
