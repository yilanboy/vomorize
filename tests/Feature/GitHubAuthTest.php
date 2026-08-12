<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('redirects to github OAuth provider', function () {
    Socialite::fake('github');

    $response = $this->get('/auth/github/redirect');

    $response->assertRedirect();
});

it('user can login with github', function () {
    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-123',
        'name' => 'Jason Beggs',
        'email' => 'jason@example.com',
    ]));

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect('/');

    $user = User::query()->where('email', 'jason@example.com')->firstOrFail();

    expect($user->email)->toBe('jason@example.com')
        ->and($user->name)->toBe('Jason Beggs')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->github_id)->toBe('github-123')
        ->and($user->github_token)->not->toBeNull()
        ->and($user->github_refresh_token)->not->toBeNull();
});

it('auto-links github account to existing password account with verified email', function () {
    $existing = User::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
        'github_id' => null,
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-123',
        'name' => 'Verified User',
        'email' => 'verified@example.com',
    ]));

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect('/');

    $existing->refresh();

    expect($existing->github_id)
        ->toBe('github-123')
        ->and($existing->email)
        ->toBe('verified@example.com');
    $this->assertAuthenticatedAs($existing);
});

it('set auto-linking github account password to null if existing email is not verified', function () {
    $existing = User::factory()->create([
        'email' => 'unverified@example.com',
        'email_verified_at' => null,
        'github_id' => null,
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'github-123',
        'name' => 'Unverified User',
        'email' => 'unverified@example.com',
    ]));

    $response = $this->get('/auth/github/callback');

    $response->assertRedirect('/');

    $existing->refresh();

    expect($existing->github_id)
        ->toBe('github-123')
        ->and($existing->email)
        ->toBe('unverified@example.com')
        ->and($existing->password)
        ->toBeNull()
        ->and($existing->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($existing);
});
