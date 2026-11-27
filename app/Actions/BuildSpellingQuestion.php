<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizType;
use App\Models\Vocabulary;

/**
 * @phpstan-import-type SpellingSlotPayload from GenerateCustomQuizSession
 * @phpstan-import-type SpellingTilePayload from GenerateCustomQuizSession
 * @phpstan-import-type SpellingQuestionPayload from GenerateCustomQuizSession
 */
readonly class BuildSpellingQuestion
{
    /**
     * Build spelling question with ladder blanking and A-Z sorted tiles.
     *
     * @return SpellingQuestionPayload
     */
    public function handle(Vocabulary $vocabulary): array
    {
        $vocabulary->loadMissing('translation');

        $targetWord = strtolower($vocabulary->word);
        $rawChars = str_split($targetWord);
        $alphaIndices = array_keys(array_filter($rawChars, static fn (string $char): bool => ctype_alpha($char)));
        $chosenIndices = $this->selectBlankIndices(count($rawChars), $alphaIndices);
        ['slots' => $slots, 'tiles' => $tiles] = $this->buildSlotsAndTiles($rawChars, $chosenIndices);

        return [
            'quiz_type' => QuizType::Spelling->value,
            'stem' => $vocabulary->definition,
            'audio_url' => $vocabulary->word_audio_url,
            'target_word' => $targetWord,
            'slots' => $slots,
            'tiles' => $tiles,
        ];
    }

    /**
     * Determine target blank count K based on word length and alphabetic character count.
     */
    private function determineTargetBlankCount(int $wordLength, int $alphaCount): int
    {
        return match (true) {
            $wordLength <= 2 => $alphaCount,
            $wordLength === 3 => max(1, min(2, $alphaCount - 1)),
            $wordLength <= 5 => min(3, max(1, $alphaCount - 1)),
            $wordLength <= 8 => min(4, max(1, $alphaCount - 2)),
            default => min(5, max(1, $alphaCount - 2)),
        };
    }

    /**
     * Select character indices to blank while preserving anchor letters where applicable.
     *
     * @param  array<int, int>  $alphaIndices
     * @return array<int, int>
     */
    private function selectBlankIndices(int $wordLength, array $alphaIndices): array
    {
        $alphaCount = count($alphaIndices);
        $targetK = $this->determineTargetBlankCount($wordLength, $alphaCount);
        $anchorIndices = $this->resolveAnchorIndices($wordLength, $alphaIndices);
        $candidateIndices = array_values(array_diff($alphaIndices, $anchorIndices));

        if (count($candidateIndices) < $targetK && $alphaCount > 1) {
            $candidateIndices = array_slice($alphaIndices, 1);
        }

        $k = min($targetK, count($candidateIndices));
        if ($k <= 0 && $alphaCount > 0) {
            $k = 1;
            $candidateIndices = $alphaIndices;
        }

        return collect($candidateIndices)
            ->shuffle()
            ->take($k)
            ->values()
            ->all();
    }

    /**
     * Anchor indices: keep first letter for length > 2, and also last letter for length >= 6.
     *
     * @param  array<int, int>  $alphaIndices
     * @return array<int, int>
     */
    private function resolveAnchorIndices(int $wordLength, array $alphaIndices): array
    {
        $alphaCount = count($alphaIndices);
        $anchorIndices = [];

        if ($wordLength > 2 && $alphaCount > 1) {
            $anchorIndices[] = $alphaIndices[0];
        }

        if ($wordLength >= 6 && $alphaCount > 3) {
            $anchorIndices[] = $alphaIndices[$alphaCount - 1];
        }

        return $anchorIndices;
    }

    /**
     * Build slot descriptors and A-Z sorted tile payloads for chosen blank indices.
     *
     * @param  array<int, string>  $rawChars
     * @param  array<int, int>  $chosenIndices
     * @return array{
     *     slots: array<int, SpellingSlotPayload>,
     *     tiles: array<int, SpellingTilePayload>,
     * }
     */
    private function buildSlotsAndTiles(array $rawChars, array $chosenIndices): array
    {
        /** @var array<int, SpellingSlotPayload> $slots */
        $slots = [];
        /** @var array<int, SpellingTilePayload> $tiles */
        $tiles = [];

        foreach ($rawChars as $idx => $char) {
            $isBlank = in_array($idx, $chosenIndices, true);
            $slots[] = [
                'is_blank' => $isBlank,
                'char' => $char,
            ];

            if (! $isBlank) {
                continue;
            }

            $tiles[] = [
                'slot_index' => $idx,
                'char' => $char,
            ];
        }

        usort($tiles, static fn (array $a, array $b): int => strcmp($a['char'], $b['char']));

        return [
            'slots' => $slots,
            'tiles' => $tiles,
        ];
    }
}
