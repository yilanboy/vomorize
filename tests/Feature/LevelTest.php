<?php

use App\Models\Level;
use App\Models\LevelTranslation;
use Database\Seeders\VocabularySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('level model has translations relationship', function () {
    $level = Level::create(['id' => 1]);

    $translation = LevelTranslation::create([
        'level_id' => $level->id,
        'locale' => 'zh_TW',
        'name' => '等級 1',
        'description' => '基礎入門：核心常用 1,000 單字',
    ]);

    expect($level->translations)->toHaveCount(1)
        ->and($level->translations->first()->name)->toBe('等級 1')
        ->and($translation->level->id)->toBe($level->id);
});

test('vocabulary seeder seeds level translations for zh_TW, zh_CN, and ja', function () {
    $this->seed(VocabularySeeder::class);

    expect(Level::count())->toBe(7)
        ->and(LevelTranslation::count())->toBe(21);

    $level1 = Level::with('translations')->findOrFail(1);

    $zhTw = $level1->translations->firstWhere('locale', 'zh_TW');
    $zhCn = $level1->translations->firstWhere('locale', 'zh_CN');
    $ja = $level1->translations->firstWhere('locale', 'ja');

    expect($zhTw)->not->toBeNull()
        ->and($zhTw->name)->toBe('等級 1')
        ->and($zhTw->description)->toBe('基礎入門：核心常用 1,000 單字')
        ->and($zhCn)->not->toBeNull()
        ->and($zhCn->name)->toBe('等级 1')
        ->and($zhCn->description)->toBe('基础入门：核心常用 1,000 单词')
        ->and($ja)->not->toBeNull()
        ->and($ja->name)->toBe('レベル 1')
        ->and($ja->description)->toBe('初級：よく使われる必須1,000単語');
});

test('home page returns level translations', function () {
    $this->seed(VocabularySeeder::class);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('levels', 7)
        ->has('levels.0.translations')
    );
});

test('level show page returns level translations', function () {
    $this->seed(VocabularySeeder::class);

    $response = $this->get('/levels/1');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('level.id')
        ->has('level.translations')
    );
});
