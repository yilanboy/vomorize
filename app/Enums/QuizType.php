<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizType: string
{
    case WordToDefinition = 'word_to_definition';
    case DefinitionToWord = 'definition_to_word';
    case AudioToDefinition = 'audio_to_definition';
    case Spelling = 'spelling';

    /**
     * Core multiple-choice quiz types for SRS stages 1-6.
     *
     * @return array<int, self>
     */
    public static function coreTypes(): array
    {
        return [
            self::WordToDefinition,
            self::DefinitionToWord,
            self::AudioToDefinition,
        ];
    }
}
