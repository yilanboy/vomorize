<?php

namespace App\Actions;

use App\Enums\QuizType;
use App\Models\Group;

/**
 * @phpstan-type VocabularyPayload array{
 *     id: int,
 *     word: string,
 *     part_of_speech: string,
 *     pronunciation: string,
 *     definition: string,
 *     example_sentence: string,
 *     example_translation: string,
 *     word_audio_url: string,
 *     sentence_audio_url: string,
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
 * }
 * @phpstan-type RawQuizStep array{
 *     kind: 'quiz',
 *     vocabulary: VocabularyPayload,
 *     quiz: QuizQuestionPayload,
 * }
 * @phpstan-type SessionStep array{
 *     id: string,
 *     step_index: int,
 *     kind: 'quiz',
 *     vocabulary: VocabularyPayload,
 *     quiz: QuizQuestionPayload,
 * }
 */
readonly class GenerateReviewQuizSession
{
    public function __construct(
        private GetGroupVocabularies $getGroupVocabularies,
    ) {}

    /**
     * Generate the 20-step Stage 2~6 review quiz session.
     * Each of the 10 group vocabularies is tested twice with 2 distinct quiz types,
     * and the two occurrences of the same word are guaranteed to be separated by at least 3 other questions.
     *
     * @return array{
     *     steps: array<int, SessionStep>,
     *     correct_audio_url: string,
     *     wrong_audio_url: string,
     * }
     */
    public function handle(Group $group): array
    {
        $vocabularies = $this->getGroupVocabularies->handle($group);
        $cdnUrl = rtrim((string) (config('filesystems.disks.r2.url') ?: 'https://assets.vomorize.com'), '/');

        if (count($vocabularies) < 10) {
            return [
                'steps' => [],
                'correct_audio_url' => "{$cdnUrl}/quiz/correct.mp3",
                'wrong_audio_url' => "{$cdnUrl}/quiz/wrong.mp3",
            ];
        }

        /** @var array<int, RawQuizStep> $round1Raw */
        $round1Raw = [];

        /** @var array<int, RawQuizStep> $round2Raw */
        $round2Raw = [];

        foreach ($vocabularies as $vocab) {
            /** @var array<int, QuizType> $selectedTypes */
            $selectedTypes = collect(QuizType::cases())->shuffle()->take(2)->values()->all();

            $round1Raw[] = [
                'kind' => 'quiz',
                'vocabulary' => $vocab,
                'quiz' => $this->buildQuizQuestion($vocab, $vocabularies, $selectedTypes[0]),
            ];

            $round2Raw[] = [
                'kind' => 'quiz',
                'vocabulary' => $vocab,
                'quiz' => $this->buildQuizQuestion($vocab, $vocabularies, $selectedTypes[1]),
            ];
        }

        /** @var array<int, RawQuizStep> $firstHalf */
        $firstHalf = collect($round1Raw)->shuffle()->values()->all();

        $tailVocabIds = [
            $firstHalf[7]['vocabulary']['id'],
            $firstHalf[8]['vocabulary']['id'],
            $firstHalf[9]['vocabulary']['id'],
        ];

        $safeForPrefix = collect($round2Raw)
            ->filter(fn (array $step): bool => ! in_array($step['vocabulary']['id'], $tailVocabIds, true))
            ->shuffle()
            ->values();

        $delayedForSuffix = collect($round2Raw)
            ->filter(fn (array $step): bool => in_array($step['vocabulary']['id'], $tailVocabIds, true))
            ->values();

        /** @var array<int, RawQuizStep> $secondHalfPrefix */
        $secondHalfPrefix = $safeForPrefix->take(3)->values()->all();

        /** @var array<int, RawQuizStep> $secondHalfSuffix */
        $secondHalfSuffix = $safeForPrefix
            ->slice(3)
            ->concat($delayedForSuffix)
            ->shuffle()
            ->values()
            ->all();

        /** @var array<int, RawQuizStep> $rawSteps */
        $rawSteps = array_merge($firstHalf, $secondHalfPrefix, $secondHalfSuffix);

        $steps = [];
        foreach ($rawSteps as $index => $step) {
            $stepIndex = $index + 1;
            $steps[] = [
                'id' => "step-{$stepIndex}-quiz-{$step['vocabulary']['id']}",
                'step_index' => $stepIndex,
                'kind' => 'quiz',
                'vocabulary' => $step['vocabulary'],
                'quiz' => $step['quiz'],
            ];
        }

        return [
            'steps' => $steps,
            'correct_audio_url' => "{$cdnUrl}/quiz/correct.mp3",
            'wrong_audio_url' => "{$cdnUrl}/quiz/wrong.mp3",
        ];
    }

    /**
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $allVocabularies
     * @return QuizQuestionPayload
     */
    private function buildQuizQuestion(array $target, array $allVocabularies, QuizType $type): array
    {
        $distractors = collect($allVocabularies)
            ->filter(fn (array $v): bool => $v['id'] !== $target['id'])
            ->shuffle()
            ->take(3)
            ->values()
            ->all();

        $allOptionsRaw = array_merge([$target], $distractors);

        $options = collect($allOptionsRaw)
            ->shuffle()
            ->values()
            ->map(fn (array $v): array => [
                'id' => $v['id'],
                'text' => $type === QuizType::DefinitionToWord ? $v['word'] : $v['definition'],
                'part_of_speech' => $v['part_of_speech'],
                'word' => $v['word'],
                'definition' => $v['definition'],
            ])
            ->all();

        $stem = match ($type) {
            QuizType::WordToDefinition => $target['word'],
            QuizType::DefinitionToWord => $target['definition'],
            QuizType::AudioToDefinition => '',
        };

        return [
            'quiz_type' => $type->value,
            'stem' => $stem,
            'audio_url' => $target['word_audio_url'],
            'options' => $options,
            'correct_option_id' => $target['id'],
        ];
    }
}
