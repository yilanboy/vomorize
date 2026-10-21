<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LevelController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/levels', [LevelController::class, 'index'])->name('levels.index');
Route::get('/levels/{level}', [LevelController::class, 'show'])->name('levels.show');
Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
