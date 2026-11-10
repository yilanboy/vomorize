<?php

declare(strict_types=1);

namespace App\Enums;

enum QuizType: string
{
    case WordToDefinition = 'word_to_definition';
    case DefinitionToWord = 'definition_to_word';
    case AudioToDefinition = 'audio_to_definition';
}
