<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\LevelTranslation;
use App\Models\Vocabulary;
use App\Services\SpacedRepetitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function show(Request $request, Group $group): Response
    {
        $group->load('level.translations');

        $user = $request->user();
        $now = now();

        $progress = null;
        if ($user) {
            $progress = LearningProgress::where('user_id', $user->id)
                ->where('group_id', $group->id)
                ->first();
        }

        $stage = $progress ? $progress->stage : 0;
        $lastScore = $progress ? $progress->last_score : null;
        $lastReviewedAt = $progress && $progress->last_reviewed_at ? $progress->last_reviewed_at->toIso8601String() : null;
        $nextReviewAt = $progress && $progress->next_review_at ? $progress->next_review_at->toIso8601String() : null;
        $status = SpacedRepetitionService::deriveStatus(
            $stage,
            $lastScore,
            $progress ? $progress->next_review_at : null,
            $now
        );

        $baseUri = Uri::of(config('services.audio.base_url'));

        $vocabularies = Vocabulary::with(['translations'])
            ->where('group_id', $group->id)
            ->get()
            ->map(fn (Vocabulary $v) => [
                'id' => $v->id,
                'word' => $v->word,
                'part_of_speech' => $v->part_of_speech,
                'pronunciation' => $v->pronunciation,
                'example_sentence' => $v->example_sentence,
                'translations' => $v->translationsByLocale(),
                'audio_url' => $baseUri
                    ->withPath("/vocabulary/{$v->id}/word.mp3")
                    ->toString(),
                'sentence_audio_url' => $baseUri
                    ->withPath("/vocabulary/{$v->id}/sentence.mp3")
                    ->toString(),
            ]);

        return Inertia::render('groups/Show', [
            'group' => [
                'id' => $group->id,
                'sequence' => $group->sequence,
                'level_id' => $group->level_id,
            ],
            /**
             * The breadcrumb trail is assembled on the client, so the level's name ships as the
             * stored translations rather than as one resolved string — a language switch never
             * reaches the server, and a resolved string would stay in the arrival language.
             */
            'level' => [
                'id' => $group->level->id,
                'translations' => $group->level->translations->map(fn (LevelTranslation $t) => [
                    'locale' => $t->locale,
                    'name' => $t->name,
                ]),
            ],
            'progress' => [
                'stage' => $stage,
                'last_score' => $lastScore,
                'last_reviewed_at' => $lastReviewedAt,
                'next_review_at' => $nextReviewAt,
                'status' => $status,
            ],
            'vocabularies' => $vocabularies,
        ]);
    }
}
