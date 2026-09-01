<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningProgress extends Model
{
    use HasFactory;

    protected $table = 'learning_progress';

    protected $fillable = [
        'user_id',
        'level_id',
        'group_id',
        'stage',
        'last_score',
        'last_reviewed_at',
        'next_review_at',
    ];

    protected function casts(): array
    {
        return [
            'stage' => 'integer',
            'last_score' => 'integer',
            'last_reviewed_at' => 'datetime',
            'next_review_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
