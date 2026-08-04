<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\VocabularyTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Three groups of ten words, mirroring the curriculum's fixed group size, so that a learned pool
 * can be a strict subset of the catalogue and a sample a strict subset of that pool. Vocabulary ids
 * run 1-10 for group 1, 11-20 for group 2, and 21-30 for group 3.
 */
beforeEach(function () {
    Level::create(['id' => 1]);

    foreach (range(1, 3) as $sequence) {
        Group::create([
            'id' => $sequence,
            'level_id' => 1,
            'sequence' => $sequence,
        ]);

        foreach (range(1, 10) as $index) {
            $id = (($sequence - 1) * 10) + $index;

            Vocabulary::create([
                'id' => $id,
                'group_id' => $sequence,
                'word' => "word_{$id}",
            ]);

            VocabularyTranslation::create([
                'vocabulary_id' => $id,
                'locale' => 'zh_TW',
                'definition' => "def_{$id}",
            ]);
        }
    }
});

/**
 * @param  array<int, array{stage: int, last_score?: int|null, last_reviewed_at?: mixed}>  $progressByGroup
 */
function createLearnerWithProgress(array $progressByGroup = []): User
{
    $user = User::factory()->create();

    foreach ($progressByGroup as $groupId => $progress) {
        LearningProgress::create([
            'user_id' => $user->id,
            'group_id' => $groupId,
            'stage' => $progress['stage'],
            'last_score' => $progress['last_score'] ?? null,
            'last_reviewed_at' => $progress['last_reviewed_at'] ?? null,
        ]);
    }

    return $user;
}

it('tells a signed-in learner how many words they have learned', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 2, 'last_reviewed_at' => now()],
        2 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)->get('/quiz/custom')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('quiz/Custom')
            ->where('learned_word_count', 20)
        );
});

/**
 * A failed first attempt still records a review, and those are the words most worth practising, so
 * the group qualifies while its stage is still zero.
 */
it('counts a group whose only session was completed and failed', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 0, 'last_score' => 40, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)->get('/quiz/custom')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('learned_word_count', 10));
});

it('leaves out a group opened but abandoned before its first session finished', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 0, 'last_reviewed_at' => null],
    ]);

    $this->actingAs($user)->get('/quiz/custom')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('learned_word_count', 0));
});

it('leaves out groups that were never attempted', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 3, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)->get('/quiz/custom')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('learned_word_count', 10));
});

it('stops shipping the group catalogue to the page', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)->get('/quiz/custom')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('all_groups')
            ->missing('eligible_group_ids')
        );
});

it('samples exactly the requested number of distinct words', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
        2 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $targets = $this->actingAs($user)
        ->postJson('/quiz/custom/fetch', ['count' => 7])
        ->assertOk()
        ->json('targets');

    expect($targets)->toHaveCount(7)
        ->and(collect($targets)->pluck('id')->unique())->toHaveCount(7);
});

it('yields the whole pool when more words are requested than exist', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)
        ->postJson('/quiz/custom/fetch', ['count' => 500])
        ->assertOk()
        ->assertJsonCount(10, 'targets');
});

/**
 * The whole-pool option sends the key explicitly rather than omitting it, so both shapes have to
 * mean the same thing.
 */
it('yields the whole pool when the count is explicitly null', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)
        ->postJson('/quiz/custom/fetch', ['count' => null])
        ->assertOk()
        ->assertJsonCount(10, 'targets')
        ->assertJsonCount(0, 'distractors');
});

it('yields the whole pool when no count is given', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
        3 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)
        ->postJson('/quiz/custom/fetch')
        ->assertOk()
        ->assertJsonCount(20, 'targets');
});

it('derives a signed-in learner pool from their progress and ignores posted group ids', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $targets = $this->actingAs($user)
        ->postJson('/quiz/custom/fetch', ['group_ids' => [2, 3]])
        ->assertOk()
        ->json('targets');

    // Only group 1 was learned, so only its ten words may appear however the request was shaped.
    expect($targets)->toHaveCount(10)
        ->and(collect($targets)->pluck('id')->sort()->values()->all())->toBe(range(1, 10));
});

it('sends distractors drawn from the pool but never the sampled words themselves', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
        2 => ['stage' => 1, 'last_reviewed_at' => now()],
        3 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $body = $this->actingAs($user)
        ->postJson('/quiz/custom/fetch', ['count' => 5])
        ->assertOk()
        ->json();

    $targetIds = collect($body['targets'])->pluck('id');
    $distractorIds = collect($body['distractors'])->pluck('id');

    expect($distractorIds)->not->toBeEmpty()
        ->and($distractorIds->intersect($targetIds)->all())->toBeEmpty()
        ->and($distractorIds->diff(range(1, 30))->all())->toBeEmpty();
});

/**
 * Every word is already being tested, so there is nothing left in the pool to draw a wrong answer
 * from — the options for each question come from the sample itself.
 */
it('omits distractors when the whole pool was requested', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)
        ->postJson('/quiz/custom/fetch')
        ->assertOk()
        ->assertJsonCount(0, 'distractors');
});

/**
 * The practice quiz carries every locale so a language change mid-session retranslates in place,
 * but it carries nothing a question does not render.
 */
it('sends only what a question renders for each word', function () {
    VocabularyTranslation::create([
        'vocabulary_id' => 1,
        'locale' => 'ja',
        'definition' => 'ja_def_1',
        'example_translation' => 'ja_example_1',
    ]);

    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $word = collect(
        $this->actingAs($user)->postJson('/quiz/custom/fetch')->assertOk()->json('targets')
    )->firstWhere('id', 1);

    expect($word['word'])->toBe('word_1')
        ->and($word['audio_url'])->toBe('https://assets.vomorize.com/vocabulary/1/word.mp3')
        ->and($word['translations']['zh_TW']['definition'])->toBe('def_1')
        ->and($word['translations']['ja']['definition'])->toBe('ja_def_1')
        ->and($word['translations']['ja'])->not->toHaveKey('example_translation')
        ->and($word)->not->toHaveKey('part_of_speech')
        ->and($word)->not->toHaveKey('pronunciation')
        ->and($word)->not->toHaveKey('example_sentence')
        ->and($word)->not->toHaveKey('sentence_audio_url');
});

it('never touches learning progress', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_score' => 90, 'last_reviewed_at' => now()],
    ]);

    $before = LearningProgress::where('user_id', $user->id)->get()->toArray();

    $this->actingAs($user)->get('/quiz/custom')->assertOk();
    $this->actingAs($user)->postJson('/quiz/custom/fetch', ['count' => 5])->assertOk();

    expect(LearningProgress::where('user_id', $user->id)->get()->toArray())->toBe($before)
        ->and(LearningProgress::count())->toBe(1);
});

it('rejects a count below one', function () {
    $user = createLearnerWithProgress([
        1 => ['stage' => 1, 'last_reviewed_at' => now()],
    ]);

    $this->actingAs($user)
        ->postJson('/quiz/custom/fetch', ['count' => 0])
        ->assertJsonValidationErrors('count');
});

it('sizes a guest pool from the groups their browser declares', function () {
    $this->postJson('/quiz/custom/count', ['group_ids' => [1, 3]])
        ->assertOk()
        ->assertExactJson(['learned_word_count' => 20]);
});

it('reports nothing learned for a guest who declares no groups', function () {
    $this->postJson('/quiz/custom/count')
        ->assertOk()
        ->assertExactJson(['learned_word_count' => 0]);
});

it('draws a guest sample from the groups their browser declares', function () {
    $targets = $this->postJson('/quiz/custom/fetch', ['count' => 6, 'group_ids' => [2]])
        ->assertOk()
        ->json('targets');

    // Group 2 holds vocabulary 11 through 20.
    expect($targets)->toHaveCount(6)
        ->and(collect($targets)->pluck('id')->every(fn (int $id): bool => $id >= 11 && $id <= 20))
        ->toBeTrue();
});

/**
 * A browser can hold an id for a group that no longer exists. Practice is not the place to punish
 * that, so the unknown id is simply not part of the pool.
 */
it('still starts a session when a declared group no longer exists', function () {
    $this->postJson('/quiz/custom/fetch', ['group_ids' => [1, 999]])
        ->assertOk()
        ->assertJsonCount(10, 'targets');
});

it('does not run one query per declared group id', function () {
    $queriesFor = function (array $groupIds): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson('/quiz/custom/count', ['group_ids' => $groupIds])->assertOk();

        return count(DB::getQueryLog());
    };

    // An existence rule on each id would make the larger request hundreds of queries longer.
    expect($queriesFor(range(1, 700)))->toBe($queriesFor([1, 2, 3]));
});

it('rejects more declared groups than the curriculum holds', function () {
    $this->postJson('/quiz/custom/count', ['group_ids' => range(1, 701)])
        ->assertJsonValidationErrors('group_ids');
});

it('rejects a group id that is not an integer', function () {
    $this->postJson('/quiz/custom/count', ['group_ids' => ['not-a-number']])
        ->assertJsonValidationErrors('group_ids.0');
});

it('creates no learning progress for a guest session', function () {
    $this->postJson('/quiz/custom/count', ['group_ids' => [1]])->assertOk();
    $this->postJson('/quiz/custom/fetch', ['count' => 5, 'group_ids' => [1]])->assertOk();

    expect(LearningProgress::count())->toBe(0);
});
