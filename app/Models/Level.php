<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property-read string $name
 * @property-read string $description
 */
#[Appends(['name', 'description'])]
class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    /**
     * @return HasMany<LevelTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(LevelTranslation::class);
    }

    /**
     * @return HasOne<LevelTranslation, $this>
     */
    public function translation(?string $locale = null): HasOne
    {
        return $this->hasOne(LevelTranslation::class)
            ->where('locale', $locale ?? app()->getLocale());
    }

    /**
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            $translation = $this->translation;

            return $translation !== null ? $translation->name : '';
        });
    }

    /**
     * @return Attribute<string, never>
     */
    protected function description(): Attribute
    {
        return Attribute::get(function (): string {
            $translation = $this->translation;

            return $translation !== null ? $translation->description : '';
        });
    }

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

    /**
     * @return HasMany<LearningProgress, $this>
     */
    public function learningProgress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }
}
