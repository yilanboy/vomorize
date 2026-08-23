<?php

declare(strict_types=1);

namespace App\Enums;

enum Locale: string
{
    case Japanese = 'ja';
    case Chinese = 'zh_CN';
    case ChineseT = 'zh_TW';

    public function routeKey(): string
    {
        return str_replace('_', '-', strtolower($this->value));
    }

    public function label(): string
    {
        return match ($this) {
            self::Japanese => '日本語',
            self::Chinese => '简体中文',
            self::ChineseT => '繁體中文',
        };
    }

    /**
     * @return string[]
     */
    public static function routeKeys(): array
    {
        return array_map(fn (self $locale) => $locale->routeKey(), self::cases());
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromRouteKey(?string $routeKey): ?self
    {
        if (! $routeKey) {
            return null;
        }

        $normalized = str_replace('_', '-', strtolower($routeKey));

        return match ($normalized) {
            'ja' => self::Japanese,
            'zh-cn' => self::Chinese,
            'zh-tw' => self::ChineseT,
            default => null,
        };
    }

    public static function tryFromValueOrRouteKey(?string $value): ?self
    {
        if (! $value) {
            return null;
        }

        return self::tryFrom($value) ?? self::fromRouteKey($value);
    }
}
