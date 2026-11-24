<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizType;
use App\Models\SentenceClozeQuestion;

/**
 * @phpstan-import-type VocabularyPayload from GenerateCustomQuizSession
 * @phpstan-import-type SentenceSegmentPayload from GenerateCustomQuizSession
 * @phpstan-import-type QuizOptionPayload from GenerateCustomQuizSession
 * @phpstan-import-type QuizQuestionPayload from GenerateCustomQuizSession
 */
readonly class BuildSentenceClozeQuestion
{
    /**
     * Build sentence cloze question using curated same-group distractors and inflected option forms.
     *
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $groupVocabularies
     * @return QuizQuestionPayload
     */
    public function handle(
        array $target,
        SentenceClozeQuestion $cloze,
        array $groupVocabularies,
    ): array {
        /** @var array<int, VocabularyPayload> $vocabById */
        $vocabById = [];
        foreach ($groupVocabularies as $vocab) {
            $vocabById[$vocab['id']] = $vocab;
        }

        $selectedDistractors = $this->selectDistractors($target, $cloze, $groupVocabularies, $vocabById);
        $options = $this->buildOptions($target, $cloze, $selectedDistractors, $vocabById);
        $segments = $this->parseSentenceSegments($cloze->sentence, $cloze->blank_word);

        return [
            'quiz_type' => QuizType::SentenceCloze->value,
            'stem' => $cloze->sentence,
            'audio_url' => '',
            'options' => $options,
            'correct_option_id' => $target['id'],
            'sentence_segments' => $segments,
        ];
    }

    /**
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $groupVocabularies
     * @param  array<int, VocabularyPayload>  $vocabById
     * @return array<int, array{vocabulary_id: int, word: string}>
     */
    private function selectDistractors(
        array $target,
        SentenceClozeQuestion $cloze,
        array $groupVocabularies,
        array $vocabById,
    ): array {
        $selectedDistractors = collect($cloze->distractors)
            ->filter(
                static fn (array $d): bool => $d['vocabulary_id'] !== $target['id']
                && array_key_exists($d['vocabulary_id'], $vocabById),
            )
            ->shuffle()
            ->take(3)
            ->values()
            ->all();

        if (count($selectedDistractors) >= 3) {
            return $selectedDistractors;
        }

        // Fallback if curated distractors had fewer than 3 valid group members
        $usedIds = array_merge([$target['id']], array_column($selectedDistractors, 'vocabulary_id'));
        $extra = collect($groupVocabularies)
            ->filter(static fn (array $v): bool => ! in_array($v['id'], $usedIds, true))
            ->shuffle()
            ->take(3 - count($selectedDistractors))
            ->map(static fn (array $v): array => ['vocabulary_id' => $v['id'], 'word' => $v['word']])
            ->all();

        return array_merge($selectedDistractors, $extra);
    }

    /**
     * @param  VocabularyPayload  $target
     * @param  array<int, array{vocabulary_id: int, word: string}>  $selectedDistractors
     * @param  array<int, VocabularyPayload>  $vocabById
     * @return array<int, QuizOptionPayload>
     */
    private function buildOptions(
        array $target,
        SentenceClozeQuestion $cloze,
        array $selectedDistractors,
        array $vocabById,
    ): array {
        $rawOptions = [
            [
                'vocabulary' => $target,
                'display_word' => $cloze->blank_word,
            ],
        ];

        foreach ($selectedDistractors as $distractor) {
            $rawOptions[] = [
                'vocabulary' => $vocabById[$distractor['vocabulary_id']],
                'display_word' => $distractor['word'],
            ];
        }

        return collect($rawOptions)
            ->shuffle()
            ->values()
            ->map(static fn (array $entry): array => [
                'id' => $entry['vocabulary']['id'],
                'text' => $entry['display_word'],
                'part_of_speech' => $entry['vocabulary']['part_of_speech'],
                'word' => $entry['vocabulary']['word'],
                'definition' => $entry['vocabulary']['definition'],
            ])
            ->all();
    }

    /**
     * @return array<int, SentenceSegmentPayload>
     */
    private function parseSentenceSegments(string $sentence, string $blankWord): array
    {
        $parts = preg_split('/(\{\{blank\}\})/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (! is_array($parts)) {
            return [];
        }

        /** @var array<int, SentenceSegmentPayload> $segments */
        $segments = [];
        foreach ($parts as $part) {
            $segments[] = $part === '{{blank}}'
                ? ['type' => 'blank', 'text' => $blankWord]
                : ['type' => 'text', 'text' => $part];
        }

        return $segments;
    }
}
