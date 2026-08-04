<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class VocabularyTranslatorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return 'You are a professional linguist and translator. Translate English vocabulary words and example sentences into the requested target locales. Ensure definitions match the specified part of speech, all translated definitions across target locales share the exact same word sense / meaning as demonstrated in the example sentence and any existing reference translations, and example translations are natural and contextual.';
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'translations' => $schema->array()->items(
                $schema->object(fn (JsonSchema $s) => [
                    'word' => $s->string()->required(),
                    'results' => $s->array()->items(
                        $s->object(fn (JsonSchema $sub) => [
                            'locale' => $sub->string()->required(),
                            'definition' => $sub->string()->required(),
                            'example_translation' => $sub->string()->required(),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
