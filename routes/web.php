<?php

use App\Enums\Locale;
use App\Http\Controllers\Auth\GitHubAuthController;
use App\Http\Controllers\CustomQuizController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupProgressController;
use App\Http\Controllers\GroupQuizController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ProgressMigrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', function (Request $request) {
    $locale = Locale::tryFrom(app()->getLocale()) ?? Locale::ChineseT;

    return redirect('/'.$locale->routeKey());
})->name('root');

// Localized routes
Route::prefix('{locale}')
    ->whereIn('locale', Locale::routeKeys())
    ->group(function () {
        // Home & Learning routes
        Route::get('/', HomeController::class)->name('home');
        Route::get('/levels/{level}', [LevelController::class, 'show'])->name('levels.show');
        Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
        Route::get('/groups/{group}/introduce', [GroupQuizController::class, 'introduce'])->name('groups.introduce');
        Route::get('/groups/{group}/quiz', [GroupQuizController::class, 'quiz'])->name('groups.quiz');
        Route::get('/groups/{group}/result', [GroupQuizController::class, 'result'])->name('groups.result');
        Route::post('/groups/{group}/progress', [GroupProgressController::class, 'store'])
            ->name('groups.progress.store');

        // Custom Quiz
        Route::get('/quiz/custom', [CustomQuizController::class, 'index'])->name('quiz.custom');
        Route::post('/quiz/custom/count', [CustomQuizController::class, 'learnedWordCount'])->name('quiz.custom.count');
        Route::post('/quiz/custom/fetch', [CustomQuizController::class, 'fetchVocabulary'])->name('quiz.custom.fetch');
    });

// Guest progress migration
Route::post('/progress/migrate', [ProgressMigrationController::class, 'store'])->name('progress.migrate');

// Socialite GitHub Login
Route::get('/auth/github/redirect', [GitHubAuthController::class, 'redirect'])->name('auth.github');
Route::get('/auth/github/callback', [GitHubAuthController::class, 'callback'])->name('auth.github.callback');

require __DIR__.'/settings.php';
require __DIR__.'/fortify.php';
