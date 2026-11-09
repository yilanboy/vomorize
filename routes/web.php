<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\ProgressSyncController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/levels', [LevelController::class, 'index'])->name('levels.index');
Route::get('/levels/{level}', [LevelController::class, 'show'])->name('levels.show');
Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
Route::get('/groups/{group}/intro', [GroupController::class, 'intro'])->name('groups.intro');
Route::get('/groups/{group}/quiz', [GroupController::class, 'quiz'])->name('groups.quiz');
Route::post('/groups/{group}/progress', [GroupController::class, 'saveProgress'])
    ->middleware('auth')
    ->name('groups.progress.save');
Route::post('/progress/sync', [ProgressSyncController::class, 'store'])
    ->middleware('auth')
    ->name('progress.sync');

Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
Route::post('/quiz/start', [QuizController::class, 'start'])->name('quiz.start');
Route::get('/quiz/play', [QuizController::class, 'play'])->name('quiz.play');
Route::post('/quiz/complete', [QuizController::class, 'complete'])->name('quiz.complete');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
