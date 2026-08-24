<?php

namespace App\Http\Controllers;

use App\Models\LearningProgress;
use App\Models\Vocabulary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomQuizController extends Controller
{
    /**
     * Extra words sent to be wrong answers and nothing else.
     *
     * Without them a short quiz fills every option slot from the very words it is testing, and by
     * the last question the remaining options give the answer away.
     */
    private const DISTRACTOR_POOL_SIZE = 20;

    /**
     * The most groups a request may declare, which is the whole curriculum: seven levels of a
     * hundred. A learner cannot have learned more than exists.
     */
    private const MAX_DECLARED_GROUPS = 700;

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('quiz/Custom', [
            // A guest's learned groups live only in their browser, so the server cannot size their
            // pool. Null says "unknown here", which is the page's cue to resolve it for itself.
            'learned_word_count' => $user
                ? Vocabulary::whereIn('group_id', $this->learnedGroupIds($user->id))->count()
                : null,
        ]);
    }

    /**
     * How many words a guest has learned.
     *
     * Signed-in learners get this in their page props, but a guest's progress lives only in their
     * browser, so the size of their pool can only be answered once they say which groups they have
     * finished.
     */
    public function learnedWordCount(Request $request): JsonResponse
    {
        $request->validate($this->declaredPoolRules());

        return response()->json([
            'learned_word_count' => Vocabulary::whereIn('group_id', $this->poolGroupIds($request))->count(),
        ]);
    }

    public function fetchVocabulary(Request $request): JsonResponse
    {
        $validated = $request->validate(array_merge(
            ['count' => ['nullable', 'integer', 'min:1']],
            $this->declaredPoolRules(),
        ));

        $count = $validated['count'] ?? null;
        $groupIds = $this->poolGroupIds($request);

        // A limit above the row count simply returns every row, so a request for more words than
        // the learner has clamps to their pool without a separate check.
        $targets = $this->poolQuery($groupIds)
            ->when($count !== null, fn (Builder $query) => $query->limit($count))
            ->get();

        $audioBaseUrl = rtrim(config('services.audio.base_url', 'https://assets.vomorize.com'), '/');

        return response()->json([
            'targets' => $this->shapeForQuestions($targets, $audioBaseUrl),
            'distractors' => $this->shapeForQuestions(
                $this->distractorsFor($groupIds, $targets->pluck('id'), $count),
                $audioBaseUrl,
            ),
        ]);
    }

    /**
     * Words the learner is not being asked about, drawn from the same pool, for the option slots
     * the sampled words cannot fill on their own.
     *
     * When the whole pool was requested there is nothing left over — every word is already in play,
     * so each question's options come from the sample itself.
     *
     * @param  Collection<int, int>  $groupIds
     * @param  Collection<int, int>  $targetIds
     * @return Collection<int, Vocabulary>
     */
    private function distractorsFor(Collection $groupIds, Collection $targetIds, ?int $count): Collection
    {
        if ($count === null) {
            return new Collection;
        }

        return $this->poolQuery($groupIds)
            ->whereNotIn('id', $targetIds)
            ->limit(self::DISTRACTOR_POOL_SIZE)
            ->get();
    }

    /**
     * @param  Collection<int, int>  $groupIds
     * @return Builder<Vocabulary>
     */
    private function poolQuery(Collection $groupIds): Builder
    {
        return Vocabulary::with('translations')
            ->whereIn('group_id', $groupIds)
            ->inRandomOrder();
    }

    /**
     * Rules for the groups a request may declare.
     *
     * Deliberately no existence rule on each id: that fires one query per element, and a guest who
     * has learned hundreds of groups sends hundreds of ids. Set membership already ignores an id
     * that matches nothing, so a browser holding an id for a group that no longer exists still gets
     * a working session rather than a rejected request.
     *
     * @return array<string, array<int, string>>
     */
    private function declaredPoolRules(): array
    {
        return [
            'group_ids' => ['nullable', 'array', 'max:'.self::MAX_DECLARED_GROUPS],
            'group_ids.*' => ['integer'],
        ];
    }

    /**
     * The groups a request draws from.
     *
     * A signed-in learner's pool comes from their stored progress, so any group ids the request
     * carries are ignored for them — there is a trustworthy answer on this side.
     *
     * A guest's progress exists only in their browser, so their pool is whatever they declare.
     * Nothing here can verify it, and nothing needs to: these endpoints expose only curriculum
     * content that is already public to everyone, and write no progress.
     *
     * @return Collection<int, int>
     */
    private function poolGroupIds(Request $request): Collection
    {
        $user = $request->user();

        if ($user) {
            return $this->learnedGroupIds($user->id);
        }

        return (new Collection($request->input('group_ids', [])))
            ->map(fn (mixed $groupId): int => (int) $groupId);
    }

    /**
     * Groups where the learner finished at least one session.
     *
     * Every persisted learning progress record represents a completed session.
     *
     * @return Collection<int, int>
     */
    private function learnedGroupIds(int $userId): Collection
    {
        return LearningProgress::where('user_id', $userId)
            ->pluck('group_id');
    }

    /**
     * Only what a question renders: the word, its definition in every locale, and the clip of the
     * word being spoken.
     *
     * Part of speech, pronunciation, the example sentence and its translation, and the sentence clip
     * are all left out — no question shows any of them, and a practice set can run to thousands of
     * words, where the difference is most of the payload. Every locale travels so that switching
     * language mid-session retranslates the questions in place rather than rebuilding them.
     *
     * @param  Collection<int, Vocabulary>  $vocabularies
     * @return array<int, array{id: int, word: string, translations: array<string, array{definition: string}>, audio_url: string}>
     */
    private function shapeForQuestions(Collection $vocabularies, string $audioBaseUrl): array
    {
        return $vocabularies
            ->map(fn (Vocabulary $vocabulary): array => [
                'id' => $vocabulary->id,
                'word' => $vocabulary->word,
                'translations' => $vocabulary->definitionsByLocale(),
                'audio_url' => "{$audioBaseUrl}/vocabulary/{$vocabulary->id}/word.mp3",
            ])
            ->values()
            ->all();
    }
}
