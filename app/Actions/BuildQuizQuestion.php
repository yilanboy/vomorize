<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizType;

/**
 * @phpstan-import-type VocabularyPayload from GenerateCustomQuizSession
 * @phpstan-import-type QuizQuestionPayload from GenerateCustomQuizSession
 */
readonly class BuildQuizQuestion
{
    /**
     * Build a random standard multiple-choice question along with an optional non-audio fallback.
     *
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $groupVocabularies
     * @return array{
     *     quiz: QuizQuestionPayload,
     *     fallback_quiz: QuizQuestionPayload|null,
     * }
     */
    public function handleStandard(array $target, array $groupVocabularies): array
    {
        $standardQuizTypes = QuizType::coreTypes();
        $selectedType = $standardQuizTypes[random_int(0, count($standardQuizTypes) - 1)];
        $quiz = $this->handle($target, $groupVocabularies, $selectedType);

        $fallbackQuiz = null;
        if ($selectedType === QuizType::AudioToDefinition) {
            $nonAudioTypes = [QuizType::WordToDefinition, QuizType::DefinitionToWord];
            $fallbackType = $nonAudioTypes[random_int(0, 1)];
            $fallbackQuiz = $this->handle($target, $groupVocabularies, $fallbackType);
        }

        return [
            'quiz' => $quiz,
            'fallback_quiz' => $fallbackQuiz,
        ];
    }

    /**
     * Build a multiple-choice quiz question with 3 distractors chosen from the same group.
     *
     * @param  VocabularyPayload  $target
     * @param  array<int, VocabularyPayload>  $groupVocabularies
     * @return QuizQuestionPayload
     */
    public function handle(array $target, array $groupVocabularies, QuizType $type): array
    {
        $distractors = collect($groupVocabularies)
            ->filter(static fn (array $v): bool => $v['id'] !== $target['id'])
            ->shuffle()
            ->take(3)
            ->values()
            ->all();

        $allOptionsRaw = array_merge([$target], $distractors);

        $options = collect($allOptionsRaw)
            ->shuffle()
            ->values()
            ->map(static fn (array $v): array => [
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
            QuizType::AudioToDefinition, QuizType::SentenceCloze => '',
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
