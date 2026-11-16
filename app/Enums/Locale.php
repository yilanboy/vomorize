<?php

declare(strict_types=1);

namespace App\Enums;

enum Locale: string
{
    case ZhTw = 'zh_TW';
    case Ja = 'ja';
    case ZhCn = 'zh_CN';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $locale): string => $locale->value,
            self::cases(),
        );
    }

    public static function default(): self
    {
        return self::ZhTw;
    }

    public static function isValid(?string $locale): bool
    {
        return $locale !== null && self::tryFrom($locale) !== null;
    }

    public function htmlLang(): string
    {
        return str_replace('_', '-', $this->value);
    }
}
