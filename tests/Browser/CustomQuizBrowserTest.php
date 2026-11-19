<?php

declare(strict_types=1);

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\SentenceClozeQuestion;
use App\Models\User;
use App\Models\Vocabulary;

test('guest visits custom quiz page with zero unlocked words and sees empty state', function () {
    visit('/quiz')
        ->assertNoSmoke()
        ->assertSee('自訂測驗')
        ->assertSee('尚未解鎖自訂測驗')
        ->assertSee('前往單字等級開始學習');
});

test('clicking empty state action navigates to levels page', function () {
    visit('/quiz')
        ->assertNoSmoke()
        ->click('前往單字等級開始學習')
        ->assertPathIs('/levels')
        ->assertNoSmoke();
});

test('clicking custom quiz link in header navigates to custom quiz page', function () {
    visit('/levels')
        ->assertNoSmoke()
        ->click('header a:text-is("自訂測驗")')
        ->assertPathIs('/quiz')
        ->assertNoSmoke();
});

test('authenticated member with unlocked words sees word pool count and question options', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $groups = Group::factory()->count(2)->create(['level_id' => $level->id]);

    foreach ($groups as $group) {
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
    }

    $this->actingAs($user);

    visit('/quiz')
        ->assertNoSmoke()
        ->assertSee('自訂測驗')
        ->assertSee('已解鎖題庫')
        ->assertSee('20 個單字')
        ->assertSee('選擇測驗題數')
        ->assertSee('10 題')
        ->assertSee('20 題')
        ->assertSee('30 題')
        ->assertSee('還差 10 題解鎖')
        ->assertSee('一般')
        ->assertSee('看例句選出正確的單字')
        ->assertSee('看例句上下文語境挖空選單字')
        ->assertSee('拼音')
        ->assertSee('顯示中文意思與詞性')
        ->assertSee('開始自訂測驗');
});

test('authenticated member can select spelling mode and enter custom quiz room', function () {
    $user = User::factory()->create();
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

    $this->actingAs($user);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertSee('拼音')
        ->click('@mode-option-spelling')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'true')
        ->click('button:has-text("開始自訂測驗")')
        ->wait(2)
        ->assertNoSmoke()
        ->assertPathIs('/quiz/play')
        ->assertSee('拼音測驗')
        ->assertSee('退格')
        ->assertSee('清除');
});

test('authenticated member completing custom quiz displays result summary without error modals', function () {
    $user = User::factory()->create();
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

    $this->actingAs($user);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertSee('開始自訂測驗')
        ->click('button:has-text("開始自訂測驗")')
        ->wait(2)
        ->assertNoSmoke()
        ->assertPathIs('/quiz/play');

    for ($i = 1; $i <= 10; $i++) {
        $page
            ->assertSeeIn('@current-step', (string) $i)
            ->assertDisabled('@continue-button')
            ->click('button:has-text("1")')
            ->assertEnabled('@continue-button')
            ->click('@continue-button');
    }

    $page
        ->assertSee('自訂測驗完成！')
        ->assertDontSee('All Inertia requests must receive a valid Inertia response')
        ->assertNoSmoke();
});

it("can remember user's choices", function () {
    app()->setLocale('zh_TW');

    $user = loginAsUser();

    $level = Level::factory()->create();
    $groups = Group::factory()
        ->count(2)
        ->create([
            'level_id' => $level->id,
        ]);
    $user->learningProgress()->createMany([
        [
            'level_id' => $level->id,
            'group_id' => $groups[0]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
        [
            'level_id' => $level->id,
            'group_id' => $groups[1]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
    ]);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertDontSee('尚未解鎖自訂測驗')
        ->assertSee('選擇測驗題數')
        ->assertSee('測驗題型模式')
        ->assertAriaAttribute('@count-option-10', 'pressed', 'true')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'false')
        ->assertAriaAttribute('@mode-option-standard', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'false')
        ->click('@count-option-20')
        ->click('@mode-option-spelling')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'true')
        ->click('a:has-text("單字等級")')
        ->assertPathIs('/levels')
        ->assertNoSmoke()
        ->click('a:has-text("自訂測驗")')
        ->assertPathIs('/quiz')
        ->assertNoSmoke()
        ->assertAriaAttribute('@count-option-20', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'true');
});

it('falls back to 10 questions when switching to a user with only 10 unlocked words', function () {
    app()->setLocale('zh_TW');

    $level = Level::factory()->create();
    $groups = Group::factory()
        ->count(2)
        ->create([
            'level_id' => $level->id,
        ]);

    $userWith20Words = User::factory()->create([
        'name' => 'Twenty User',
    ]);
    $userWith20Words->learningProgress()->createMany([
        [
            'level_id' => $level->id,
            'group_id' => $groups[0]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
        [
            'level_id' => $level->id,
            'group_id' => $groups[1]->id,
            'stage' => 1,
            'last_score' => 90,
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subDay(),
        ],
    ]);

    $userWith10Words = User::factory()->create([
        'name' => 'Ten User',
    ]);
    $userWith10Words->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $groups[0]->id,
        'stage' => 1,
        'last_score' => 90,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subDay(),
    ]);

    loginAsUser($userWith20Words);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->assertSee('20 個單字')
        ->click('@count-option-20')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'true')
        ->click('header button:has-text("TU")')
        ->click('@logout-button')
        ->wait(1)
        ->click('header a:has-text("登入")')
        ->assertPathIs('/login')
        ->fill('#email', $userWith10Words->email)
        ->fill('#password', 'password')
        ->click('@login-button')
        ->wait(1)
        ->click('header a:has-text("自訂測驗")')
        ->assertPathIs('/quiz')
        ->assertNoSmoke()
        ->assertSee('10 個單字')
        ->assertAriaAttribute('@count-option-10', 'pressed', 'true')
        ->assertAriaAttribute('@count-option-20', 'pressed', 'false');
});

it('falls back to backend defaults when stored preferences are outside allowed ranges', function () {
    app()->setLocale('zh_TW');

    $user = loginAsUser();
    $level = Level::factory()->create();
    $group = Group::factory()->create([
        'level_id' => $level->id,
    ]);
    $user->learningProgress()->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 90,
        'last_reviewed_at' => now()->subDay(),
        'next_review_at' => now()->subDay(),
    ]);

    $page = visit('/quiz');
    $page->script(<<<'JS'
        localStorage.setItem('quiz_question_count', '999');
        localStorage.setItem('quiz_mode', 'unsupported_mode');
    JS);

    $page
        ->navigate('/quiz')
        ->assertNoSmoke()
        ->assertAriaAttribute('@count-option-10', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-standard', 'pressed', 'true')
        ->assertAriaAttribute('@mode-option-sentence-cloze', 'pressed', 'false')
        ->assertAriaAttribute('@mode-option-spelling', 'pressed', 'false');
});

test('authenticated member can select sentence cloze mode and play quiz with post-answer translation reveal', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $vocabularies = Vocabulary::factory()
        ->count(10)
        ->withTranslation('zh_TW', null, '團隊執行了這項計畫。')
        ->create([
            'level_id' => $level->id,
            'group_id' => $group->id,
        ]);

    foreach ($vocabularies as $index => $vocab) {
        $peers = $vocabularies
            ->filter(fn (Vocabulary $v): bool => $v->id !== $vocab->id)
            ->take(4)
            ->values();

        SentenceClozeQuestion::factory()->create([
            'vocabulary_id' => $vocab->id,
            'sentence' => "The team {{blank}} the plan for task {$index}.",
            'blank_word' => $vocab->word.'ed',
            'distractors' => $peers->map(fn (Vocabulary $peer): array => [
                'vocabulary_id' => $peer->id,
                'word' => $peer->word.'ed',
            ])->all(),
        ]);
    }

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
    ]);

    $this->actingAs($user);

    $page = visit('/quiz');
    $page
        ->assertNoSmoke()
        ->click('@mode-option-sentence-cloze')
        ->assertAriaAttribute('@mode-option-sentence-cloze', 'pressed', 'true')
        ->click('button:has-text("開始自訂測驗")')
        ->wait(2)
        ->assertNoSmoke()
        ->assertPathIs('/quiz/play')
        ->assertSee('看例句選字')
        ->assertSee('_____')
        ->assertMissing('@disable-listening-button')
        ->assertAttribute('[data-test="sentence-cloze-reveal"]', 'aria-hidden', 'true')
        ->click('button:has-text("1")')
        ->assertAttribute('[data-test="sentence-cloze-reveal"]', 'aria-hidden', 'false')
        ->assertSee('團隊執行了這項計畫。');
});

test('selecting 10 or 20 questions starts a custom quiz session with matching question count', function () {
    //
})->skip('Practice: verify /quiz/play top progress bar shows 1 / 10 or 1 / 20 according to selection');

test('member completes standard custom quiz with perfect score using correct-option and sees 100/100 result', function () {
    //
})->skip('Practice: add data-test="correct-option" in QuizOptions.svelte, answer all 10 questions correctly, and assert 100 / 100');

test('member completes sentence cloze custom quiz with perfect score using correct-option', function () {
    //
})->skip('Practice: complete 10 sentence_cloze questions via @correct-option and verify 100 / 100 result');

test('member completes spelling custom quiz with perfect score and sees 100/100 result', function () {
    //
})->skip('Practice: complete 10 spelling questions by clicking matching letter tiles and verify 100 / 100 result');

test('custom quiz result summary matches partial correct count and lists wrong answers', function () {
    //
})->skip('Practice: mix @wrong-option (3x) and @correct-option (7x), then assert 70 / 100, 7 / 10, and 3 mistakes');

test('custom quiz shows expected visual feedback when selecting correct option', function () {
    //
})->skip('Practice: assert correct-option feedback state (or fixed-seed assertScreenshotMatches())');

test('custom quiz shows expected visual feedback when selecting wrong option', function () {
    //
})->skip('Practice: assert wrong-option & highlighted correct-option feedback state (or fixed-seed assertScreenshotMatches())');
