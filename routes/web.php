<?php

declare(strict_types=1);

use App\Http\Controllers\CustomQuizSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupIntroController;
use App\Http\Controllers\GroupProgressController;
use App\Http\Controllers\GroupQuizController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ProgressSyncController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizPlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/levels', [LevelController::class, 'index'])->name('levels.index');
Route::get('/levels/{level}', [LevelController::class, 'show'])->name('levels.show');
Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
Route::get('/groups/{group}/intro', [GroupIntroController::class, 'show'])->name('groups.intro');
Route::get('/groups/{group}/quiz', [GroupQuizController::class, 'show'])->name('groups.quiz');
Route::post('/groups/{group}/progress', [GroupProgressController::class, 'store'])
    ->middleware('auth')
    ->name('groups.progress.save');
Route::post('/progress/sync', [ProgressSyncController::class, 'store'])
    ->middleware('auth')
    ->name('progress.sync');

Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
Route::post('/quiz/start', [CustomQuizSessionController::class, 'store'])->name('quiz.start');
Route::get('/quiz/play', [QuizPlayController::class, 'show'])->name('quiz.play');
Route::post('/quiz/complete', [CustomQuizSessionController::class, 'destroy'])->name('quiz.complete');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
