<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\CashierManager;

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/cashier', CashierManager::class)
            ->name('admin.cashier');
    });
