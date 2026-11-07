<?php

use App\Models\Vocabulary;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AudioPrompt;

beforeEach(function () {
    Storage::fake('r2');
    Audio::fake();
});

test('fails when --id option is missing or empty', function () {
    $this
        ->artisan('generate:audio')
        ->expectsOutputToContain('Please provide a valid vocabulary ID')
        ->assertFailed();
});

test('fails when --id option contains invalid characters', function () {
    $this
        ->artisan('generate:audio --id=abc')
        ->expectsOutputToContain('Contains invalid characters')
        ->assertFailed();
});

it('can generate audio for a specific vocabulary ID', function () {
    $vocabulary = Vocabulary::factory()->create(['id' => 1]);

    $this
        ->artisan('generate:audio --id=1')
        ->assertSuccessful();

    Storage::disk('r2')
        ->assertExists("vocabulary/$vocabulary->id/word.mp3")
        ->assertExists("vocabulary/$vocabulary->id/sentence.mp3");

    Audio::assertGenerated(fn (AudioPrompt $prompt) => $prompt->contains($vocabulary->word) && $prompt->isFemale());
    Audio::assertGenerated(fn (AudioPrompt $prompt) => $prompt->contains($vocabulary->example_sentence) && $prompt->isFemale());
});

it('can generate audio for multiple comma-separated IDs while excluding others', function () {
    $target1 = Vocabulary::factory()->create(['id' => 1]);
    $excluded = Vocabulary::factory()->create(['id' => 2]);
    $target2 = Vocabulary::factory()->create(['id' => 3]);

    $this
        ->artisan('generate:audio --id=1,3')
        ->assertSuccessful();

    Storage::disk('r2')
        ->assertExists("vocabulary/$target1->id/word.mp3")
        ->assertExists("vocabulary/$target1->id/sentence.mp3")
        ->assertExists("vocabulary/$target2->id/word.mp3")
        ->assertExists("vocabulary/$target2->id/sentence.mp3")
        ->assertMissing("vocabulary/$excluded->id/word.mp3")
        ->assertMissing("vocabulary/$excluded->id/sentence.mp3");
});

it('can generate audio for a range of IDs', function () {
    $targets = Vocabulary::factory()->count(3)->sequence(
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    )->create();
    $outside = Vocabulary::factory()->create(['id' => 4]);

    $this
        ->artisan('generate:audio --id=1-3')
        ->assertSuccessful();

    foreach ($targets as $target) {
        Storage::disk('r2')
            ->assertExists("vocabulary/$target->id/word.mp3")
            ->assertExists("vocabulary/$target->id/sentence.mp3");
    }

    Storage::disk('r2')
        ->assertMissing("vocabulary/$outside->id/word.mp3")
        ->assertMissing("vocabulary/$outside->id/sentence.mp3");
});

it('can generate audio with whitespace in parameter', function () {
    $target1 = Vocabulary::factory()->create(['id' => 1]);
    $target2 = Vocabulary::factory()->create(['id' => 2]);
    $target4 = Vocabulary::factory()->create(['id' => 4]);
    $excluded = Vocabulary::factory()->create(['id' => 3]);

    $this
        ->artisan('generate:audio --id=" 1 - 2 , 4 "')
        ->assertSuccessful();

    Storage::disk('r2')
        ->assertExists("vocabulary/$target1->id/word.mp3")
        ->assertExists("vocabulary/$target2->id/word.mp3")
        ->assertExists("vocabulary/$target4->id/word.mp3")
        ->assertMissing("vocabulary/$excluded->id/word.mp3");
});

it('handles non-existent vocabulary IDs gracefully without generating files', function () {
    $this
        ->artisan('generate:audio --id=9999')
        ->assertSuccessful();

    expect(Storage::disk('r2')->allFiles())->toBeEmpty();
    Audio::assertNothingGenerated();
});
