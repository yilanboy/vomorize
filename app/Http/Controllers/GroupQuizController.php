<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Vocabulary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GroupQuizController extends Controller
{
    public function introduce(Request $request, Group $group): Response|RedirectResponse
    {
        if ($group->vocabularies()->count() < 3) {
            return redirect()->route('groups.show', $group);
        }

        $progress = $this->progressFor($request, $group);

        if ($progress && $progress->stage > 0) {
            return redirect()->route('groups.quiz', $group);
        }

        return Inertia::render('groups/Introduce', $this->quizPayload($group, $progress));
    }

    public function quiz(Request $request, Group $group): Response|RedirectResponse
    {
        if ($group->vocabularies()->count() < 3) {
            return redirect()->route('groups.show', $group);
        }

        $progress = $this->progressFor($request, $group);

        // A guest's progress lives only in their browser, so the server cannot gate them
        // here; Show.svelte points them at the introduction until they clear it.
        if ($request->user() && ($progress === null || $progress->stage === 0)) {
            return redirect()->route('groups.introduce', $group);
        }

        if ($progress?->next_review_at?->isFuture()) {
            return redirect()->route('groups.show', $group);
        }

        return Inertia::render('groups/Quiz', $this->quizPayload($group, $progress));
    }

    public function result(Request $request, Group $group): Response
    {
        $validated = $request->validate([
            'phase' => ['required', 'string', 'in:introduce,quiz'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        return Inertia::render('groups/Result', [
            'group' => [
                'id' => $group->id,
                'level_id' => $group->level_id,
                'sequence' => $group->sequence,
            ],
            'result' => [
                'phase' => $validated['phase'],
                'score' => (int) $validated['score'],
                'passed' => $validated['score'] >= 90,
            ],
        ]);
    }

    /**
     * @return array{group: array{id: int, level_id: int, sequence: int}, progress: array<string, mixed>|null, vocabularies: Collection<int, array<string, mixed>>}
     */
    private function quizPayload(Group $group, ?LearningProgress $progress): array
    {
        $baseUrl = rtrim(config('services.audio.base_url', 'https://assets.vomorize.com'), '/');
        $vocabularies = Vocabulary::with('translations')
            ->where('group_id', $group->id)
            ->orderBy('id')
            ->get()
            ->map(fn (Vocabulary $vocabulary): array => [
                'id' => $vocabulary->id,
                'word' => $vocabulary->word,
                'part_of_speech' => $vocabulary->part_of_speech,
                'pronunciation' => $vocabulary->pronunciation,
                'example_sentence' => $vocabulary->example_sentence,
                'translations' => $vocabulary->translationsByLocale(),
                'audio_url' => "{$baseUrl}/vocabulary/{$vocabulary->id}/word.mp3",
                'sentence_audio_url' => "{$baseUrl}/vocabulary/{$vocabulary->id}/sentence.mp3",
            ]);

        return [
            'group' => [
                'id' => $group->id,
                'level_id' => $group->level_id,
                'sequence' => $group->sequence,
            ],
            'progress' => $progress ? [
                'stage' => $progress->stage,
                'last_score' => $progress->last_score,
                'next_review_at' => $progress->next_review_at?->toIso8601String(),
            ] : null,
            'vocabularies' => $vocabularies,
        ];
    }

    private function progressFor(Request $request, Group $group): ?LearningProgress
    {
        if (! $request->user()) {
            return null;
        }

        return LearningProgress::where('user_id', $request->user()->id)
            ->where('group_id', $group->id)
            ->first();
    }
}
