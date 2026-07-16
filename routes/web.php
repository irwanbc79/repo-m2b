<?php

use Illuminate\Support\Facades\Route;

// Fallback: jika LiteSpeed Cache atau browser melakukan GET ke /lw-update
// (seharusnya POST), redirect ke dashboard daripada menampilkan error 405
Route::get('/lw-update', fn() => redirect('/admin/dashboard'));
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Models
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Document;
use App\Models\Shipment;

// Livewire Controllers (Customer)
use App\Livewire\Customer\Dashboard as CustomerDashboard;
use App\Livewire\Customer\ShipmentList;
use App\Livewire\Customer\ShipmentDetail;
use App\Livewire\Customer\CreateShipment;
use App\Livewire\Customer\Profile as CustomerProfile;
use App\Livewire\Customer\KursPajakPage;
use App\Livewire\Customer\CustomsCalculator as CustomerCalculator;

// Livewire Controllers (Admin)
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\ShipmentManagement;
use App\Livewire\Admin\CustomerManagement;
use App\Livewire\Admin\InvoiceManager;
use App\Livewire\Admin\QuotationManager;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\Accounting\ChartOfAccounts;
use App\Livewire\Admin\AuditLogManager;
use App\Livewire\Admin\CustomsCalculator as AdminCalculator;
use App\Livewire\Admin\VendorManagement;
use App\Livewire\Admin\JobCostingManager;

// Customer Survey Routes
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\Admin\SurveyAdminController;
use App\Http\Controllers\Api\HsCodeApiController;

// IMPORT CLASS INBOX (WAJIB ADA)
use App\Livewire\Admin\EmailInbox;
use App\Http\Controllers\Admin\EmailAttachmentController;
use App\Livewire\Admin\UserRequestManager;
use App\Http\Controllers\Customer\InvoiceController;

use Barryvdh\DomPDF\Facade\Pdf;

/*
 |--------------------------------------------------------------------------
 | Web Routes
 |--------------------------------------------------------------------------
 |
 | Berikut file routes/web.php yang telah diperbarui.
 | Perhatian khusus: route /debug-email telah diperbaiki agar tidak memicu
 | IMAP 'SEARCH' kosong yang menyebabkan error pada beberapa server.
 |
 */

// --- HALAMAN UTAMA ---
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->role === 'customer' ? redirect()->route('customer.dashboard') : redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('/admin/inbox/attachment/{mailbox}/{id}', [EmailAttachmentController::class , 'download'])
    ->name('admin.inbox.attachment')
    ->middleware(['web', 'auth', 'admin']); // sesuaikan middleware anda

Route::get('/admin/inbox/body/{id}', [EmailAttachmentController::class , 'showBody'])
    ->name('admin.inbox.body')
    ->middleware(['web', 'auth', 'admin']);


// --- GUEST ROUTES (LOGIN, REGISTER, FORGOT PASSWORD) ---
Route::middleware('guest')->group(function () {
    // Login - throttle 5 percobaan per menit untuk mencegah brute force
    Route::get('/login', function () {
            return view('auth.login'); }
        )->name('login');
        Route::post('/login', function (Request $request) {
            $credentials = $request->validate(['email' => 'required', 'password' => 'required']);
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
                $primaryRole = Auth::user()->getPrimaryRole();
                return $primaryRole === 'customer' ? redirect()->intended(route('customer.dashboard')) : redirect()->intended(route('admin.dashboard'));
            }
            return back()->withErrors(['email' => 'Email salah.']);
        }
        )->middleware('throttle:5,1');

        // Register
        Route::get('/register', function () {
            return view('auth.register'); }
        )->name('register');
        Route::get("/register/success", function () {
            return view("auth.register-success"); }
        )->name("register.success");
        Route::post('/register', function (Request $request) {
            $request->validate([
                'name' => 'required|string|max:150',
                'company_name' => 'required|string|max:200',
                'position' => 'required|string|max:100',
                'email' => 'required|email|unique:users',
                'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/'],
                'address' => 'required|string|min:10|max:500',
                'city' => 'required|string|max:100',
                'npwp' => ['nullable', 'string', 'regex:/^[0-9]{15,16}$/'],
                'trade_type' => 'required|in:import,export,both,domestic',
                'trade_plan' => 'required|string|min:10|max:1000',
                'password' => 'required|confirmed|min:8',
            ], [
                'position.required' => 'Mohon isi jabatan Anda sebagai perwakilan perusahaan.',
                'phone.regex' => 'Nomor HP tidak valid. Gunakan format Indonesia, contoh 08xxxxxxxxxx.',
                'address.min' => 'Alamat terlalu pendek, mohon isi alamat lengkap.',
                'npwp.regex'  => 'NPWP harus 15 atau 16 digit angka.',
                'trade_type.required' => 'Pilih kebutuhan layanan Anda (Impor / Ekspor / Keduanya / Domestik).',
                'trade_plan.required' => 'Mohon jelaskan rencana pengiriman / komoditas Anda.',
            ]);

            $user = null;
            DB::transaction(function () use ($request, &$user) {
                    $user = User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => Hash::make($request->password),
                        'role' => 'customer',
                        'is_active' => false, // menunggu persetujuan admin
                    ]);

                    Customer::create([
                        'user_id' => $user->id,
                        'customer_code' => Customer::generateCustomerCode(),
                        'company_name' => $request->company_name,
                        'position' => $request->position,
                        'phone' => $request->phone,
                        'address' => $request->address,
                        'city' => $request->city,
                        'npwp' => $request->npwp,
                        'trade_type' => $request->trade_type,
                        'trade_plan' => $request->trade_plan,
                    ]);
                }
                );

                // Kirim email verifikasi
                $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'verification.verify', now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
                );
                \Mail::to($user->email)->send(new \App\Mail\VerifyEmailMail($user, $verificationUrl));

                return redirect()->route('register.success');
            }
            );


            // Forgot Password
            Route::get('/forgot-password', function () {
            return view('auth.forgot-password'); }
        )->name('password.request');
        Route::post('/forgot-password', function (Request $request) {
            $request->validate(['email' => 'required|email']);

            $user = \App\Models\User::where('email', $request->email)->first();

            // Hanya kirim email custom untuk CUSTOMER
            if ($user && $user->role === 'customer') {
                $token = \Illuminate\Support\Str::random(64);
                \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => \Hash::make($token), 'created_at' => now()]
                );

                // Clean up expired tokens older than 2 hours
                \DB::table('password_reset_tokens')->where('created_at', '<', now()->subHours(2))->delete();

                $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));
                \Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $resetUrl));
            }
            else {
                // Untuk non-customer (staf), gunakan default Laravel
                Password::sendResetLink($request->only('email'));
            }

            return back()->with('status', 'Jika email terdaftar, link reset password telah dikirim.');
        }
        )->name('password.email');


        Route::get('/reset-password/{token}', function ($token, Request $request) {
            return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
        }
        )->name('password.reset');

        Route::post('/reset-password', function (Request $request) {
            $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|confirmed']);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $pass) {
                $user->forceFill(['password' => \Hash::make($pass)])->save();

                // Kirim konfirmasi HANYA untuk customer
                if ($user->role === 'customer') {
                    \Mail::to($user->email)->send(new \App\Mail\PasswordChangedMail($user));
                }
            }
            );

            return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Password berhasil diubah! Silakan login.')
            : back()->withErrors(['email' => [__($status)]]);
        }
        )->name('password.update');
    });


// --- GOOGLE OAUTH ROUTES ---
Route::get('auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirectToGoogle'])->name('login.google');
Route::get('auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'handleGoogleCallback'])->name('login.google.callback');

// Lengkapi data untuk pendaftar Google baru (akun dibuat setelah data valid diisi)
Route::get('register/complete', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'showCompleteProfile'])->name('register.complete');
Route::post('register/complete', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'storeCompleteProfile'])->name('register.complete.store')->middleware('throttle:10,1');


// --- AUTH COMMON ---
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// GET logout dihapus untuk mencegah CSRF attack via <img src="/logout">
// Gunakan POST /logout saja (sudah ada di atas)
Route::get('/email/verify', function () {
    return view('auth.verify-email'); })->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function ($id, $hash, Request $request) {
    // Cari user berdasarkan ID
    $user = \App\Models\User::find($id);

    if (!$user) {
        return redirect()->route('login')->withErrors(['email' => 'Link verifikasi tidak valid.']);
    }

    // Validasi hash
    if (!hash_equals(sha1($user->email), $hash)) {
        return redirect()->route('login')->withErrors(['email' => 'Link verifikasi tidak valid.']);
    }

    // Cek apakah sudah diverifikasi
    if ($user->email_verified_at) {
        return redirect()->route('login')->with('status', 'Email sudah diverifikasi sebelumnya. Silakan login.');
    }

    // Verifikasi email
    $user->email_verified_at = now();
    $user->save();

    // Kirim welcome email HANYA untuk customer
    if ($user->role === 'customer') {
        \Mail::to($user->email)->send(new \App\Mail\WelcomeVerifiedMail($user));
    }

    return redirect()->route('login')->with('status', 'Email berhasil diverifikasi! Silakan login.');
})->middleware(['signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'Link sent!'); })->middleware(['auth', 'throttle:6,1'])->name('verification.send');



// SSO Web Login — token-based, tidak perlu middleware auth (token sudah validasi identitas)
Route::get('/sso/login', [App\Http\Controllers\SsoWebController::class, 'login'])->name('sso.login');

// --- CUSTOMER ROUTES ---
Route::middleware(['auth', 'customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', CustomerDashboard::class)->name('dashboard');
    Route::get('/shipments', ShipmentList::class)->name('shipments.index');
    Route::get('/shipments/create', CreateShipment::class)->name('shipments.create');
    Route::get('/shipments/{id}', ShipmentDetail::class)->name('shipment.show');
    Route::get('/profile', CustomerProfile::class)->name('profile');

    // Fitur Tambahan Customer
    Route::get('/kurs-pajak', KursPajakPage::class)->name('kurs');
    Route::get('/calculator', CustomerCalculator::class)->name('calculator');
    Route::get('/hs-codes', \App\Livewire\Customer\HsCodeExplorer::class)->name('hs-codes');
    Route::get('/invoices', \App\Livewire\Customer\InvoiceList::class)->name('invoices');
    Route::get('/reports', \App\Livewire\Customer\ReportStatistics::class)->name('reports');

    // Penawaran (Quotation) Customer
    Route::get('/quotations', \App\Livewire\Customer\QuotationList::class)->name('quotations');

    // Tutup banner pengingat lengkapi data ("Ingatkan nanti")
    Route::post('/profile-reminder/dismiss', [\App\Http\Controllers\Customer\ProfileReminderController::class, 'dismiss'])
        ->name('profile-reminder.dismiss');
});

// Berhenti impersonate — harus bisa diakses saat login sebagai customer.
Route::get('/impersonate/stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])
    ->middleware('auth')->name('impersonate.stop');

// Public: Approval quotation via token (tanpa login)
Route::get('/quotation/approve/{token}', [\App\Http\Controllers\QuotationApprovalController::class, 'show'])
    ->name('quotation.approve');
Route::post('/quotation/approve/{token}', [\App\Http\Controllers\QuotationApprovalController::class, 'process'])
    ->name('quotation.process');
Route::post('/quotation/approve/{token}/upload', [\App\Http\Controllers\QuotationApprovalController::class, 'uploadDocument'])
    ->name('quotation.upload');

// --- ADMIN ROUTES ---
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/shipments', ShipmentManagement::class)->name('shipments.index');

    // INBOX (Fix Namespace & Route Name)
    // Menggunakan nama 'inbox.index' (akan menjadi 'admin.inbox.index' karena prefix name)
    // Pastikan di blade memanggil route('admin.inbox.index')
    // JIKA di blade bapak memanggil route('inbox.index'), maka route ini HARUS dikeluarkan dari grup name('admin.')
    // SOLUSI: Saya keluarkan dari grup name('admin.') di bawah.

    // Detail Shipment
    Route::get('/shipments/{id}', \App\Livewire\Admin\ShipmentDetail::class)->name('shipments.show');

    // Cetak Surat Jalan
    Route::get('/shipments/{id}/print-do', function ($id) {
            $shipment = Shipment::with('customer')->findOrFail($id);
            return view('admin.print-do', compact('shipment'));
        }
        )->name('shipments.print-do');

        Route::get('/customers', CustomerManagement::class)->name('customers.index');
        // Lihat sebagai customer (impersonate, admin level saja)
        Route::get('/customers/{customer}/impersonate', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])
            ->name('customers.impersonate');
        // Inbox pesan customer (diskusi per shipment)
        Route::get('/customer-messages', \App\Livewire\Admin\CustomerMessages::class)->name('customer-messages');
        Route::get('/users', UserManagement::class)->name('users.index');
        Route::get('/user-requests', UserRequestManager::class)->name('user-requests.index');
        Route::get('/reports', Reports::class)->name('reports');
        Route::get('/profile', \App\Livewire\Admin\AdminProfile::class)->name('profile');
        Route::get('/invoices', InvoiceManager::class)->name('invoices.index');
        Route::get('/products', \App\Livewire\Admin\ProductManager::class)->name('products');
        Route::get('/quotations', QuotationManager::class)->name('quotations.index');
        Route::get('/vendors', VendorManagement::class)->name('vendors.index');
        Route::get('/job-costing', JobCostingManager::class)->name('job-costing.index');
        Route::get('/profit-report', \App\Livewire\Admin\ProfitReport::class)->name('profit-report');
        Route::get('/calculator', AdminCalculator::class)->name('calculator');

        // --- PRINT ROUTES (FIX AKSES DITOLAK 403) ---
        // Dipindahkan ke sini (dalam middleware admin) agar staff bisa akses
        Route::get('/invoices/{id}/print', function ($id, Illuminate\Http\Request $request) {
            $invoice = Invoice::with(['shipment.customer', 'items'])->findOrFail($id);

            $f = new NumberFormatter("id", NumberFormatter::SPELLOUT);
            $terbilangText = ucwords($f->format($invoice->grand_total)) . " Rupiah";

            // Ambil parameter dari URL
            $signerId = $request->get('signer', 1);
            $signatureType = $request->get('signature', 'full'); // full, stamp_only, blank
            $useMaterai = $request->get('materai', 0);

            // Data penandatangan
            $signers = config('signers');

            $signer = $signers[$signerId] ?? $signers[1];

            return view('admin.invoice-print', compact('invoice', 'terbilangText', 'signer', 'signatureType', 'useMaterai'));
        }
        )->name('invoices.print');

        Route::get('/quotations/{id}/print', function ($id, Illuminate\Http\Request $request) {
            $quotation = Quotation::with('customer', 'items')->findOrFail($id);
            $f = new NumberFormatter("id", NumberFormatter::SPELLOUT);
            $terbilangText = ucwords($f->format($quotation->grand_total)) . " Rupiah";

            // Ambil parameter signature dari URL
            $signerId = $request->get('signer', 1);
            $signatureType = $request->get('signature', 'blank'); // full, blank

            // Data penandatangan
            $signers = config('signers');
            $signer = $signers[$signerId] ?? $signers[1];

            return view('admin.quotation-print', compact('quotation', 'terbilangText', 'signer', 'signatureType'));
        }
        )->name('quotations.print');

        // --- HRD & PAYROLL ---
        Route::prefix('hrd')->name('hrd.')->group(function () {
            Route::get('/karyawan', \App\Livewire\Admin\HRD\EmployeeManagement::class)->name('employees');
            Route::get('/jabatan', \App\Livewire\Admin\HRD\JabatanManagement::class)->name('jabatan');
            Route::get('/penggajian', \App\Livewire\Admin\HRD\PayrollPeriodManagement::class)->name('payroll-periods');
            Route::get('/penggajian/{periodId}/slip', \App\Livewire\Admin\HRD\PayrollSlipManagement::class)->name('payroll-slips');

            // Export routes (controller-based)
            Route::get('/penggajian/{periodId}/export-excel', [\App\Http\Controllers\Admin\HRD\PayrollExportController::class, 'exportExcel'])->name('export-excel');
            Route::get('/slip/{slipId}/export-pdf', [\App\Http\Controllers\Admin\HRD\PayrollExportController::class, 'exportPdfSlip'])->name('export-pdf-slip');
        });

        // --- PAJAK & KEUANGAN ---
        Route::get('/pajak/catatan', \App\Livewire\Admin\TaxNoteManagement::class)->name('tax-notes.index');
    });


// --- ROUTE INBOX SPESIFIK (AGAR SESUAI PANGGILAN DI BLADE) ---

// Test route for EmailInbox debugging - dilindungi auth+admin
Route::get('/test-inbox', \App\Livewire\Admin\EmailInbox::class)->name('test.inbox')->middleware(['auth', 'admin']);

Route::middleware(['auth', 'admin'])->group(function () {
    // Route ini bernama 'inbox.index' (tanpa prefix admin.) agar cocok dengan admin.blade.php
    Route::get('/admin/inbox', EmailInbox::class)->name('inbox.index');
    Route::get('/admin/sent-emails', \App\Livewire\Admin\SentEmails::class)->name('sent-emails.index');
    Route::get('/admin/mora-leads', \App\Livewire\Admin\MoraLeadManager::class)->name('admin.mora-leads');
});

// --- ADMIN ACCOUNTING ---
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/accounting/coa', ChartOfAccounts::class)->name('accounting.coa');
    Route::get('/accounting/journal', \App\Livewire\Admin\Accounting\JournalEntry::class)->name('accounting.journal');

    // RUTE BARU: MANAJEMEN KASIR - MENGGUNAKAN FQCN LENGKAP
    Route::get('/accounting/cashier', \App\Livewire\Admin\Accounting\CashierManagement::class)->name('accounting.cashier');
    // RUTE KASIR SEDERHANA (SIMPLE CASHIER) - NO ACCOUNTING TERMS
    Route::get('/kasir-sederhana', \App\Livewire\Admin\SimpleCashier::class)->name('simple-cashier');

    Route::get('/accounting/ledger', \App\Livewire\Admin\Accounting\GeneralLedger::class)->name('accounting.ledger');
    Route::get('/accounting/trial-balance', \App\Livewire\Admin\Accounting\TrialBalance::class)->name('accounting.trial_balance');
    Route::get('/accounting/profit-loss', \App\Livewire\Admin\Accounting\ProfitLoss::class)->name('accounting.profit_loss');
    Route::get('/accounting/balance-sheet', \App\Livewire\Admin\Accounting\BalanceSheet::class)->name('accounting.balance_sheet');
    Route::get('/audit-logs', AuditLogManager::class)->name('audit-logs');
});

// --- UTILITIES ---

// ROUTE UTILITY: DOWNLOAD / PREVIEW IMAP ATTACHMENT
Route::middleware(['auth', 'admin'])->get('/admin/inbox/download/{account}/{uid}/{id}/{mode?}', function ($account, $uid, $id, $mode = 'inline') {
    try {
        // Konek Sesaat
        $client = \Webklex\IMAP\Facades\Client::account($account);
        $client->connect();

        $folder = $client->getFolder('INBOX');
        $message = $folder->query()->whereUid($uid)->get()->first();

        if (!$message)
            abort(404, 'Email tidak ditemukan.');

        $attachment = null;
        foreach ($message->getAttachments() as $att) {
            if ($att->getId() == $id) {
                $attachment = $att;
                break;
            }
        }

        if (!$attachment)
            abort(404, 'Attachment tidak ditemukan.');

        // Stream File
        return response($attachment->getContent())
        ->header('Content-Type', $attachment->getMimeType())
        ->header('Content-Disposition', $mode . '; filename="' . $attachment->getName() . '"');

    }
    catch (\Exception $e) {
        return "Gagal mengambil file: " . $e->getMessage();
    }
})->name('admin.inbox.download');

// =======================================================
// // DEV ONLY — ACCOUNTING WORKFLOW TEST (SAFE VERSION)
// // =======================================================
// 
// Route::get('/__dev/test-proforma', function () {
//     $invoice = \App\Models\Invoice::where('type', 'proforma')->firstOrFail();
// 
//     if (!$invoice->payment_date) {
//         $invoice->payment_date = now();
//         $invoice->save();
//     }
// 
//     app(\App\Services\Business\AccountingWorkflowService::class)
//         ->handleProformaPaid($invoice);
// 
//     return 'TEST PROFORMA OK : '.$invoice->invoice_number;
// });
// 
// Route::get('/__dev/test-commercial', function () {
//     $invoice = \App\Models\Invoice::where('type', 'commercial')
//         ->where('down_payment', '>', 0)
//         ->firstOrFail();
// 
//     if (!$invoice->payment_date) {
//         $invoice->payment_date = now();
//         $invoice->save();
//     }
// 
//     app(\App\Services\Business\AccountingWorkflowService::class)
//         ->handleCommercialPaid($invoice);
// 
//     return 'TEST COMMERCIAL OK : '.$invoice->invoice_number;
// });

// ...
// Print receipt route - admin only
Route::get('/admin/kasir-sederhana/print/{id}', function ($id) {
    $transaction = \App\Models\CashTransaction::with([
        'customer',
        'vendor',
        'shipment',
        'journal.items.account',
        'journal.approver',
        'creator'
    ])->findOrFail($id);

    return view('livewire.admin.print-receipt', ['transaction' => $transaction->toArray()]);
})->name('cashier.print')->middleware(['auth', 'admin']);

Route::middleware(['auth', 'customer'])->prefix('customer')->group(function () {
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class , 'preview'])
        ->name('customer.invoices.preview');
});

// Public survey (no auth) - rate limit untuk mencegah spam
Route::prefix('survey')->name('survey.')->middleware('throttle:30,1')->group(function () {
    Route::get('/', [SurveyController::class , 'index'])->name('public');
    Route::get('/thank-you', [SurveyController::class , 'thankYou'])->name('thank-you');
    Route::get('/qr-code', [SurveyController::class , 'generateQrCode'])->name('qr-code');
});

// Admin survey (auth required)
Route::middleware(['auth', 'admin'])->prefix('admin/survey')->name('admin.survey.')->group(function () {
    Route::get('/dashboard', [SurveyAdminController::class , 'dashboard'])->name('dashboard');
    Route::get('/response/{id}', [SurveyAdminController::class , 'viewResponse'])->name('view');
    Route::post('/response/{id}/toggle-flag', [SurveyAdminController::class , 'toggleFlag'])->name('toggle-flag');
    Route::post('/response/{id}/notes', [SurveyAdminController::class , 'updateNotes'])->name('update-notes');
    Route::delete('/response/{id}', [SurveyAdminController::class , 'deleteResponse'])->name('delete');
    Route::get('/export/excel', [SurveyAdminController::class , 'exportExcel'])->name('export.excel');
    Route::get('/export/report/{format}', [SurveyAdminController::class , 'exportReport'])->name('export.report');
});
// QR Code Generator sudah terdaftar di grup prefix('survey') di atas (survey.qr-code)

// Testimonial publik
Route::prefix('testimoni')->name('testimonial.')->middleware('throttle:20,1')->group(function () {
    Route::get('/', [\App\Http\Controllers\TestimonialController::class, 'index'])->name('index');
    Route::get('/form/{token}', [\App\Http\Controllers\TestimonialController::class, 'form'])->name('form');
    Route::post('/form/{token}', [\App\Http\Controllers\TestimonialController::class, 'submit'])->name('submit');
    Route::get('/terima-kasih', [\App\Http\Controllers\TestimonialController::class, 'thankyou'])->name('thankyou');
});

// Admin testimoni moderasi
Route::middleware(['auth', 'admin'])->prefix('admin/testimoni')->name('admin.testimonial.')->group(function () {
    Route::get('/', \App\Livewire\Admin\TestimonialModeration::class)->name('index');
});

// Simple Invoice Routes (Complete CRUD)
Route::prefix('finance/simple-invoice')->name('finance.simple-invoice.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'store'])->name('store');
    Route::get('/{id}/edit', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'destroy'])->name('destroy');
    Route::get('/{id}/pdf', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'pdf'])->name('pdf');
    Route::get('/{id}/download', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'download'])->name('download');
    Route::post('/{id}/update-payment', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'updatePayment'])->name('update-payment');
});

// Simple Invoice Routes (Complete CRUD)

// Simple Invoice Detail Route (for modal view)
Route::get('/finance/simple-invoice/{id}/detail', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'detail'])->name('finance.simple-invoice.detail')->middleware('auth');
// Route Print Simple Invoice (9.5" x 11")
Route::get('/finance/simple-invoice/{id}/print', [App\Http\Controllers\Finance\SimpleInvoiceController::class , 'print'])->name('finance.simple-invoice.print')->middleware('auth');


// HS Codes - public API dengan rate limiting untuk mencegah abuse
Route::prefix('hs-codes')->middleware('throttle:120,1')->group(function () {
    Route::get('/search', [HsCodeApiController::class , 'search']);
    Route::get('/validate/{code}', [HsCodeApiController::class , 'validate']);
    Route::get('/chapters', [HsCodeApiController::class , 'chapters']);
    Route::get('/chapter/{chapter}', [HsCodeApiController::class , 'byChapter']);
    Route::get('/{code}', [HsCodeApiController::class , 'show']);
    Route::get('/{code}/hierarchy', [HsCodeApiController::class , 'hierarchy']);
    Route::get('/{code}/children', [HsCodeApiController::class , 'children']);
});

// HS Code Search Page (Livewire)
// Route::get('/hs-codes', function () {
// return view('livewire.hs-code-search');
// })->name('hs-codes.search');
// 
// 
// // ============================================
// // HS CODE EXPLORER ROUTES
// // ============================================
// Route::middleware(['auth'])->group(function () {
Route::get('/hs-codes', \App\Livewire\HsCode\Explorer::class)->name('hs-codes.explorer');

// API endpoint for autocomplete - aman dari SQL injection (parameter binding)
Route::get('/api/hs-codes/search', function (Request $request) {
    $query = $request->input('q', '');
    $query = \Illuminate\Support\Str::limit($query, 100); // Batasi panjang input

    if (strlen($query) < 2) {
        return response()->json([]);
    }

    $pattern = '%' . $query . '%';
    $results = DB::table('hs_codes')
        ->where('hs_code', 'LIKE', $pattern)
        ->orWhere('description_id', 'LIKE', $pattern)
        ->limit(10)
        ->get(['hs_code', 'description_id', 'hs_level']);

    return response()->json($results);
})->name('api.hs-codes.search')->middleware('throttle:60,1');

// ============================================
// FIELD DOCUMENTATION ROUTES
// ============================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard Dokumentasi Lapangan
    Route::get('/field-docs', [App\Http\Controllers\Admin\FieldDocController::class , 'index'])
        ->name('field-docs.index');

    // Upload Form (bisa langsung atau via shipment number)
    Route::get('/field-docs/upload/{shipment?}', [App\Http\Controllers\Admin\FieldDocController::class , 'upload'])
        ->name('field-docs.upload');

    // Gallery per Shipment
    Route::get('/field-docs/gallery/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class , 'gallery'])
        ->name('field-docs.gallery');

    // QR Code Display & Download
    Route::get('/field-docs/qr/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class , 'qrCode'])
        ->name('field-docs.qr');

    Route::get('/field-docs/qr/{shipment}/download', [App\Http\Controllers\Admin\FieldDocController::class , 'downloadQr'])
        ->name('field-docs.qr-download');

    // Export PDF Report
    Route::get('/field-docs/export/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class , 'exportPdf'])
        ->name('field-docs.export-pdf');

    // API Search Shipments (untuk autocomplete)
    Route::get('/field-docs/api/shipments/search', [App\Http\Controllers\Admin\FieldDocController::class , 'searchShipments'])
        ->name('field-docs.search-shipments');
});

// Field Officer Mobile Upload (bisa diberi middleware khusus)
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/upload/{shipment?}', [App\Http\Controllers\Admin\FieldDocController::class , 'mobileUpload'])
        ->name('field-upload');
});

// Portal Petugas Lapangan (field_uploader role)
Route::middleware(['auth', 'field_uploader'])->prefix('field')->name('field.')->group(function () {
    Route::get('/', [App\Http\Controllers\Field\FieldUploaderController::class, 'dashboard'])
        ->name('dashboard');
    Route::get('/upload/{shipment?}', [App\Http\Controllers\Field\FieldUploaderController::class, 'upload'])
        ->name('upload');
});

// Field Documentation - Delete Photo & Download Routes (admin only)
Route::middleware(['auth', 'admin'])->prefix('admin/field-docs')->name('admin.field-docs.')->group(function () {
    Route::delete('/photo/{photo}', [App\Http\Controllers\Admin\FieldDocController::class , 'deletePhoto'])->name('delete-photo');
    Route::post('/photos/bulk-delete', [App\Http\Controllers\Admin\FieldDocController::class , 'bulkDeletePhotos'])->name('bulk-delete-photos');
    Route::get('/download-zip/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class , 'downloadZip'])->name('download-zip');
});

// Temporary fix-invoice route telah dihapus (keamanan)

// Bank Reconciliation Routes (admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/bank-reconciliation', \App\Livewire\Admin\BankReconciliation::class)
        ->name('admin.bank-reconciliation');
});

// ==========================================
// DOCUMENT VIEW & DOWNLOAD ROUTES (FIXED)
// ==========================================
Route::middleware(['auth'])->group(function () {

    // Route untuk view/preview dokumen
    Route::get('/document/{id}/view', function ($id) {
            $doc = \App\Models\Document::findOrFail($id);

            // SECURITY CHECK: Customer hanya bisa lihat dokumen miliknya
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user->role === 'customer') {
                $shipment = $doc->shipment;
                if ($shipment && $user->customer && $shipment->customer_id !== $user->customer->id) {
                    abort(403, 'Akses ditolak.');
                }
            }

            // Coba berbagai lokasi file untuk mendukung file lama
            $possiblePaths = array_filter([
                $doc->file_path,
                'documents/' . $doc->filename,
                'documents/customer_uploads/' . $doc->filename,
                'shipments/' . $doc->filename,
                'uploads/' . $doc->filename,
                $doc->filename,
                ltrim($doc->file_path ?? '', '/'),
                str_replace('storage/', '', $doc->file_path ?? ''),
            ]);

            $existingPath = null;
            $diskName = 'public';
            foreach ($possiblePaths as $path) {
                if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    $existingPath = $path;
                    $diskName = 'public';
                    break;
                }
                if ($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                    $existingPath = $path;
                    $diskName = 'local';
                    break;
                }
            }

            if (!$existingPath) {
                \Illuminate\Support\Facades\Log::warning('Document file not found', [
                    'document_id' => $id,
                    'original_path' => $doc->file_path,
                    'filename' => $doc->filename,
                ]);
                return response()->view('errors.document-not-found', ['document' => $doc], 404);
            }

            $fullPath = $diskName === 'local'
                ? storage_path('app/' . $existingPath)
                : storage_path('app/public/' . $existingPath);
            $mimeType = $doc->mime_type ?? (function_exists('mime_content_type') ? mime_content_type($fullPath) : 'application/octet-stream');

            $inlineTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $disposition = in_array($mimeType, $inlineTypes) ? 'inline' : 'attachment';

            return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . ($doc->filename ?? basename($existingPath)) . '"',
            ]);

        }
        )->name('document.view');

        // Route untuk download dokumen
        Route::get('/document/{id}/download', function ($id) {
            $doc = \App\Models\Document::findOrFail($id);

            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user->role === 'customer') {
                $shipment = $doc->shipment;
                if ($shipment && $user->customer && $shipment->customer_id !== $user->customer->id) {
                    abort(403, 'Akses ditolak.');
                }
            }

            $possiblePaths = array_filter([
                $doc->file_path,
                'documents/' . $doc->filename,
                'documents/customer_uploads/' . $doc->filename,
                'shipments/' . $doc->filename,
                'uploads/' . $doc->filename,
                $doc->filename,
                ltrim($doc->file_path ?? '', '/'),
                str_replace('storage/', '', $doc->file_path ?? ''),
            ]);

            $existingPath = null;
            $diskName = 'public';
            foreach ($possiblePaths as $path) {
                if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    $existingPath = $path;
                    $diskName = 'public';
                    break;
                }
                if ($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                    $existingPath = $path;
                    $diskName = 'local';
                    break;
                }
            }

            if (!$existingPath) {
                return response()->view('errors.document-not-found', ['document' => $doc], 404);
            }

            $fullPath = $diskName === 'local'
                ? storage_path('app/' . $existingPath)
                : storage_path('app/public/' . $existingPath);
            return response()->download($fullPath, $doc->filename ?? basename($existingPath));

        }
        )->name('document.download');
    });

// API Search Customer untuk Simple Invoice - aman dari SQL injection
Route::get('/api/customers/search', function (Illuminate\Http\Request $request) {
    $query = \Illuminate\Support\Str::limit($request->get('q', ''), 100);
    $pattern = '%' . $query . '%';
    $customers = \App\Models\Customer::query()
        ->where('company_name', 'like', $pattern)
        ->orWhere('phone', 'like', $pattern)
        ->orWhere('city', 'like', $pattern)
        ->orderBy('company_name')
        ->limit(20)
        ->get(['id', 'company_name', 'address', 'phone', 'city']);
    return response()->json($customers);
})->name('api.customers.search')->middleware(['auth', 'throttle:60,1']);

// ========== KAS KECIL ==========
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/kas-kecil', \App\Livewire\Admin\PettyCashManager::class)->name('admin.petty-cash');
    Route::get('/admin/attendance', \App\Livewire\Admin\AttendanceManagement::class)->name('admin.attendance');
    Route::get('/admin/visits', \App\Livewire\Admin\VisitManagement::class)->name('admin.visits');
});