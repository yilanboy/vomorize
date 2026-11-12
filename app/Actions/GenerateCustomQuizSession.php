<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizType;
use App\Models\Group;
use App\Models\LearningProgress;
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
    /**
     * Resolve available passed group IDs for a member or guest.
     *
     * @param  array<int, int>  $passedGroupIds
     * @return array<int, int>
     */
    public function resolveAvailableGroupIds(?User $user = null, array $passedGroupIds = []): array
    {
        /** @var array<int, int> $availableGroupIds */
        $availableGroupIds = $user instanceof User
            ? LearningProgress::query()
                ->where('user_id', $user->id)
                ->where('stage', '>=', 1)
                ->pluck('group_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all()
            : Group::query()
                ->whereIn('id', $passedGroupIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();

        return $availableGroupIds;
    }

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
    public function handle(int $questionCount, ?User $user = null, array $passedGroupIds = [], string $quizMode = 'standard'): array
    {
        $cdnUrl = rtrim((string) (config('filesystems.disks.r2.url') ?: 'https://assets.vomorize.com'), '/');

        $availableGroupIds = $this->resolveAvailableGroupIds($user, $passedGroupIds);

        $totalWordsAvailable = count($availableGroupIds) * 10;
        if ($totalWordsAvailable < $questionCount || $questionCount <= 0) {
            throw new InvalidArgumentException("Insufficient vocabulary pool for the requested {$questionCount} questions.");
        }

        $rawVocabularies = Vocabulary::query()
            ->whereIn('group_id', $availableGroupIds)
            ->get();

        /** @var array<int, VocabularyPayload> $allVocabularies */
        $allVocabularies = [];
        /** @var array<int, array<int, VocabularyPayload>> $vocabulariesByGroup */
        $vocabulariesByGroup = [];

        foreach ($rawVocabularies as $vocab) {
            /** @var VocabularyPayload $payload */
            $payload = [
                'id' => (int) $vocab->id,
                'group_id' => (int) $vocab->group_id,
                'word' => (string) $vocab->word,
                'part_of_speech' => (string) $vocab->part_of_speech,
                'pronunciation' => (string) $vocab->pronunciation,
                'definition' => (string) $vocab->definition,
                'example_sentence' => (string) $vocab->example_sentence,
                'example_translation' => (string) $vocab->example_translation,
                'word_audio_url' => "{$cdnUrl}/vocabulary/{$vocab->id}/word.mp3",
                'sentence_audio_url' => "{$cdnUrl}/vocabulary/{$vocab->id}/sentence.mp3",
            ];

            $allVocabularies[] = $payload;
            $vocabulariesByGroup[$payload['group_id']][] = $payload;
        }

        /** @var array<int, VocabularyPayload> $selectedTargets */
        $selectedTargets = collect($allVocabularies)
            ->shuffle()
            ->take($questionCount)
            ->values()
            ->all();

        $steps = [];
        $standardQuizTypes = [
            QuizType::WordToDefinition,
            QuizType::DefinitionToWord,
            QuizType::AudioToDefinition,
        ];

        foreach ($selectedTargets as $index => $target) {
            $stepIndex = $index + 1;
            $groupVocabularies = $vocabulariesByGroup[$target['group_id']] ?? [];

            if ($quizMode === 'spelling') {
                $quiz = $this->buildSpellingQuestion($target);
                $fallbackQuiz = null;
            } else {
                $selectedType = $standardQuizTypes[random_int(0, count($standardQuizTypes) - 1)];
                $quiz = $this->buildQuizQuestion($target, $groupVocabularies, $selectedType);

                $fallbackQuiz = null;
                if ($selectedType === QuizType::AudioToDefinition) {
                    $nonAudioTypes = [QuizType::WordToDefinition, QuizType::DefinitionToWord];
                    $fallbackType = $nonAudioTypes[random_int(0, 1)];
                    $fallbackQuiz = $this->buildQuizQuestion($target, $groupVocabularies, $fallbackType);
                }
            }

            $steps[] = [
                'id' => "step-{$stepIndex}-custom-quiz-{$target['id']}",
                'step_index' => $stepIndex,
                'kind' => 'quiz',
                'vocabulary' => $target,
                'quiz' => $quiz,
                'fallback_quiz' => $fallbackQuiz,
            ];
        }

        return [
            'steps' => $steps,
            'correct_audio_url' => "{$cdnUrl}/quiz/correct.mp3",
            'wrong_audio_url' => "{$cdnUrl}/quiz/wrong.mp3",
            'question_count' => count($steps),
        ];
    }

    /**
     * Build quiz question with 3 distractors chosen from the same group.
     *
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $groupVocabularies
     * @return QuizQuestionPayload
     */
    private function buildQuizQuestion(array $target, array $groupVocabularies, QuizType $type): array
    {
        $distractors = collect($groupVocabularies)
            ->filter(fn (array $v): bool => $v['id'] !== $target['id'])
            ->shuffle()
            ->take(3)
            ->values()
            ->all();

        $allOptionsRaw = array_merge([$target], $distractors);

        /** @var array<int, QuizOptionPayload> $options */
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
            QuizType::DefinitionToWord, QuizType::Spelling => $target['definition'],
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

    /**
     * Build spelling question with ladder blanking and A-Z sorted tiles.
     *
     * @param  VocabularyPayload  $target
     * @return SpellingQuestionPayload
     */
    private function buildSpellingQuestion(array $target): array
    {
        $rawChars = str_split(strtolower($target['word']));
        $len = count($rawChars);

        // Identify non-blankable separator characters (hyphen, apostrophe, space)
        $alphaIndices = [];
        foreach ($rawChars as $idx => $char) {
            if (ctype_alpha($char)) {
                $alphaIndices[] = $idx;
            }
        }

        $alphaCount = count($alphaIndices);

        // Determine target blank count K based on word length
        $targetK = match (true) {
            $len <= 2 => $alphaCount,
            $len === 3 => max(1, min(2, $alphaCount - 1)),
            $len <= 5 => min(3, max(1, $alphaCount - 1)),
            $len <= 8 => min(4, max(1, $alphaCount - 2)),
            default => min(5, max(1, $alphaCount - 2)),
        };

        // Anchor indices:
        // For len <= 2: no anchor (both can be blanked if alphaCount == 2)
        // For len == 3: keep first letter (anchor index 0)
        // For len >= 6: keep first letter and last letter
        $anchorIndices = [];
        if ($len > 2 && $alphaCount > 1) {
            $anchorIndices[] = $alphaIndices[0];
        }
        if ($len >= 6 && $alphaCount > 3) {
            $anchorIndices[] = $alphaIndices[$alphaCount - 1];
        }

        $candidateIndices = array_values(array_diff($alphaIndices, $anchorIndices));

        // Fallback if candidate pool is smaller than targetK
        if (count($candidateIndices) < $targetK && $alphaCount > 1) {
            $candidateIndices = array_slice($alphaIndices, 1);
        }

        $k = min($targetK, count($candidateIndices));
        if ($k <= 0 && $alphaCount > 0) {
            $k = 1;
            $candidateIndices = $alphaIndices;
        }

        /** @var array<int, int> $chosenIndices */
        $chosenIndices = collect($candidateIndices)
            ->shuffle()
            ->take($k)
            ->values()
            ->all();

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

            if ($isBlank) {
                $tiles[] = [
                    'slot_index' => $idx,
                    'char' => $char,
                ];
            }
        }

        // Sort tiles alphabetically (A-Z)
        usort($tiles, fn (array $a, array $b): int => strcmp($a['char'], $b['char']));

        return [
            'quiz_type' => QuizType::Spelling->value,
            'stem' => $target['definition'],
            'audio_url' => $target['word_audio_url'],
            'target_word' => strtolower($target['word']),
            'slots' => $slots,
            'tiles' => $tiles,
        ];
    }
}
