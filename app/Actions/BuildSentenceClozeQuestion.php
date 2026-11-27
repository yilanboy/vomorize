<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizType;
use App\Models\SentenceClozeQuestion;
use App\Models\Vocabulary;

/**
 * @phpstan-import-type SentenceSegmentPayload from GenerateCustomQuizSession
 * @phpstan-import-type QuizOptionPayload from GenerateCustomQuizSession
 * @phpstan-import-type QuizQuestionPayload from GenerateCustomQuizSession
 */
readonly class BuildSentenceClozeQuestion
{
    /**
     * Build sentence cloze question using curated distractors stored on the cloze record.
     *
     * @return QuizQuestionPayload|null
     */
    public function handle(Vocabulary $vocabulary): ?array
    {
        $vocabulary->loadMissing('sentenceClozeQuestion');

        $cloze = $vocabulary->sentenceClozeQuestion;
        if (! $cloze instanceof SentenceClozeQuestion) {
            return null;
        }

        /** @var array<int, QuizOptionPayload> $options */
        $options = collect($cloze->distractors)
            ->filter(static fn (array $d): bool => $d['vocabulary_id'] !== $vocabulary->id)
            ->shuffle()
            ->take(3)
            ->map(static fn (array $d): array => [
                'id' => $d['vocabulary_id'],
                'text' => $d['word'],
            ])
            ->prepend([
                'id' => $vocabulary->id,
                'text' => $cloze->blank_word,
            ])
            ->shuffle()
            ->values()
            ->all();

        $segments = $this->parseSentenceSegments($cloze->sentence, $cloze->blank_word);

        return [
            'quiz_type' => QuizType::SentenceCloze->value,
            'stem' => $cloze->sentence,
            'audio_url' => '',
            'options' => $options,
            'correct_option_id' => $vocabulary->id,
            'sentence_segments' => $segments,
        ];
    }

    /**
     * @return array<int, SentenceSegmentPayload>
     */
    private function parseSentenceSegments(string $sentence, string $blankWord): array
    {
        $parts = preg_split('/(\\{\\{blank\\}\\})/', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
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
