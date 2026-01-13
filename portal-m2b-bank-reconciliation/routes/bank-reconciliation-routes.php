<?php

/**
 * Bank Reconciliation Routes
 * 
 * Tambahkan route ini ke file routes/web.php di bagian admin routes
 * 
 * Contoh penambahan di web.php:
 * 
 * Route::middleware(['auth', 'verified'])->group(function () {
 *     // ... existing routes ...
 *     
 *     // Bank Reconciliation (admin only)
 *     Route::middleware(['role:admin'])->group(function () {
 *         Route::get('/admin/bank-reconciliation', \App\Livewire\Admin\BankReconciliation::class)
 *             ->name('admin.bank-reconciliation');
 *     });
 * });
 */

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\BankReconciliation;

// ============================================
// COPY ROUTES DI BAWAH INI KE routes/web.php
// ============================================

/*
// Tambahkan di dalam group admin middleware

Route::get('/admin/bank-reconciliation', BankReconciliation::class)
    ->name('admin.bank-reconciliation');

// Atau jika menggunakan prefix admin:

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/bank-reconciliation', BankReconciliation::class)
        ->name('admin.bank-reconciliation');
});
*/

// ============================================
// ALTERNATIF: Include file ini dari web.php
// ============================================

// Di web.php, tambahkan:
// require __DIR__.'/bank-reconciliation-routes.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Bank Reconciliation - Admin Only
    Route::get('/admin/bank-reconciliation', BankReconciliation::class)
        ->name('admin.bank-reconciliation')
        ->middleware('role:admin');
});
