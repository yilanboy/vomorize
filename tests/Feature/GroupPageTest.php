<?php

use App\Models\Group;
use App\Models\Level;
use App\Models\LevelTranslation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * The trail above a group is assembled in the browser, because a language switch never reaches
 * the server. What the response owes it is therefore the raw materials rather than finished
 * labels: the level's stored names, and the group's own position within its level.
 */
test('it carries the level names the breadcrumb trail is built from', function () {
    $user = User::factory()->create();
    $level = Level::create(['id' => 1]);

    foreach (['zh-tw' => '等級 1', 'zh-cn' => '等级 1', 'ja' => 'レベル 1'] as $locale => $name) {
        LevelTranslation::create([
            'level_id' => $level->id,
            'locale' => $locale,
            'name' => $name,
            'description' => '',
        ]);
    }

    $group = Group::create(['level_id' => $level->id, 'sequence' => 1]);

    $response = $this->actingAs($user)->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Show')
        ->where('group.sequence', 1)
        ->where('level.id', $level->id)
        ->has('level.translations', 3)
    );

    $names = collect($response->viewData('page')['props']['level']['translations'])
        ->pluck('name', 'locale');

    expect($names['zh-tw'])->toBe('等級 1')
        ->and($names['zh-cn'])->toBe('等级 1')
        ->and($names['ja'])->toBe('レベル 1');
});

/**
 * The group's own label is interface copy rather than stored content, and the shared props carry
 * every locale's copy on every response — so the client can name the group in whichever language
 * the learner switches to, without asking the server again.
 */
test('it carries the group label template for every locale', function () {
    $user = User::factory()->create();
    $level = Level::create(['id' => 1]);
    $group = Group::create(['level_id' => $level->id, 'sequence' => 1]);

    $response = $this->actingAs($user)->get(route('groups.show', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('translations.app.zh-tw.group_title', '組別 :id')
        ->where('translations.app.zh-cn.group_title', '组别 :id')
        ->where('translations.app.ja.group_title', 'グループ :id')
    );
});
