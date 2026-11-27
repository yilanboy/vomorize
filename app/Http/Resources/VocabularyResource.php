<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vocabulary
 */
class VocabularyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'word' => $this->word,
            'part_of_speech' => $this->part_of_speech,
            'pronunciation' => $this->pronunciation,
            'definition' => $this->definition,
            'example_sentence' => $this->example_sentence,
            'example_translation' => $this->example_translation,
            'word_audio_url' => $this->word_audio_url,
            'sentence_audio_url' => $this->sentence_audio_url,
        ];
    }
}
