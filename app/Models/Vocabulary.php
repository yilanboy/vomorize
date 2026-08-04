<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vocabulary extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'word',
        'part_of_speech',
        'pronunciation',
        'example_sentence',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return HasMany<VocabularyTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(VocabularyTranslation::class);
    }

    /**
     * Every locale's content, keyed by locale, for the client to resolve at render time.
     *
     * Resolution moved to the client so a learner can change language mid-quiz without the
     * question set being rebuilt, which would reshuffle and restart their attempt. The client
     * applies the same fallback this used to apply here: the requested locale, then zh_TW.
     *
     * Costs no extra queries wherever the relation is already eager-loaded, which is every
     * caller — they previously loaded all locales and discarded all but one.
     *
     * @return array<string, array{definition: string, example_translation: string}>
     */
    public function translationsByLocale(): array
    {
        return $this->translations
            ->mapWithKeys(fn (VocabularyTranslation $translation): array => [
                $translation->locale => [
                    'definition' => $translation->definition,
                    // The only nullable one of the pair.
                    'example_translation' => $translation->example_translation ?? '',
                ],
            ])
            ->all();
    }

    /**
     * Every locale's definition and nothing else — the subset a quiz question renders.
     *
     * Keyed by locale for the same reason the fuller accessor is: the client resolves the active
     * locale at render time so changing language mid-quiz does not rebuild the question set.
     *
     * @return array<string, array{definition: string}>
     */
    public function definitionsByLocale(): array
    {
        return $this->translations
            ->mapWithKeys(fn (VocabularyTranslation $translation): array => [
                $translation->locale => ['definition' => $translation->definition],
            ])
            ->all();
    }
}
