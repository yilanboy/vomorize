<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GitHubAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'github' => 'Unable to authenticate with GitHub.',
            ]);
        }

        $githubId = (string) $githubUser->getId();
        $email = $githubUser->getEmail();

        // 1. Check by github_id
        $user = User::where('github_id', $githubId)->first();

        if ($user) {
            Auth::login($user);

            return redirect()->intended('/');
        }

        // 2. Check by email if present
        if ($email) {
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                // Verified-email auto-link only
                if ($existingUser->email_verified_at !== null) {
                    $existingUser->github_id = $githubId;
                    $existingUser->save();
                    Auth::login($existingUser);

                    return redirect()->intended('/');
                }

                return redirect()->route('login')->withErrors([
                    'github' => 'An account with this email exists but is not verified. Please log in with password.',
                ]);
            }
        }

        // 3. Create new user
        $newUser = User::create([
            'name' => $githubUser->getName() ?: $githubUser->getNickname() ?: 'Learner',
            'email' => $email ?: "github_{$githubId}@vomorize.local",
            'email_verified_at' => now(),
            'password' => bcrypt(Str::random(32)),
            'github_id' => $githubId,
            'locale' => session('locale', 'zh_TW'),
        ]);

        Auth::login($newUser);

        return redirect()->intended('/');
    }
}
