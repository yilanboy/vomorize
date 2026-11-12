<?php

declare(strict_types=1);

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
 * @phpstan-type RawStep array{
 *     kind: 'intro'|'quiz',
 *     vocabulary: VocabularyPayload,
 *     quiz: QuizQuestionPayload|null,
 *     fallback_quiz?: QuizQuestionPayload|null,
 * }
 * @phpstan-type SessionStep array{
 *     id: string,
 *     step_index: int,
 *     kind: 'intro'|'quiz',
 *     vocabulary: VocabularyPayload,
 *     quiz: QuizQuestionPayload|null,
 *     fallback_quiz: QuizQuestionPayload|null,
 * }
 */
readonly class GenerateStage1Session
{
    public function __construct(
        private GetGroupVocabularies $getGroupVocabularies,
    ) {}

    /**
     * Generate the 30-step Stage 1 intro and spaced retrieval quiz session.
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

        /** @var array<int, array{0: array{quiz: QuizQuestionPayload, fallback_quiz: QuizQuestionPayload|null}, 1: array{quiz: QuizQuestionPayload, fallback_quiz: QuizQuestionPayload|null}}> $quizzesByWordIndex */
        $quizzesByWordIndex = [];

        foreach ($vocabularies as $i => $vocab) {
            $cases = QuizType::coreTypes();
            /** @var array<int, QuizType> $selectedTypes */
            $selectedTypes = collect($cases)->shuffle()->take(2)->values()->all();
            $remainingType = collect($cases)->first(fn (QuizType $t): bool => ! in_array($t, $selectedTypes, true));

            $quizzes = [];
            foreach ($selectedTypes as $type) {
                $quizPayload = $this->buildQuizQuestion($vocab, $vocabularies, $type);
                $fallbackPayload = ($type === QuizType::AudioToDefinition && $remainingType instanceof QuizType)
                    ? $this->buildQuizQuestion($vocab, $vocabularies, $remainingType)
                    : null;

                $quizzes[] = [
                    'quiz' => $quizPayload,
                    'fallback_quiz' => $fallbackPayload,
                ];
            }

            $quizzesByWordIndex[$i] = [$quizzes[0], $quizzes[1]];
        }

        /** @var array<int, RawStep> $rawSteps */
        $rawSteps = [];

        // Round 1 (4 steps): Intro W0, W1 -> Quiz [W0(T1), W1(T1)] shuffled
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[0], 'quiz' => null, 'fallback_quiz' => null];
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[1], 'quiz' => null, 'fallback_quiz' => null];
        /** @var array<int, RawStep> $r1Quizzes */
        $r1Quizzes = collect([
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[0], 'quiz' => $quizzesByWordIndex[0][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[0][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[1], 'quiz' => $quizzesByWordIndex[1][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[1][0]['fallback_quiz']],
        ])->shuffle()->all();
        array_push($rawSteps, ...$r1Quizzes);

        // Round 2 (6 steps): Intro W2, W3 -> Quiz [W2(T1), W3(T1), W0(T2), W1(T2)] shuffled
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[2], 'quiz' => null, 'fallback_quiz' => null];
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[3], 'quiz' => null, 'fallback_quiz' => null];
        /** @var array<int, RawStep> $r2Quizzes */
        $r2Quizzes = collect([
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[2], 'quiz' => $quizzesByWordIndex[2][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[2][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[3], 'quiz' => $quizzesByWordIndex[3][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[3][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[0], 'quiz' => $quizzesByWordIndex[0][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[0][1]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[1], 'quiz' => $quizzesByWordIndex[1][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[1][1]['fallback_quiz']],
        ])->shuffle()->all();
        array_push($rawSteps, ...$r2Quizzes);

        // Round 3 (6 steps): Intro W4, W5 -> Quiz [W4(T1), W5(T1), W2(T2), W3(T2)] shuffled
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[4], 'quiz' => null, 'fallback_quiz' => null];
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[5], 'quiz' => null, 'fallback_quiz' => null];
        /** @var array<int, RawStep> $r3Quizzes */
        $r3Quizzes = collect([
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[4], 'quiz' => $quizzesByWordIndex[4][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[4][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[5], 'quiz' => $quizzesByWordIndex[5][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[5][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[2], 'quiz' => $quizzesByWordIndex[2][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[2][1]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[3], 'quiz' => $quizzesByWordIndex[3][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[3][1]['fallback_quiz']],
        ])->shuffle()->all();
        array_push($rawSteps, ...$r3Quizzes);

        // Round 4 (6 steps): Intro W6, W7 -> Quiz [W6(T1), W7(T1), W4(T2), W5(T2)] shuffled
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[6], 'quiz' => null, 'fallback_quiz' => null];
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[7], 'quiz' => null, 'fallback_quiz' => null];
        /** @var array<int, RawStep> $r4Quizzes */
        $r4Quizzes = collect([
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[6], 'quiz' => $quizzesByWordIndex[6][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[6][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[7], 'quiz' => $quizzesByWordIndex[7][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[7][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[4], 'quiz' => $quizzesByWordIndex[4][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[4][1]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[5], 'quiz' => $quizzesByWordIndex[5][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[5][1]['fallback_quiz']],
        ])->shuffle()->all();
        array_push($rawSteps, ...$r4Quizzes);

        // Round 5 (6 steps): Intro W8, W9 -> Quiz [W8(T1), W9(T1), W6(T2), W7(T2)] shuffled
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[8], 'quiz' => null, 'fallback_quiz' => null];
        $rawSteps[] = ['kind' => 'intro', 'vocabulary' => $vocabularies[9], 'quiz' => null, 'fallback_quiz' => null];
        /** @var array<int, RawStep> $r5Quizzes */
        $r5Quizzes = collect([
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[8], 'quiz' => $quizzesByWordIndex[8][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[8][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[9], 'quiz' => $quizzesByWordIndex[9][0]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[9][0]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[6], 'quiz' => $quizzesByWordIndex[6][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[6][1]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[7], 'quiz' => $quizzesByWordIndex[7][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[7][1]['fallback_quiz']],
        ])->shuffle()->all();
        array_push($rawSteps, ...$r5Quizzes);

        // Round 6 (2 steps): Quiz [W8(T2), W9(T2)] shuffled
        /** @var array<int, RawStep> $r6Quizzes */
        $r6Quizzes = collect([
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[8], 'quiz' => $quizzesByWordIndex[8][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[8][1]['fallback_quiz']],
            ['kind' => 'quiz', 'vocabulary' => $vocabularies[9], 'quiz' => $quizzesByWordIndex[9][1]['quiz'], 'fallback_quiz' => $quizzesByWordIndex[9][1]['fallback_quiz']],
        ])->shuffle()->all();
        array_push($rawSteps, ...$r6Quizzes);

        // Map into final steps with 1-based indexing and unique IDs
        $steps = [];
        foreach ($rawSteps as $index => $step) {
            $stepIndex = $index + 1;
            $steps[] = [
                'id' => "step-{$stepIndex}-{$step['kind']}-{$step['vocabulary']['id']}",
                'step_index' => $stepIndex,
                'kind' => $step['kind'],
                'vocabulary' => $step['vocabulary'],
                'quiz' => $step['quiz'],
                'fallback_quiz' => $step['fallback_quiz'] ?? null,
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
}
