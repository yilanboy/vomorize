<?php

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    /**
     * @return HasMany<Group, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<Vocabulary, $this>
     */
    public function vocabularies(): HasMany
    {
        return $this->hasMany(Vocabulary::class);
    }
}
