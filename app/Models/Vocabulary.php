<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\VocabularyFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $level_id
 * @property int $group_id
 * @property string $word
 * @property string $part_of_speech
 * @property string $pronunciation
 * @property string $example_sentence
 * @property-read string $definition
 * @property-read string $example_translation
 */
class Vocabulary extends Model
{
    /** @use HasFactory<VocabularyFactory> */
    use HasFactory;

    /** @use HasTranslations<VocabularyTranslation> */
    use HasTranslations;

    protected $fillable = [
        'level_id',
        'group_id',
        'word',
        'part_of_speech',
        'pronunciation',
        'example_sentence',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'translations',
    ];

    /**
     * @return class-string<VocabularyTranslation>
     */
    protected function translationModelClass(): string
    {
        return VocabularyTranslation::class;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function definition(): Attribute
    {
        return Attribute::get(function (): string {
            $translation = $this->resolveTranslation();

            return $translation !== null ? $translation->definition : '';
        });
    }

    /**
     * @return Attribute<string, never>
     */
    protected function exampleTranslation(): Attribute
    {
        return Attribute::get(function (): string {
            $translation = $this->resolveTranslation();

            return $translation !== null ? $translation->example_translation : '';
        });
    }

    /**
     * @return BelongsTo<Level, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return HasOne<SentenceClozeQuestion, $this>
     */
    public function sentenceClozeQuestion(): HasOne
    {
        return $this->hasOne(SentenceClozeQuestion::class);
    }
}
