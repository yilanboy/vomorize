<?php

namespace App\Models;

use Database\Factories\LearningProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $level_id
 * @property int $group_id
 * @property int $stage
 * @property int $last_score
 * @property Carbon $last_reviewed_at
 * @property Carbon|null $next_review_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LearningProgress extends Model
{
    /** @use HasFactory<LearningProgressFactory> */
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
