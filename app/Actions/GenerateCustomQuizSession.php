<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizMode;
use App\Models\Group;
use App\Models\SentenceClozeQuestion;
use App\Models\User;
use App\Models\Vocabulary;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * @phpstan-type VocabularyPayload array{
 *     id: int,
 *     group_id: int,
 *     word: string,
 *     part_of_speech: string,
 *     pronunciation: string,
 *     definition: string,
 *     example_sentence: string,
 *     example_translation: string,
 *     word_audio_url: string,
 *     sentence_audio_url: string,
 * }
 * @phpstan-type SentenceSegmentPayload array{
 *     type: 'text'|'blank',
 *     text: string,
 * }
 * @phpstan-type QuizOptionPayload array{
 *     id: int,
 *     text: string,
 *     part_of_speech?: string,
 *     word?: string,
 *     definition?: string,
 * }
 * @phpstan-type QuizQuestionPayload array{
 *     quiz_type: string,
 *     stem: string,
 *     audio_url: string,
 *     options: array<int, QuizOptionPayload>,
 *     correct_option_id: int,
 *     sentence_segments?: array<int, SentenceSegmentPayload>,
 * }
 * @phpstan-type SpellingSlotPayload array{
 *     is_blank: bool,
 *     char: string,
 * }
 * @phpstan-type SpellingTilePayload array{
 *     slot_index: int,
 *     char: string,
 * }
 * @phpstan-type SpellingQuestionPayload array{
 *     quiz_type: string,
 *     stem: string,
 *     audio_url: string,
 *     target_word: string,
 *     slots: array<int, SpellingSlotPayload>,
 *     tiles: array<int, SpellingTilePayload>,
 * }
 * @phpstan-type SessionStep array{
 *     id: string,
 *     step_index: int,
 *     kind: 'quiz',
 *     vocabulary: VocabularyPayload,
 *     quiz: QuizQuestionPayload|SpellingQuestionPayload,
 *     fallback_quiz: QuizQuestionPayload|null,
 * }
 */
readonly class GenerateCustomQuizSession
{
    /** @var array<int, int> */
    public const array QUESTION_COUNT_OPTIONS = [10, 20, 30, 50, 100];

    public const int DEFAULT_QUESTION_COUNT = 10;

    public function __construct(
        private ResolveAvailableQuizGroupIds $resolveAvailableQuizGroupIds,
        private BuildQuizQuestion $buildQuizQuestion,
        private BuildSentenceClozeQuestion $buildSentenceClozeQuestion,
        private BuildSpellingQuestion $buildSpellingQuestion,
    ) {}

    /**
     * Generate custom quiz session with questions sampled from passed Stage 1 groups.
     * Distractors for each question are randomly chosen from the same group.
     *
     * @param  array<int, int>  $passedGroupIds
     * @return array{
     *     steps: array<int, SessionStep>,
     *     correct_audio_url: string,
     *     wrong_audio_url: string,
     *     question_count: int,
     * }
     */
    public function handle(
        int $questionCount,
        ?User $user = null,
        array $passedGroupIds = [],
        string $quizMode = QuizMode::Standard->value,
    ): array {
        $cdnUrl = rtrim((string) (config('filesystems.disks.r2.url') ?: 'https://assets.vomorize.com'), '/');
        $availableGroupIds = $this->resolveAvailableQuizGroupIds->handle($user, $passedGroupIds);

        $totalWordsAvailable = count($availableGroupIds) * Group::VOCABULARY_PER_GROUP;
        if ($totalWordsAvailable < $questionCount || $questionCount <= 0) {
            throw new InvalidArgumentException(
                "Insufficient vocabulary pool for the requested {$questionCount} questions.",
            );
        }

        $mode = QuizMode::tryFrom($quizMode) ?? QuizMode::Standard;
        $allVocabularies = $this->loadVocabularyPool($availableGroupIds, $mode);
        $vocabulariesByGroup = $allVocabularies->groupBy('group_id');
        $selectedTargets = $this->selectTargetVocabularies($allVocabularies, $questionCount, $mode);

        $steps = [];
        foreach ($selectedTargets as $index => $target) {
            /** @var Collection<int, Vocabulary> $groupVocabularies */
            $groupVocabularies = $vocabulariesByGroup->get($target->group_id, collect());
            $steps[] = $this->buildStep($index + 1, $target, $groupVocabularies, $mode);
        }

        return [
            'steps' => $steps,
            'correct_audio_url' => "{$cdnUrl}/quiz/correct.mp3",
            'wrong_audio_url' => "{$cdnUrl}/quiz/wrong.mp3",
            'question_count' => count($steps),
        ];
    }

    /**
     * Load vocabularies with localized translations and optional sentence cloze relations.
     *
     * @param  array<int, int>  $availableGroupIds
     * @return Collection<int, Vocabulary>
     */
    private function loadVocabularyPool(array $availableGroupIds, QuizMode $mode): Collection
    {
        return Vocabulary::query()
            ->with('translation')
            ->whereIn('group_id', $availableGroupIds)
            ->when(
                $mode === QuizMode::SentenceCloze,
                static fn ($query) => $query->with('sentenceClozeQuestion'),
            )
            ->get();
    }

    /**
     * @return VocabularyPayload
     */
    private function formatVocabularyPayload(Vocabulary $vocabulary): array
    {
        return [
            'id' => $vocabulary->id,
            'group_id' => $vocabulary->group_id,
            'word' => $vocabulary->word,
            'part_of_speech' => $vocabulary->part_of_speech,
            'pronunciation' => $vocabulary->pronunciation,
            'definition' => $vocabulary->definition,
            'example_sentence' => $vocabulary->example_sentence,
            'example_translation' => $vocabulary->example_translation,
            'word_audio_url' => $vocabulary->word_audio_url,
            'sentence_audio_url' => $vocabulary->sentence_audio_url,
        ];
    }

    /**
     * Filter eligible vocabularies for the mode and randomly sample the requested count.
     *
     * @param  Collection<int, Vocabulary>  $allVocabularies
     * @return array<int, Vocabulary>
     */
    private function selectTargetVocabularies(
        Collection $allVocabularies,
        int $questionCount,
        QuizMode $mode,
    ): array {
        $targetPool = $allVocabularies;

        if ($mode === QuizMode::SentenceCloze) {
            $targetPool = $allVocabularies
                ->filter(static fn (Vocabulary $v): bool => $v->sentenceClozeQuestion instanceof SentenceClozeQuestion)
                ->values();

            if ($targetPool->count() < $questionCount) {
                throw new InvalidArgumentException(
                    "Insufficient sentence cloze questions for the requested {$questionCount} questions.",
                );
            }
        }

        return $targetPool
            ->shuffle()
            ->take($questionCount)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Vocabulary>  $groupVocabularies
     * @return SessionStep
     */
    private function buildStep(
        int $stepIndex,
        Vocabulary $target,
        Collection $groupVocabularies,
        QuizMode $mode,
    ): array {
        ['quiz' => $quiz, 'fallback_quiz' => $fallbackQuiz] = match ($mode) {
            QuizMode::Spelling => [
                'quiz' => $this->buildSpellingQuestion->handle($target),
                'fallback_quiz' => null,
            ],
            QuizMode::SentenceCloze => [
                'quiz' => $this->buildSentenceClozeQuestion->handle($target) ?? throw new InvalidArgumentException(
                    "Missing sentence cloze question for vocabulary {$target->id}.",
                ),
                'fallback_quiz' => null,
            ],
            QuizMode::Standard => $this->buildQuizQuestion->handleStandard($target, $groupVocabularies),
        };

        return [
            'id' => "step-{$stepIndex}-custom-quiz-{$target->id}",
            'step_index' => $stepIndex,
            'kind' => 'quiz',
            'vocabulary' => $this->formatVocabularyPayload($target),
            'quiz' => $quiz,
            'fallback_quiz' => $fallbackQuiz,
        ];
    }
}
