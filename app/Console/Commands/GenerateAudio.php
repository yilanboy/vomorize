<?php

namespace App\Console\Commands;

use App\Models\Vocabulary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Audio;
use Throwable;

use function Illuminate\Support\seconds;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\number;

#[Signature('generate:audio')]
#[Description('Command to generate the vocabulary and the example sentence audio, then upload to R2')]
class GenerateAudio extends Command
{
    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $startFrom = number(
            label: 'Start from vocabulary ID',
            placeholder: 'Default value is 1',
            default: 1
        );

        if ($startFrom <= 0) {
            error('Invalid vocabulary ID');

            $this->fail();
        }

        $limit = number(
            label: 'Maximum number of vocabularies to process',
            placeholder: 'Default value is 100',
            default: 100
        );

        if ($limit <= 0) {
            error('Invalid limit value');

            $this->fail();
        }

        $audioTypes = multiselect(
            label: 'What audio do you want to generate?',
            options: ['Word', 'Sentence'],
            default: ['Word', 'Sentence'],
            required: true
        );

        $vocabularies = Vocabulary::query()
            ->where('id', '>=', $startFrom)
            ->limit((int) $limit)
            ->get();

        if ($vocabularies->isEmpty()) {
            info('No vocabularies found to process.');

            return;
        }

        info("Found {$vocabularies->count()} vocabularies to process.");

        foreach ($vocabularies as $vocabulary) {
            $wordPath = "vocabulary/$vocabulary->id/word.mp3";
            $sentencePath = "vocabulary/$vocabulary->id/sentence.mp3";

            if (in_array('Word', $audioTypes)) {
                info("Generating word audio for [ID: {$vocabulary->id}] {$vocabulary->word}");

                $wordInstructions = collect([
                    'Speak this English word clearly and accurately as a native English speaker in standard dictionary citation form.',
                    $vocabulary->pronunciation ? "IPA / Pronunciation: {$vocabulary->pronunciation}." : null,
                    $vocabulary->part_of_speech ? "Part of speech: {$vocabulary->part_of_speech}." : null,
                    $vocabulary->example_sentence ? "Context sentence for reference: \"{$vocabulary->example_sentence}\"." : null,
                    'Ensure correct syllable stress and natural falling intonation. Pronounce ONLY the target word itself, without reading the context sentence, phonetics, or any extra explanation.',
                ])->filter()->implode(' ');

                retry(
                    5,
                    function () use ($vocabulary, $wordPath, $wordInstructions) {
                        Audio::of($vocabulary->word)
                            ->female()
                            ->instructions($wordInstructions)
                            ->generate()
                            ->storeAs($wordPath);
                    },
                    seconds(10),
                );
            }

            if (in_array('Sentence', $audioTypes)) {
                info("Generating example sentence audio for [ID: {$vocabulary->id}]: \"{$vocabulary->example_sentence}\"");

                retry(
                    5,
                    function () use ($vocabulary, $sentencePath) {
                        Audio::of($vocabulary->example_sentence)
                            ->female()
                            ->instructions(
                                'Speak the sentence naturally and clearly as a native English speaker with natural rhythm, stress, and intonation.',
                            )
                            ->generate()
                            ->storeAs($sentencePath);
                    },
                    seconds(10),
                );
            }
        }

        info("Audio generation completed for {$vocabularies->count()} vocabularies. From {$vocabularies->first()->id} to {$vocabularies->last()->id}");
    }
}
