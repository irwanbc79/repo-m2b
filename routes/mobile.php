<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BankReconciliationExportController;
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

    // Bank Reconciliation Export
    Route::get('/bank-reconciliation/export/pdf',   [BankReconciliationExportController::class, 'exportPdf'])  ->name('bank-reconciliation.export.pdf');
    Route::get('/bank-reconciliation/export/excel', [BankReconciliationExportController::class, 'exportExcel'])->name('bank-reconciliation.export.excel');

    // Print Voucher per transaksi
    Route::get('/bank-reconciliation/voucher/{id}', [BankReconciliationExportController::class, 'printVoucher'])->name('bank-reconciliation.voucher');
});
