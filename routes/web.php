<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SyncController;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::get('/sync/token', [SyncController::class, 'token'])->name('sync.token');

    Route::controller(PageController::class)->group(function () {
        Route::get('/records', 'records')->name('records');
        Route::match(['get', 'post'], '/register', 'register')->name('register');
        Route::match(['get', 'post'], '/immunization', 'immunization')->name('immunization');
        Route::match(['get', 'post'], '/sms', 'sms')->name('sms');
        Route::post('/sms/settings', 'updateSmsSettings')->name('sms.settings');
        Route::get('/sms/status', 'testSmsGatewayConnection')->name('sms.status');
        Route::match(['get', 'post'], '/messaging', 'messaging')->name('messaging');
        Route::match(['get', 'post'], '/growth', 'growth')->name('growth');
        Route::match(['get', 'post'], '/maternal', 'maternal')->name('maternal');
        Route::get('/reports', 'reports')->name('reports');

        Route::get('/portal', 'patientPortal')->name('patient.portal');
        Route::get('/admin', 'admin')->name('admin')->middleware('role:admin');
    });
});
