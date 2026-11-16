<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Locale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @template TTranslation of Model
 */
trait HasTranslations
{
    /**
     * @return class-string<TTranslation>
     */
    abstract protected function translationModelClass(): string;

    /**
     * @return HasMany<TTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModelClass());
    }

    /**
     * @return HasOne<TTranslation, $this>
     */
    public function translation(?string $locale = null): HasOne
    {
        return $this->hasOne($this->translationModelClass())
            ->where('locale', $locale ?? app()->getLocale());
    }

    /**
     * @return TTranslation|null
     */
    public function resolveTranslation(?string $locale = null): ?Model
    {
        $targetLocale = $locale ?? app()->getLocale();
        $fallbackLocale = Locale::default()->value;

        if ($this->relationLoaded('translations')) {
            /** @var Collection<int, TTranslation> $translations */
            $translations = $this->getRelation('translations');

            return $translations->firstWhere('locale', $targetLocale)
                ?? $translations->firstWhere('locale', $fallbackLocale)
                ?? $translations->first();
        }

        if ($locale === null && $this->relationLoaded('translation')) {
            $translation = $this->getRelation('translation');
            if ($translation instanceof Model) {
                /** @var TTranslation $translation */
                return $translation;
            }
        }

        return $this->translations()->where('locale', $targetLocale)->first()
            ?? ($targetLocale !== $fallbackLocale
                ? $this->translations()->where('locale', $fallbackLocale)->first()
                : null)
            ?? $this->translations()->first();
    }
}
