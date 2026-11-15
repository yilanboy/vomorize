<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SentenceClozeQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $vocabulary_id
 * @property string $sentence
 * @property string $blank_word
 * @property array<int, array{vocabulary_id: int, word: string}> $distractors
 */
class SentenceClozeQuestion extends Model
{
    /** @use HasFactory<SentenceClozeQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'vocabulary_id',
        'sentence',
        'blank_word',
        'distractors',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'distractors' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Vocabulary, $this>
     */
    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(Vocabulary::class);
    }
}
