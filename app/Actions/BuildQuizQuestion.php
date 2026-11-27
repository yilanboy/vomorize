<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QuizType;
use App\Models\Vocabulary;
use Illuminate\Support\Collection;

/**
 * @phpstan-import-type QuizQuestionPayload from GenerateCustomQuizSession
 */
readonly class BuildQuizQuestion
{
    /**
     * Build a random standard multiple-choice question along with an optional non-audio fallback.
     *
     * @param  Collection<int, Vocabulary>  $groupVocabularies
     * @return array{
     *     quiz: QuizQuestionPayload,
     *     fallback_quiz: QuizQuestionPayload|null,
     * }
     */
    public function handleStandard(Vocabulary $target, Collection $groupVocabularies): array
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
     * @param  Collection<int, Vocabulary>  $groupVocabularies
     * @return QuizQuestionPayload
     */
    public function handle(Vocabulary $target, Collection $groupVocabularies, QuizType $type): array
    {
        $distractors = $groupVocabularies
            ->filter(static fn (Vocabulary $v): bool => $v->id !== $target->id)
            ->shuffle()
            ->take(3)
            ->values();

        $options = $distractors
            ->prepend($target)
            ->shuffle()
            ->values()
            ->map(static fn (Vocabulary $v): array => [
                'id' => $v->id,
                'text' => $type === QuizType::DefinitionToWord ? $v->word : $v->definition,
                'part_of_speech' => $v->part_of_speech,
                'word' => $v->word,
                'definition' => $v->definition,
            ])
            ->all();

        $stem = match ($type) {
            QuizType::WordToDefinition => $target->word,
            QuizType::DefinitionToWord, QuizType::Spelling => $target->definition,
            QuizType::AudioToDefinition, QuizType::SentenceCloze => '',
        };

        return [
            'quiz_type' => $type->value,
            'stem' => $stem,
            'audio_url' => $target->word_audio_url,
            'options' => $options,
            'correct_option_id' => $target->id,
        ];
    }
}
