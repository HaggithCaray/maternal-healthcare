<?php

use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('sync')->group(function () {
    Route::get('/token', [SyncController::class, 'token'])->name('api.sync.token');
    Route::post('/patients', [SyncController::class, 'registrations'])->name('api.sync.patients');
    Route::post('/batch', [SyncController::class, 'registrations'])->name('api.sync.batch');
});
