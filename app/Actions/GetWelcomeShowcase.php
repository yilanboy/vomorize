<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SentenceClozeQuestion;
use App\Models\Vocabulary;

/**
 * @phpstan-import-type VocabularyPayload from GenerateCustomQuizSession
 * @phpstan-import-type QuizQuestionPayload from GenerateCustomQuizSession
 * @phpstan-import-type SpellingQuestionPayload from GenerateCustomQuizSession
 *
 * @phpstan-type ShowcasePayload array{
 *     vocabulary: VocabularyPayload,
 *     cloze_question: QuizQuestionPayload|null,
 *     spelling_question: SpellingQuestionPayload,
 * }
 */
readonly class GetWelcomeShowcase
{
    public function __construct(
        private BuildSentenceClozeQuestion $buildSentenceClozeQuestion,
        private BuildSpellingQuestion $buildSpellingQuestion,
    ) {}

    /**
     * Load a random vocabulary along with real cloze and spelling payloads for the landing page.
     *
     * @return array{
     *     vocabulary: Vocabulary|null,
     *     showcase: ShowcasePayload|null,
     * }
     */
    public function handle(): array
    {
        $vocabulary = Vocabulary::query()
            ->whereHas('sentenceClozeQuestion')
            ->with(['translation:vocabulary_id,definition,example_translation', 'sentenceClozeQuestion'])
            ->inRandomOrder()
            ->first(['id', 'group_id', 'word', 'part_of_speech', 'pronunciation', 'example_sentence']);

        if ($vocabulary === null) {
            $vocabulary = Vocabulary::query()
                ->with('translation:vocabulary_id,definition,example_translation')
                ->inRandomOrder()
                ->first(['id', 'group_id', 'word', 'part_of_speech', 'pronunciation', 'example_sentence']);
        }

        if ($vocabulary === null) {
            return [
                'vocabulary' => null,
                'showcase' => null,
            ];
        }

        $cdnUrl = $this->resolveCdnUrl();
        $targetPayload = $this->formatVocabularyPayload($vocabulary, $cdnUrl);
        $spellingQuestion = $this->buildSpellingQuestion->handle($targetPayload);
        $clozeQuestion = $this->resolveClozeQuestion($vocabulary, $targetPayload, $cdnUrl);

        return [
            'vocabulary' => $vocabulary,
            'showcase' => [
                'vocabulary' => $targetPayload,
                'cloze_question' => $clozeQuestion,
                'spelling_question' => $spellingQuestion,
            ],
        ];
    }

    /**
     * @param  VocabularyPayload  $targetPayload
     * @return QuizQuestionPayload|null
     */
    private function resolveClozeQuestion(
        Vocabulary $vocabulary,
        array $targetPayload,
        string $cdnUrl,
    ): ?array {
        $cloze = $vocabulary->sentenceClozeQuestion;
        if (! $cloze instanceof SentenceClozeQuestion) {
            return null;
        }

        $groupVocabularies = Vocabulary::query()
            ->with('translation:vocabulary_id,definition,example_translation')
            ->where('group_id', $vocabulary->group_id)
            ->get(['id', 'group_id', 'word', 'part_of_speech', 'pronunciation', 'example_sentence'])
            ->map(fn (Vocabulary $item): array => $this->formatVocabularyPayload($item, $cdnUrl))
            ->values()
            ->all();

        if (count($groupVocabularies) < 4) {
            return null;
        }

        return $this->buildSentenceClozeQuestion->handle($targetPayload, $cloze, $groupVocabularies);
    }

    /**
     * @return VocabularyPayload
     */
    private function formatVocabularyPayload(Vocabulary $vocabulary, string $cdnUrl): array
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
            'word_audio_url' => "{$cdnUrl}/vocabulary/{$vocabulary->id}/word.mp3",
            'sentence_audio_url' => "{$cdnUrl}/vocabulary/{$vocabulary->id}/sentence.mp3",
        ];
    }

    private function resolveCdnUrl(): string
    {
        /** @var string|null $configuredUrl */
        $configuredUrl = config('filesystems.disks.r2.url');
        $baseUrl = is_string($configuredUrl) && $configuredUrl !== ''
            ? $configuredUrl
            : 'https://assets.vomorize.com';

        return rtrim($baseUrl, '/');
    }
}
