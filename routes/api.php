<?php

use App\Http\Controllers\ProgressSyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('progress/sync', [ProgressSyncController::class, 'store'])->name('progress.sync');
});
