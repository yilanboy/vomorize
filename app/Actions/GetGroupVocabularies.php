<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Group;
use App\Models\Vocabulary;

readonly class GetGroupVocabularies
{
    /**
     * Retrieve and format the vocabularies with CDN audio URLs for a group.
     *
     * @return array<int, array{
     *     id: int,
     *     word: string,
     *     part_of_speech: string,
     *     pronunciation: string,
     *     definition: string,
     *     example_sentence: string,
     *     example_translation: string,
     *     word_audio_url: string,
     *     sentence_audio_url: string,
     * }>
     */
    public function handle(Group $group): array
    {
        $group->loadMissing('vocabularies.translation');

        return $group
            ->vocabularies
            ->sortBy('id')
            ->values()
            ->map(fn (Vocabulary $v): array => [
                'id' => $v->id,
                'word' => $v->word,
                'part_of_speech' => $v->part_of_speech,
                'pronunciation' => $v->pronunciation,
                'definition' => $v->definition,
                'example_sentence' => $v->example_sentence,
                'example_translation' => $v->example_translation,
                'word_audio_url' => $v->word_audio_url,
                'sentence_audio_url' => $v->sentence_audio_url,
            ])
            ->all();
    }
}
