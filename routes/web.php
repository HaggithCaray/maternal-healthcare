<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\MaternalRecordController;
use App\Http\Controllers\ChildHealthController;
use App\Http\Controllers\SmsGatewayController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SyncController;

// Public Authentication Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // Offline Token & Synchronization Routes
    Route::get('/sync/token', [SyncController::class, 'token'])->name('sync.token');

    // Patient & Clinical Directory Management
    Route::get('/records', [PatientController::class, 'records'])->name('records');
    Route::match(['get', 'post'], '/register', [PatientController::class, 'register'])->name('register');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::post('/patients/{patient}/portal-password', [PatientController::class, 'resetPortalPassword'])->name('patients.portal-password');
    Route::get('/portal', [PatientController::class, 'patientPortal'])->name('patient.portal');

    // Maternal Health Tracking
    Route::match(['get', 'post'], '/maternal', [MaternalRecordController::class, 'maternal'])->name('maternal');

    // Child Health & Immunization Tracking
    Route::match(['get', 'post'], '/growth', [ChildHealthController::class, 'growth'])->name('growth');
    Route::match(['get', 'post'], '/immunization', [ChildHealthController::class, 'immunization'])->name('immunization');

    // SMS Gateway Center (exposes gateway credentials and every patient's phone number)
    Route::middleware('role:admin')->group(function () {
        Route::match(['get', 'post'], '/sms', [SmsGatewayController::class, 'sms'])->name('sms');
        Route::post('/sms/settings', [SmsGatewayController::class, 'updateSmsSettings'])->name('sms.settings');
        Route::get('/sms/status', [SmsGatewayController::class, 'testSmsGatewayConnection'])->name('sms.status');
    });

    // Real-Time Chat & Patient Communication
    Route::match(['get', 'post'], '/messaging', [ChatController::class, 'messaging'])->name('messaging');
    Route::get('/messaging/attachments/{message}', [ChatController::class, 'attachment'])->name('messaging.attachment');

    // Reporting & Administration
    Route::get('/reports', [ReportController::class, 'reports'])->name('reports')->middleware('role:admin');
    Route::get('/admin', [ReportController::class, 'admin'])->name('admin')->middleware('role:admin');
});
