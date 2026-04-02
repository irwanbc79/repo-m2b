<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\AttendanceManagement;
use App\Livewire\Admin\LeaveManagement;
use App\Livewire\Admin\ExpenseManagement;

/*
|--------------------------------------------------------------------------
| Mobile App Admin Routes
|--------------------------------------------------------------------------
| Modul manajemen absensi, cuti/izin, dan klaim biaya dari aplikasi mobile.
| Route ini terpisah dari web.php agar tidak mengganggu route yang ada.
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Absensi
    Route::get('/attendance', AttendanceManagement::class)->name('attendance.index');

    // Cuti & Izin
    Route::get('/leaves', LeaveManagement::class)->name('leaves.index');

    // Klaim Biaya
    Route::get('/expenses', ExpenseManagement::class)->name('expenses.index');
});
