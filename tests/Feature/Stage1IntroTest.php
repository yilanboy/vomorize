<?php

use App\Models\Group;
use App\Models\LearningProgress;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest can access group intro page and receives 30-step session payload', function () {
    $level = Level::factory()->create(['name' => '基礎入門']);
    $group = Group::factory()->create(['level_id' => $level->id, 'sequence' => 1]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $response = $this->get(route('groups.intro', $group));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('groups/Intro')
        ->has('group')
        ->where('group.id', $group->id)
        ->where('group.sequence', 1)
        ->where('progress', null)
        ->has('session.steps', 30)
        ->has('session.correct_audio_url')
        ->has('session.wrong_audio_url')
    );
});

test('unauthenticated or unstarted member accessing group show is redirected to group intro', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    // Member with no progress record
    $response = $this->actingAs($user)->get(route('groups.show', $group));
    $response->assertRedirect(route('groups.intro', $group));

    // Member with stage 0 progress record
    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 0,
        'last_score' => 60,
    ]);

    $response2 = $this->actingAs($user)->get(route('groups.show', $group));
    $response2->assertRedirect(route('groups.intro', $group));
});

test('member who already passed stage 1 accessing group intro is redirected to group show', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    LearningProgress::factory()->create([
        'user_id' => $user->id,
        'level_id' => $level->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 85,
    ]);

    $response = $this->actingAs($user)->get(route('groups.intro', $group));
    $response->assertRedirect(route('groups.show', $group));
});

test('member can save quiz progress via inertia post and stays on intro for result summary', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    Vocabulary::factory()->count(10)->create([
        'level_id' => $level->id,
        'group_id' => $group->id,
    ]);

    $response = $this->actingAs($user)
        ->from(route('groups.intro', $group))
        ->post(route('groups.progress.save', $group), [
            'score' => 90,
        ]);

    $response->assertRedirect(route('groups.intro', $group));

    $this->assertDatabaseHas('learning_progress', [
        'user_id' => $user->id,
        'group_id' => $group->id,
        'stage' => 1,
        'last_score' => 90,
    ]);

    // Following the redirect with flashed quiz_completed renders groups/Intro with updated progress
    $followUp = $this->actingAs($user)->get(route('groups.intro', $group));
    $followUp->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('groups/Intro')
            ->where('progress.stage', 1)
            ->where('progress.status', 'cooldown')
        );
});

test('saving progress requires valid score between 0 and 100', function () {
    $user = User::factory()->create();
    $level = Level::factory()->create();
    $group = Group::factory()->create(['level_id' => $level->id]);

    $response = $this->actingAs($user)->post(route('groups.progress.save', $group), [
        'score' => 150,
    ]);

    $response->assertSessionHasErrors(['score']);
});
