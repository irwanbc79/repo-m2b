<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (SAFE MODE)
|--------------------------------------------------------------------------
| API belum diaktifkan penuh.
| Route yang menunjuk ke controller yang belum ada
| DI-NONAKTIFKAN agar tidak merusak route registry.
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH API (DISABLED – CONTROLLER BELUM ADA)
    |--------------------------------------------------------------------------
    |
    | Aktifkan kembali jika AuthController API sudah siap.
    |
    */

    // Route::post('/login', [AuthController::class, 'login']);
    // Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {

        // Route::get('/user', [AuthController::class, 'user']);
        // Route::post('/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | SHIPMENT API (DISABLED)
        |--------------------------------------------------------------------------
        */
        // Route::get('/shipments', [ShipmentController::class, 'index']);
        // Route::get('/shipments/{id}', [ShipmentController::class, 'show']);

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT API (DISABLED)
        |--------------------------------------------------------------------------
        */
        // Route::get('/documents/{id}', [DocumentController::class, 'show']);
    });
});
