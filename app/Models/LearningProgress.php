<?php

declare(strict_types=1);

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

    public const int STAGE_MASTERED = 6;

    public const int STAGE_UNSTARTED = 0;

    /**
     * Cooldown hours after passing each stage (Stage 1..5). Stage 6 is mastered (no cooldown).
     *
     * @var array<int, int|null>
     */
    public const array STAGE_COOLDOWN_HOURS = [
        1 => 24,
        2 => 72,
        3 => 168,
        4 => 336,
        5 => 720,
        6 => null,
    ];

    /**
     * Stepped cooldown hours after failing each target stage (Stage 1..6).
     * Stage 1 keeps zero cooldown (null) for immediate retry; Stage 2..6 retain current stage with stepped cooldowns.
     *
     * @var array<int, int|null>
     */
    public const array STAGE_FAILURE_COOLDOWN_HOURS = [
        1 => null,
        2 => 24,
        3 => 24,
        4 => 72,
        5 => 72,
        6 => 168,
    ];

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
