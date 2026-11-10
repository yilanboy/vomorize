<?php

declare(strict_types=1);

use App\Models\Vocabulary;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Laravel\Ai\Prompts\AudioPrompt;

beforeEach(function () {
    Storage::fake('r2');
    Audio::fake();
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => true,
            'errors' => [],
            'result' => ['id' => 'test_zone_id'],
        ], 200),
    ]);

    Config::set('services.cloudflare.key', 'test_key');
    Config::set('services.cloudflare.zone_id', 'test_zone_id');
    Config::set('filesystems.disks.r2.url', 'https://assets.vomorize.com');
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
    Http::assertNothingSent();
});

it('purges cloudflare cdn cache with vocabulary prefixes after generating audio', function () {
    $vocabulary = Vocabulary::factory()->create(['id' => 1]);

    $this
        ->artisan('generate:audio --id=1')
        ->expectsOutputToContain('Purging Cloudflare CDN cache...')
        ->expectsOutputToContain('Cloudflare CDN cache purged successfully.')
        ->assertSuccessful();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache'
            && $request['prefixes'] === ['assets.vomorize.com/vocabulary/1/'];
    });
});

it('fails and outputs error when cloudflare cdn purge fails', function () {
    Http::swap(new Factory);
    Http::fake([
        'https://api.cloudflare.com/client/v4/zones/test_zone_id/purge_cache' => Http::response([
            'success' => false,
            'errors' => [
                ['message' => 'Zone not found or unauthorized'],
            ],
        ], 403),
    ]);

    Vocabulary::factory()->create(['id' => 1]);

    $this
        ->artisan('generate:audio --id=1')
        ->expectsOutputToContain('Failed to purge Cloudflare CDN cache: Cloudflare CDN purge failed (Status 403): Zone not found or unauthorized')
        ->assertFailed();
});

it('fails and outputs error when cloudflare credentials are missing', function () {
    Config::set('services.cloudflare.zone_id', null);

    Vocabulary::factory()->create(['id' => 1]);

    $this
        ->artisan('generate:audio --id=1')
        ->expectsOutputToContain('Failed to purge Cloudflare CDN cache: Cloudflare Zone ID is not configured (CLOUDFLARE_ZONE_ID).')
        ->assertFailed();
});
