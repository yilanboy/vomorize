<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizMode: string
{
    case Standard = 'standard';
    case SentenceCloze = 'sentence_cloze';
    case Spelling = 'spelling';

    /**
     * Modes currently enabled for custom quizzes.
     *
     * @return array<int, self>
     */
    public static function availableModes(): array
    {
        return [
            self::Standard,
            self::SentenceCloze,
            self::Spelling,
        ];
    }

    /**
     * String values of modes currently enabled for custom quizzes.
     *
     * @return array<int, string>
     */
    public static function availableValues(): array
    {
        return array_map(
            static fn (self $mode): string => $mode->value,
            self::availableModes(),
        );
    }

    public static function defaultMode(): self
    {
        return self::Standard;
    }
}
