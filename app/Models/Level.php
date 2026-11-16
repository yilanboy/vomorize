<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property-read string $name
 * @property-read string $description
 */
class Level extends Model
{
    /** @use HasFactory<LevelFactory> */
    use HasFactory;

    /** @use HasTranslations<LevelTranslation> */
    use HasTranslations;

    protected $fillable = [
        'id',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'name',
        'description',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'translations',
    ];

    /**
     * @return class-string<LevelTranslation>
     */
    protected function translationModelClass(): string
    {
        return LevelTranslation::class;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            $translation = $this->resolveTranslation();

            return $translation !== null ? $translation->name : '';
        });
    }

    /**
     * @return Attribute<string, never>
     */
    protected function description(): Attribute
    {
        return Attribute::get(function (): string {
            $translation = $this->resolveTranslation();

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
