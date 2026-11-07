<?php

namespace App\Console\Commands;

use App\Actions\ParseIdRange;
use App\Models\Vocabulary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Ai\Audio;

#[Signature('generate:audio
    {--id= : Specific vocabulary ID to generate audio for (e.g. --id=1, --id=1,3,5, or --id=1-1000)}
')]
#[Description('Generate audio for a specific vocabulary ID or range')]
class GenerateAudio extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ParseIdRange $parseIdRange): int
    {
        $id = $this->option('id');

        if ($id === null || trim($id) === '') {
            $this->error('Please provide a valid vocabulary ID. For example: --id=1, --id=1,3,5, or --id=1-1000');

            return Command::FAILURE;
        }

        // Only allow numbers, commas, hyphens, and whitespace
        if (! preg_match('/^[0-9,\-\s]+$/', $id)) {
            $this->error('Contains invalid characters. Only numbers, commas, and hyphens are allowed.');

            return Command::FAILURE;
        }

        $ids = $parseIdRange->handle($id);

        if (empty($ids)) {
            $this->error('No valid vocabulary IDs found in the provided parameter.');

            return Command::FAILURE;
        }

        Vocabulary::query()
            ->whereIn('id', $ids)
            ->lazyById()
            ->each(function ($vocabulary) {
                $wordPath = "vocabulary/$vocabulary->id/word.mp3";
                $sentencePath = "vocabulary/$vocabulary->id/sentence.mp3";

                $this->info("Generating audio for vocabulary ID: $vocabulary->id");
                $this->info("Generating word audio for $vocabulary->word (ID: $vocabulary->id)...");

                retry(
                    5,
                    function () use ($vocabulary, $wordPath, $sentencePath) {
                        Audio::of($vocabulary->word)
                            ->female()
                            ->instructions(
                                "Read this word as a native speaker. the example sentence is: $vocabulary->example_sentence",
                            )
                            ->generate()
                            ->storePubliclyAs($wordPath);

                        Audio::of($vocabulary->example_sentence)
                            ->female()
                            ->instructions('Read as a native speaker.')
                            ->generate()
                            ->storePubliclyAs($sentencePath);
                    },
                    10_000,
                );

                $this->info('Done!');
            });

        return Command::SUCCESS;
    }
}
