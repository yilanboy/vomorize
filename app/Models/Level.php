<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class)->orderBy('sequence');
    }

    public function vocabularies(): HasMany
    {
        return $this->hasMany(Vocabulary::class);
    }
}
