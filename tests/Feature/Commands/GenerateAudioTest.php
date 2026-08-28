<?php

use App\Models\Vocabulary;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AudioPrompt;

beforeEach(function () {
    Storage::fake();
    Audio::fake();
});

test('audio generation', function () {
    $vocabulary = Vocabulary::factory()->create([
        'id' => 1,
        'word' => 'test',
        'example_sentence' => 'This is a test sentence',
    ]);

    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', $vocabulary->id)
        ->expectsQuestion('Maximum number of vocabularies to process', 1)
        ->assertSuccessful();

    Storage::assertExists("vocabulary/$vocabulary->id/word.mp3");
    Storage::assertExists("vocabulary/$vocabulary->id/sentence.mp3");

    Audio::assertGenerated(function (AudioPrompt $prompt) {
        return $prompt->text === 'test' && $prompt->isFemale();
    });

    Audio::assertGenerated(function (AudioPrompt $prompt) {
        return $prompt->text === 'This is a test sentence' && $prompt->isFemale();
    });
});

test('respects startFrom and limit options', function () {
    Vocabulary::factory()->count(10)->sequence(fn ($sequence) => ['id' => $sequence->index + 1])->create();

    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', 3)
        ->expectsQuestion('Maximum number of vocabularies to process', 2)
        ->assertSuccessful();

    Storage::assertMissing('vocabulary/1/word.mp3');
    Storage::assertMissing('vocabulary/1/sentence.mp3');
    Storage::assertMissing('vocabulary/2/word.mp3');
    Storage::assertMissing('vocabulary/2/sentence.mp3');
    Storage::assertExists('vocabulary/3/word.mp3');
    Storage::assertExists('vocabulary/3/sentence.mp3');
    Storage::assertExists('vocabulary/4/word.mp3');
    Storage::assertExists('vocabulary/4/sentence.mp3');
    Storage::assertMissing('vocabulary/5/word.mp3');
    Storage::assertMissing('vocabulary/5/sentence.mp3');
});

it('fails on invalid vocabulary ID', function () {
    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', 0)
        ->expectsPromptsError('Invalid vocabulary ID')
        ->assertFailed();

    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', -1)
        ->expectsPromptsError('Invalid vocabulary ID')
        ->assertFailed();

    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', 999)
        ->expectsQuestion('Maximum number of vocabularies to process', 10)
        ->expectsPromptsInfo('No vocabularies found to process.')
        ->assertSuccessful();
});

it('fails on invalid limit number', function () {
    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', 1)
        ->expectsQuestion('Maximum number of vocabularies to process', 0)
        ->expectsPromptsError('Invalid limit value')
        ->assertFailed();

    $this->artisan('generate:audio')
        ->expectsQuestion('Start from vocabulary ID', 1)
        ->expectsQuestion('Maximum number of vocabularies to process', -1)
        ->expectsPromptsError('Invalid limit value')
        ->assertFailed();
});
