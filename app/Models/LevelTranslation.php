<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LevelTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $level_id
 * @property string $locale
 * @property string $name
 * @property string $description
 */
class LevelTranslation extends Model
{
    /** @use HasFactory<LevelTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'level_id',
        'locale',
        'name',
        'description',
    ];

    /**
     * @return BelongsTo<Level, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
