<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VocabularyTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vocabulary_id
 * @property string $locale
 * @property string $definition
 * @property string $example_translation
 */
class VocabularyTranslation extends Model
{
    /** @use HasFactory<VocabularyTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'vocabulary_id',
        'locale',
        'definition',
        'example_translation',
    ];

    /**
     * @return BelongsTo<Vocabulary, $this>
     */
    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }
}
