<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — M2B Executive Mobile App
|--------------------------------------------------------------------------
| Base URL: portal.m2b.co.id/api/v1/
| Auth: Laravel Sanctum (Bearer token)
*/

Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ── Protected ───────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout',  [AuthController::class, 'logout']);
        Route::get('/auth/profile',  [AuthController::class, 'profile']);

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

        // Dashboard
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    });
});
