<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'locale',
        'name',
        'description',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
