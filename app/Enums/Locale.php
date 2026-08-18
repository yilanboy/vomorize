<?php

namespace App\Enums;

use ArchTech\Enums\Values;

enum Locale: string
{
    use Values;

    case JP = 'ja';
    case ZH_CN = 'zh_CN';
    case ZH_TW = 'zh_TW';

    public function getLabel(): string
    {
        return match ($this) {
            self::JP => '日本語',
            self::ZH_CN => '简体中文',
            self::ZH_TW => '繁體中文',
        };
    }

    public static function getLabels(): array
    {
        return [
            self::JP->value => '日本語',
            self::ZH_CN->value => '简体中文',
            self::ZH_TW->value => '繁體中文',
        ];
    }

    /**
     * @return string[]
     */
    public static function routeKeys(): array
    {
        return array_map(fn (self $locale) => str_replace('_', '-', strtolower($locale->value)), self::cases());
    }

    public function routeKey(): string
    {
        return str_replace('_', '-', strtolower($this->value));
    }

    public static function fromRouteKey(?string $routeKey): ?self
    {
        if (! $routeKey) {
            return null;
        }

        $normalized = str_replace('_', '-', strtolower($routeKey));

        return match ($normalized) {
            'ja' => self::JP,
            'zh-cn' => self::ZH_CN,
            'zh-tw' => self::ZH_TW,
            default => null,
        };
    }
}
