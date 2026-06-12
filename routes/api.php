<?php

use App\Http\Controllers\Api\MoraLeadWebhookController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\FieldDocController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\SsoController;
use App\Http\Controllers\Api\V1\VisitController;
use App\Http\Controllers\Api\V1\PayrollController;
use Illuminate\Support\Facades\Route;

// ── MORA Chat Lead Webhook (dari m2b.co.id) ────────────────────────────────
Route::post('/mora-lead-incoming', [MoraLeadWebhookController::class, 'store'])
    ->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| API Routes — M2B Executive Mobile App
|--------------------------------------------------------------------------
| Base URL: portal.m2b.co.id/api/v1/
| Auth: Laravel Sanctum (Bearer token)
*/

Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────────────────────
    Route::middleware('throttle:10,1')->post('/auth/login', [AuthController::class, 'login']);

    // ── Protected ───────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout',       [AuthController::class, 'logout']);
        Route::get('/auth/profile',       [AuthController::class, 'profile']);
        Route::post('/auth/sso-token',    [SsoController::class,  'generateToken']);

        // Attendance
        Route::prefix('attendance')->group(function () {
            Route::get('/locations', [AttendanceController::class, 'locations']);
            Route::post('/checkin',  [AttendanceController::class, 'checkin']);
            Route::post('/checkout', [AttendanceController::class, 'checkout']);
            Route::get('/history',   [AttendanceController::class, 'history']);
            Route::get('/today',     [AttendanceController::class, 'today']);
            Route::post('/sync',     [AttendanceController::class, 'sync']);
        });

        // Leave
        Route::prefix('leaves')->group(function () {
            Route::get('/',          [LeaveController::class, 'index']);
            Route::post('/request',  [LeaveController::class, 'store']);
            Route::get('/{id}',      [LeaveController::class, 'show'])->where('id', '[0-9]+');
        });

        // Expenses
        Route::prefix('expenses')->group(function () {
            Route::get('/',          [ExpenseController::class, 'index']);
            Route::post('/claim',    [ExpenseController::class, 'store']);
            Route::get('/{id}',      [ExpenseController::class, 'show'])->where('id', '[0-9]+');
        });

        // Client Visits
        Route::prefix('visits')->group(function () {
            Route::post('/checkin',  [VisitController::class, 'store']);
            Route::get('/history',   [VisitController::class, 'history']);
        });

        // Payroll / Slip Gaji
        Route::prefix('payroll')->group(function () {
            Route::get('/',      [PayrollController::class, 'index']);
            Route::get('/{id}',  [PayrollController::class, 'show'])->where('id', '[0-9]+');
        });

        // Dashboard
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

        // Field Documentation (petugas lapangan & staf yang merangkap)
        Route::prefix('field-docs')->group(function () {
            Route::get('/shipments/search', [FieldDocController::class, 'searchShipments']);
            Route::post('/upload',          [FieldDocController::class, 'upload']);
            Route::get('/history',          [FieldDocController::class, 'history']);
        });
    });
});
