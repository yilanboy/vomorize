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
            info("Generating word audio for [ID: {$vocabulary->id}] {$vocabulary->word}");

            $wordPath = "vocabulary/$vocabulary->id/word.mp3";
            $sentencePath = "vocabulary/$vocabulary->id/sentence.mp3";

            retry(
                5,
                function () use ($vocabulary, $wordPath) {
                    Audio::of($vocabulary->word)
                        ->female()
                        ->instructions(
                            'Speak the vocabulary clearly as a professional English teacher.',
                        )
                        ->generate()
                        ->storeAs($wordPath);
                },
                seconds(10),
            );

            info("Generating example sentence audio for [ID: {$vocabulary->id}]: \"{$vocabulary->example_sentence}\"");

            retry(
                5,
                function () use ($vocabulary, $sentencePath) {
                    Audio::of($vocabulary->example_sentence)
                        ->female()
                        ->instructions(
                            'Speak the sentence as a native English speaker.',
                        )
                        ->generate()
                        ->storeAs($sentencePath);
                },
                seconds(10),
            );
        }

        info("Audio generation completed for {$vocabularies->count()} vocabularies. From {$vocabularies->first()->id} to {$vocabularies->last()->id}");
    }
}
