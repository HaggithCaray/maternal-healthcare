<?php

use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('sync')->group(function () {
    Route::post('/patients', [SyncController::class, 'registrations'])
        ->name('api.sync.patients');
});
