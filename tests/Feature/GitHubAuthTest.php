<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

it('redirects to github OAuth provider', function () {
    $response = $this->get('/auth/github');

    $response->assertRedirect();
});

it('creates a new user from github callback', function () {
    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('123456');
    $abstractUser->shouldReceive('getName')->andReturn('GitHub Learner');
    $abstractUser->shouldReceive('getNickname')->andReturn('ghlearner');
    $abstractUser->shouldReceive('getEmail')->andReturn('github@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect('/');
    $this->assertDatabaseHas('users', [
        'email' => 'github@example.com',
        'github_id' => '123456',
    ]);
    $this->assertAuthenticated();
});

it('auto-links github account to existing password account with verified email', function () {
    $existing = User::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
        'github_id' => null,
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('789012');
    $abstractUser->shouldReceive('getName')->andReturn('Verified User');
    $abstractUser->shouldReceive('getNickname')->andReturn('verifieduser');
    $abstractUser->shouldReceive('getEmail')->andReturn('verified@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect('/');
    expect($existing->fresh()->github_id)->toBe('789012');
    $this->assertAuthenticatedAs($existing);
});

it('rejects auto-linking github account if existing email is not verified', function () {
    $existing = User::factory()->create([
        'email' => 'unverified@example.com',
        'email_verified_at' => null,
        'github_id' => null,
    ]);

    $abstractUser = Mockery::mock(SocialiteUser::class);
    $abstractUser->shouldReceive('getId')->andReturn('999999');
    $abstractUser->shouldReceive('getName')->andReturn('Unverified User');
    $abstractUser->shouldReceive('getNickname')->andReturn('unverifieduser');
    $abstractUser->shouldReceive('getEmail')->andReturn('unverified@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect('/login');
    expect($existing->fresh()->github_id)->toBeNull();
    $this->assertGuest();
});
