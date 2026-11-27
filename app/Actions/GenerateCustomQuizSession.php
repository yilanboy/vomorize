<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizMode;
use App\Models\Group;
use App\Models\SentenceClozeQuestion;
use App\Models\User;
use App\Models\Vocabulary;
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
 *     part_of_speech: string,
 *     word: string,
 *     definition: string,
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

        [
            'all' => $allVocabularies,
            'by_group' => $vocabulariesByGroup,
            'cloze_by_vocab_id' => $clozeByVocabId,
        ] = $this->loadVocabularyPool($availableGroupIds, $mode);

        $selectedTargets = $this->selectTargetVocabularies(
            $allVocabularies,
            $clozeByVocabId,
            $questionCount,
            $mode,
        );

        $steps = [];
        foreach ($selectedTargets as $index => $target) {
            $groupVocabularies = $vocabulariesByGroup[$target['group_id']] ?? [];
            $steps[] = $this->buildStep($index + 1, $target, $groupVocabularies, $clozeByVocabId, $mode);
        }

        return [
            'steps' => $steps,
            'correct_audio_url' => "{$cdnUrl}/quiz/correct.mp3",
            'wrong_audio_url' => "{$cdnUrl}/quiz/wrong.mp3",
            'question_count' => count($steps),
        ];
    }

    /**
     * Load vocabulary payloads indexed by group and optional sentence cloze question mapping.
     *
     * @param  array<int, int>  $availableGroupIds
     * @return array{
     *     all: array<int, VocabularyPayload>,
     *     by_group: array<int, array<int, VocabularyPayload>>,
     *     cloze_by_vocab_id: array<int, SentenceClozeQuestion>,
     * }
     */
    private function loadVocabularyPool(array $availableGroupIds, QuizMode $mode): array
    {
        $isSentenceCloze = $mode === QuizMode::SentenceCloze;

        $rawVocabularies = Vocabulary::query()
            ->with('translation')
            ->whereIn('group_id', $availableGroupIds)
            ->when($isSentenceCloze, static fn ($query) => $query->with('sentenceClozeQuestion'))
            ->get();

        /** @var array<int, VocabularyPayload> $allVocabularies */
        $allVocabularies = [];
        /** @var array<int, array<int, VocabularyPayload>> $vocabulariesByGroup */
        $vocabulariesByGroup = [];
        /** @var array<int, SentenceClozeQuestion> $clozeByVocabId */
        $clozeByVocabId = [];

        foreach ($rawVocabularies as $vocab) {
            $payload = $this->formatVocabularyPayload($vocab);
            $allVocabularies[] = $payload;
            $vocabulariesByGroup[$payload['group_id']][] = $payload;

            if ($isSentenceCloze && $vocab->sentenceClozeQuestion instanceof SentenceClozeQuestion) {
                $clozeByVocabId[$payload['id']] = $vocab->sentenceClozeQuestion;
            }
        }

        return [
            'all' => $allVocabularies,
            'by_group' => $vocabulariesByGroup,
            'cloze_by_vocab_id' => $clozeByVocabId,
        ];
    }

    /**
     * @return VocabularyPayload
     */
    private function formatVocabularyPayload(Vocabulary $vocab): array
    {
        return [
            'id' => $vocab->id,
            'group_id' => $vocab->group_id,
            'word' => $vocab->word,
            'part_of_speech' => $vocab->part_of_speech,
            'pronunciation' => $vocab->pronunciation,
            'definition' => $vocab->definition,
            'example_sentence' => $vocab->example_sentence,
            'example_translation' => $vocab->example_translation,
            'word_audio_url' => $vocab->word_audio_url,
            'sentence_audio_url' => $vocab->sentence_audio_url,
        ];
    }

    /**
     * Filter eligible vocabularies for the mode and randomly sample the requested count.
     *
     * @param  array<int, VocabularyPayload>  $allVocabularies
     * @param  array<int, SentenceClozeQuestion>  $clozeByVocabId
     * @return array<int, VocabularyPayload>
     */
    private function selectTargetVocabularies(
        array $allVocabularies,
        array $clozeByVocabId,
        int $questionCount,
        QuizMode $mode,
    ): array {
        $targetPool = $allVocabularies;

        if ($mode === QuizMode::SentenceCloze) {
            $targetPool = array_values(array_filter(
                $allVocabularies,
                static fn (array $v): bool => array_key_exists($v['id'], $clozeByVocabId),
            ));

            if (count($targetPool) < $questionCount) {
                throw new InvalidArgumentException(
                    "Insufficient sentence cloze questions for the requested {$questionCount} questions.",
                );
            }
        }

        return collect($targetPool)
            ->shuffle()
            ->take($questionCount)
            ->values()
            ->all();
    }

    /**
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $groupVocabularies
     * @param  array<int, SentenceClozeQuestion>  $clozeByVocabId
     * @return SessionStep
     */
    private function buildStep(
        int $stepIndex,
        array $target,
        array $groupVocabularies,
        array $clozeByVocabId,
        QuizMode $mode,
    ): array {
        ['quiz' => $quiz, 'fallback_quiz' => $fallbackQuiz] = match ($mode) {
            QuizMode::Spelling => [
                'quiz' => $this->buildSpellingQuestion->handle($target),
                'fallback_quiz' => null,
            ],
            QuizMode::SentenceCloze => [
                'quiz' => $this->buildSentenceClozeQuestion->handle(
                    $target,
                    $clozeByVocabId[$target['id']] ?? throw new InvalidArgumentException(
                        "Missing sentence cloze question for vocabulary {$target['id']}.",
                    ),
                    $groupVocabularies,
                ),
                'fallback_quiz' => null,
            ],
            QuizMode::Standard => $this->buildQuizQuestion->handleStandard($target, $groupVocabularies),
        };

        return [
            'id' => "step-{$stepIndex}-custom-quiz-{$target['id']}",
            'step_index' => $stepIndex,
            'kind' => 'quiz',
            'vocabulary' => $target,
            'quiz' => $quiz,
            'fallback_quiz' => $fallbackQuiz,
        ];
    }
}
