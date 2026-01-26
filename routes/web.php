<?php

use Illuminate\Support\Facades\Route;
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

Route::get('/admin/inbox/attachment/{mailbox}/{id}', [EmailAttachmentController::class, 'download'])
    ->name('admin.inbox.attachment')
    ->middleware(['web', 'auth', 'admin']); // sesuaikan middleware anda


// --- GUEST ROUTES (LOGIN, REGISTER, FORGOT PASSWORD) ---
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', function () { return view('auth.login'); })->name('login');
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate(['email'=>'required','password'=>'required']);
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return Auth::user()->role === 'customer' ? redirect()->intended(route('customer.dashboard')) : redirect()->intended(route('admin.dashboard'));
        }
        return back()->withErrors(['email'=>'Email salah.']);
    });
    
    // Register
    Route::get('/register', function () { return view('auth.register'); })->name('register');
    Route::get("/register/success", function () { return view("auth.register-success"); })->name("register.success");
    Route::post('/register', function (Request $request) {
        $request->validate(['name'=>'required', 'company_name'=>'required', 'email'=>'required|email|unique:users', 'password'=>'required|confirmed']);
        
        $user = null;
        DB::transaction(function () use ($request, &$user) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'customer',
            ]);
            
            Customer::create([
                'user_id'       => $user->id,
                'customer_code' => Customer::generateCustomerCode(),
                'company_name'  => $request->company_name,
                'address'       => '-',
                'city'          => 'Indonesia'
            ]);
        });
        
        // Kirim email verifikasi
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify', now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        \Mail::to($user->email)->send(new \App\Mail\VerifyEmailMail($user, $verificationUrl));
        
        return redirect()->route('register.success');
    });


    // Forgot Password
    Route::get('/forgot-password', function () { return view('auth.forgot-password'); })->name('password.request');
    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email'=>'required|email']);
        
        $user = \App\Models\User::where('email', $request->email)->first();
        
        // Hanya kirim email custom untuk CUSTOMER
        if ($user && $user->role === 'customer') {
            $token = \Illuminate\Support\Str::random(64);
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => \Hash::make($token), 'created_at' => now()]
            );
            
            $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($user->email));
            \Mail::to($user->email)->send(new \App\Mail\ResetPasswordMail($user, $resetUrl));
        } else {
            // Untuk non-customer (staf), gunakan default Laravel
            Password::sendResetLink($request->only('email'));
        }
        
        return back()->with('status', 'Jika email terdaftar, link reset password telah dikirim.');
    })->name('password.email');

    
    Route::get('/reset-password/{token}', function ($token, Request $request) { 
        return view('auth.reset-password', ['token'=>$token, 'email'=>$request->query('email')]); 
    })->name('password.reset');
    
    Route::post('/reset-password', function (Request $request) {
        $request->validate(['token'=>'required', 'email'=>'required|email', 'password'=>'required|confirmed']);
        
        $status = Password::reset(
            $request->only('email','password','password_confirmation','token'), 
            function($user, $pass) { 
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
    })->name('password.update');
});


// --- AUTH COMMON ---
Route::post('/logout', function () { 
    Auth::logout(); 
    request()->session()->invalidate(); 
    request()->session()->regenerateToken(); 
    return redirect('/'); 
})->name('logout');

Route::get('/logout', function () { 
    Auth::logout(); 
    request()->session()->invalidate(); 
    request()->session()->regenerateToken(); 
    return redirect('/'); 
}); // Fallback for cached links
Route::get('/email/verify', function () { return view('auth.verify-email'); })->middleware('auth')->name('verification.notice');
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
Route::post('/email/verification-notification', function (Request $request) { $request->user()->sendEmailVerificationNotification(); return back()->with('status', 'Link sent!'); })->middleware(['auth', 'throttle:6,1'])->name('verification.send');



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
});

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
    })->name('shipments.print-do');

    Route::get('/customers', CustomerManagement::class)->name('customers.index');
    Route::get('/users', UserManagement::class)->name('users.index');
    Route::get('/user-requests', UserRequestManager::class)->name('user-requests.index');
    Route::get('/reports', Reports::class)->name('reports');
    Route::get('/profile', \App\Livewire\Admin\AdminProfile::class)->name('profile');
    Route::get('/invoices', InvoiceManager::class)->name('invoices.index');
    Route::get('/products', \App\Livewire\Admin\ProductManager::class)->name('products');
    Route::get('/quotations', QuotationManager::class)->name('quotations.index');
    Route::get('/vendors', VendorManagement::class)->name('vendors.index');
    Route::get('/job-costing', JobCostingManager::class)->name('job-costing.index');
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
        $signers = [
            1 => ['name' => 'Nurul Asyikin', 'title' => 'Sales & Finance', 'sign' => 'sign_nurul.png'],
            2 => ['name' => 'Nadila Shamimi', 'title' => 'Document & Operation', 'sign' => 'sign_dila.png'],
            3 => ['name' => 'Tasya Indriyani', 'title' => 'Cashier & Admin', 'sign' => 'sign_tasya.png'],
            4 => ['name' => 'Eka Mayang Sari Harahap, S. E.', 'title' => 'Director', 'sign' => 'sign_direktur.png'],
        ];
        
        $signer = $signers[$signerId] ?? $signers[1];
        
        return view('admin.invoice-print', compact('invoice', 'terbilangText', 'signer', 'signatureType', 'useMaterai'));
    })->name('invoices.print'); 

    Route::get('/quotations/{id}/print', function ($id) {
        $quotation = Quotation::with('customer', 'items')->findOrFail($id);
        $f = new NumberFormatter("id", NumberFormatter::SPELLOUT);
        $terbilangText = ucwords($f->format($quotation->grand_total)) . " Rupiah";
        return view('admin.quotation-print', compact('quotation', 'terbilangText'));
    })->name('quotations.print');
});


// --- ROUTE INBOX SPESIFIK (AGAR SESUAI PANGGILAN DI BLADE) ---

// Test route for EmailInbox debugging - Direct Livewire component
Route::get('/test-inbox', \App\Livewire\Admin\EmailInbox::class)->name('test.inbox');

Route::middleware(['auth', 'admin'])->group(function () {
    // Route ini bernama 'inbox.index' (tanpa prefix admin.) agar cocok dengan admin.blade.php
    Route::get('/admin/inbox', EmailInbox::class)->name('inbox.index');
    Route::get('/admin/sent-emails', \App\Livewire\Admin\SentEmails::class)->name('sent-emails.index');
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




// ... (pastikan route admin.inbox.download IMAP tetap ada untuk tombol di Inbox)

// Route pembersih cache
Route::get('/bersih-bersih', function () {
    Artisan::call('route:clear'); 
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear'); // Tambahan
    return "Semua Cache Bersih! Silakan coba akses /admin/inbox lagi.";
});
// ... (Kode route lain tetap sama)

// --- ALAT DIAGNOSTIK EMAIL M2B ---
Route::get('/debug-email', function () {
    $accounts = ['sales', 'import', 'export', 'domestic'];
    $target = request('akun', 'sales'); // Default cek sales

    echo "<h1>🔍 Diagnostik Email M2B</h1>";
    echo "<p>Sedang memeriksa akun: <strong>".strtoupper($target)."</strong></p>";
    
    // 1. Cek Config Laravel
    $config = config("imap.accounts.$target");
    echo "<h3>1. Cek Konfigurasi (config/imap.php)</h3>";
    if (!$config) {
        echo "<span style='color:red'>[FAIL] Konfigurasi tidak ditemukan! Cek file config/imap.php</span>";
        return;
    }
    echo "Host: " . $config['host'] . "<br>";
    echo "Port: " . $config['port'] . "<br>";
    echo "User: " . $config['username'] . "<br>";
    echo "Encryption: " . $config['encryption'] . "<br>";
    echo "Validate Cert: " . ($config['validate_cert'] ? 'YES' : 'NO') . "<br>";

    // 2. Cek Koneksi Server (Socket)
    echo "<h3>2. Cek Koneksi Server (Socket)</h3>";
    $fp = @fsockopen($config['host'], $config['port'], $errno, $errstr, 5);
    if (!$fp) {
        echo "<span style='color:red'>[FAIL] Tidak bisa terhubung ke server! ($errno - $errstr)</span><br>";
        echo "Kemungkinan: Firewall memblokir port ".$config['port']." atau Hostname salah.";
        return;
    }
    echo "<span style='color:green'>[OK] Server bisa dihubungi lewat Port ".$config['port'].".</span><br>";
    fclose($fp);

    // 3. Cek Login IMAP (Real Login) - SAFE QUERY (hindari SEARCH kosong)
    echo "<h3>3. Cek Login IMAP</h3>";
    try {
        $client = \Webklex\IMAP\Facades\Client::account($target);
        $client->connect();
        echo "<span style='color:green; font-weight:bold; font-size:16px;'>[SUCCESS] LOGIN BERHASIL! 🎉</span><br>";

        // Ambil folder INBOX dengan pendekatan yang lebih aman:
        $folder = $client->getFolder('INBOX');
        
        // Route download attachment (IMAP streaming)
Route::middleware(['auth','admin'])->get('/admin/inbox/attachment/{account}/{uid}/{idx}', function($account, $uid, $idx) {
    try {
        $client = \Webklex\IMAP\Facades\Client::account($account);
        $client->connect();
        $folder = $client->getFolder('INBOX');

        // cari message by UID (coba where UID, fallback)
        $msg = null;
        try {
            $msg = $folder->messages()->where('UID', (int)$uid)->limit(1)->get()->first();
        } catch (\Throwable $ex) {
            $all = $folder->messages()->all()->limit(100)->get();
            foreach ($all as $m) {
                if ((string)$m->getUid() == (string)$uid) { $msg = $m; break; }
            }
        }

        if (!$msg) abort(404, 'Message not found');

        $atts = $msg->getAttachments();
        $idx = (int)$idx;
        if (!isset($atts[$idx])) abort(404, 'Attachment not found');

        $att = $atts[$idx];

        // Ambil nama, tipe dan konten
        $filename = 'attachment';
        try { $filename = $att->getName(); } catch (\Throwable $e) { if (property_exists($att,'name')) $filename = $att->name; }
        $mime = 'application/octet-stream';
        try { $mime = $att->getMimeType(); } catch (\Throwable $e) { if(property_exists($att,'mime')) $mime = $att->mime; }

        // baca konten (coba beberapa method)
        $content = null;
        try { $content = $att->getContent(); } catch (\Throwable $e) {}
        try { if (is_null($content) && method_exists($att,'getDecodedContent')) $content = $att->getDecodedContent(); } catch (\Throwable $e) {}
        try { if (is_null($content) && method_exists($att,'getBytes')) $content = $att->getBytes(); } catch (\Throwable $e) {}
        try { if (is_null($content) && method_exists($att,'content')) $content = $att->content; } catch (\Throwable $e) {}

        if (is_null($content)) abort(500, 'Tidak bisa membaca konten attachment');

        // Jika $content objekt stream, ambil string
        if (is_object($content) && method_exists($content,'getContents')) {
            $bin = $content->getContents();
        } else {
            $bin = (string)$content;
        }

        return response($bin, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'attachment; filename="'.basename($filename).'"');

    } catch (\Throwable $e) {
        abort(500, 'Download gagal: ' . $e->getMessage());
    }
});


        // Langkah 1: coba messages()->all()
        $count = null;
        try {
            $messages = $folder->messages()->all()->get();
            $count = is_countable($messages) ? $messages->count() : 'N/A';
        } catch (\Exception $e1) {
            // Jika gagal (mis. server menolak SEARCH kosong), coba query() dengan kriteria jelas (UNSEEN)
            Log::warning("IMAP messages()->all() gagal untuk akun {$target}: " . $e1->getMessage());
            try {
                $messages = $folder->query()->unseen()->get();
                $count = is_countable($messages) ? $messages->count() : 'N/A';
            } catch (\Exception $e2) {
                // Fallback terakhir: tangkap error dan tampilkan pesan
                Log::error("IMAP fallback (unseen) juga gagal untuk akun {$target}: " . $e2->getMessage(), ['trace' => $e2->getTraceAsString()]);
                $count = 'ERROR COUNT: ' . $e2->getMessage();
            }
        }

        echo "Jumlah Email di Inbox: <strong>{$count}</strong>";

    } catch (\Exception $e) {
        // Login gagal atau error IMAP lainnya
        Log::error("IMAP connect/login error for account {$target}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        echo "<span style='color:red; font-weight:bold; font-size:16px;'>[FAIL] LOGIN GAGAL! ❌</span><br>";
        echo "Pesan Error: " . $e->getMessage() . "<br><br>";
        echo "<strong>Saran Perbaikan:</strong><br>";
        echo "1. Pastikan password di file .env sudah benar (tidak ada spasi di awal/akhir).<br>";
        echo "2. Coba ganti 'IMAP_ENCRYPTION' di .env menjadi 'ssl' atau 'tls'.<br>";
        echo "3. Coba set 'IMAP_VALIDATE_CERT=false' di .env jika sertifikat SSL server self-signed.<br>";
        echo "4. Jika masih gagal, jalankan tes openssl di server hosting (openssl s_client -connect {$config['host']}:{$config['port']}).<br>";
    }

    echo "<hr><p>Cek akun lain: ";
    foreach($accounts as $acc) {
        echo "<a href='/debug-email?akun=$acc'>".ucfirst($acc)."</a> | ";
    }
    echo "</p>";
});
// ... (Kode route lainnya tetap sama)

// --- ALAT PERBAIKAN & TES KONEKSI EMAIL ---
Route::get('/fix-imap', function () {
    // 1. BERSIHKAN SEMUA CACHE KONFIGURASI (CRUCIAL)
    try {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    } catch (\Exception $e) {
        // Abaikan jika gagal di hosting tertentu
    }

    echo "<h2 style='font-family:sans-serif'>🛠️ M2B Email Connection Fixer</h2>";
    echo "<p style='color:green'>✅ Cache Konfigurasi Berhasil Direset!</p>";

    // 2. TES KONEKSI AKUN SALES
    $account = 'sales';
    echo "<hr><h3>Mencoba koneksi akun: " . strtoupper($account) . "...</h3>";
    
    // Debug: Lihat apakah config terbaca benar dari .env
    $configHost = config("imap.accounts.$account.host");
    $configUser = config("imap.accounts.$account.username");
    
    echo "<ul>";
    echo "<li>Host: <strong>$configHost</strong> (Harus: bandung01.emailkerja.id)</li>";
    echo "<li>User: <strong>$configUser</strong> (Harus: sales@m2b.co.id)</li>";
    echo "</ul>";

    try {
        $client = \Webklex\IMAP\Facades\Client::account($account);
        $client->connect();
        
        $folder = $client->getFolder('INBOX');
        $count = $folder->query()->count();
        
        echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:8px; border:1px solid #bbf7d0;'>";
        echo "<strong>🎉 KONEKSI SUKSES!</strong><br>";
        echo "Berhasil terhubung ke Inbox Sales.<br>";
        echo "Jumlah Email: <strong>$count</strong>";
        echo "</div>";
        
        echo "<p><a href='/admin/inbox' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#1e3a8a; color:white; text-decoration:none; border-radius:5px;'>Buka Inbox Sekarang &rarr;</a></p>";

    } catch (\Exception $e) {
        echo "<div style='background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fecaca;'>";
        echo "<strong>❌ KONEKSI GAGAL</strong><br>";
        echo "Error: " . $e->getMessage();
        echo "</div>";
        echo "<p><strong>Saran:</strong> Cek kembali password di file .env. Pastikan diapit tanda kutip dua. Contoh: <code>IMAP_SALES_PASS=\"Password123\"</code></p>";
    }
});
// ...

// ... (Kode route lain tetap ada)

// ROUTE UTILITY: DOWNLOAD / PREVIEW IMAP ATTACHMENT
Route::middleware(['auth', 'admin'])->get('/admin/inbox/download/{account}/{uid}/{id}/{mode?}', function ($account, $uid, $id, $mode = 'inline') {
    try {
        // Konek Sesaat
        $client = \Webklex\IMAP\Facades\Client::account($account);
        $client->connect();
        
        $folder = $client->getFolder('INBOX');
        $message = $folder->query()->whereUid($uid)->get()->first();
        
        if (!$message) abort(404, 'Email tidak ditemukan.');
        
        $attachment = null;
        foreach($message->getAttachments() as $att) {
            if ($att->getId() == $id) {
                $attachment = $att;
                break;
            }
        }
        
        if (!$attachment) return response()->view('errors.document-not-found', ['document' => $doc], 404);

        // Stream File
        return response($attachment->getContent())
            ->header('Content-Type', $attachment->getMimeType())
            ->header('Content-Disposition', $mode . '; filename="'.$attachment->getName().'"');

    } catch (\Exception $e) {
        return "Gagal mengambil file: " . $e->getMessage();
    }
})->name('admin.inbox.download');

// =======================================================
// DEV ONLY — ACCOUNTING WORKFLOW TEST (SAFE VERSION)
// =======================================================

Route::get('/__dev/test-proforma', function () {
    $invoice = \App\Models\Invoice::where('type', 'proforma')->firstOrFail();

    if (!$invoice->payment_date) {
        $invoice->payment_date = now();
        $invoice->save();
    }

    app(\App\Services\Business\AccountingWorkflowService::class)
        ->handleProformaPaid($invoice);

    return 'TEST PROFORMA OK : '.$invoice->invoice_number;
});

Route::get('/__dev/test-commercial', function () {
    $invoice = \App\Models\Invoice::where('type', 'commercial')
        ->where('down_payment', '>', 0)
        ->firstOrFail();

    if (!$invoice->payment_date) {
        $invoice->payment_date = now();
        $invoice->save();
    }

    app(\App\Services\Business\AccountingWorkflowService::class)
        ->handleCommercialPaid($invoice);

    return 'TEST COMMERCIAL OK : '.$invoice->invoice_number;
});

// ...
// Print receipt route
Route::get('/admin/kasir-sederhana/print/{id}', function($id) {
    $transaction = \App\Models\CashTransaction::with([
        'customer', 
        'vendor', 
        'shipment', 
        'journal.items.account',
        'journal.approver',
        'creator'
    ])->findOrFail($id);
    
    return view('livewire.admin.print-receipt', ['transaction' => $transaction->toArray()]);
})->name('cashier.print')->middleware('auth');

Route::middleware(['auth', 'customer'])->prefix('customer')->group(function () {
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])
        ->name('customer.invoices.preview');
});

Route::get('/_debug/invoice-pdf/{id}', function ($id) {
    $invoice = Invoice::with(['items','customer','shipment'])->findOrFail($id);

    return Pdf::loadView('admin.invoice-pdf', [
        'invoice' => $invoice,
        'isPdf'   => true,
    ])->stream('debug.pdf');
});

// Public survey (no auth)
Route::prefix('survey')->name('survey.')->group(function () {
    Route::get('/', [SurveyController::class, 'index'])->name('public');
    Route::get('/thank-you', [SurveyController::class, 'thankYou'])->name('thank-you');
    Route::get('/qr-code', [SurveyController::class, 'generateQrCode'])->name('qr-code');
});

// Admin survey (auth required)
Route::middleware(['auth'])->prefix('admin/survey')->name('admin.survey.')->group(function () {
    Route::get('/dashboard', [SurveyAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/response/{id}', [SurveyAdminController::class, 'viewResponse'])->name('view');
    Route::post('/response/{id}/toggle-flag', [SurveyAdminController::class, 'toggleFlag'])->name('toggle-flag');
    Route::post('/response/{id}/notes', [SurveyAdminController::class, 'updateNotes'])->name('update-notes');
    Route::delete('/response/{id}', [SurveyAdminController::class, 'deleteResponse'])->name('delete');
    Route::get('/export/excel', [SurveyAdminController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/report/{format}', [SurveyAdminController::class, 'exportReport'])->name('export.report');
});
// QR Code Generator
Route::get('/survey/qr-code', function() {
    return view('survey.qr-code');
})->name('survey.qr-code');

Route::prefix('finance/simple-invoice')->name('finance.simple-invoice.')->group(function () {
    Route::get('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'create'])->name('create');
    Route::get('/{id}/pdf', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'pdf'])->name('pdf');
    Route::get('/{id}/preview', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'preview'])->name('preview');
    Route::get('/{id}/edit', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'edit'])->name('edit');
    Route::delete('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/update-payment', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'updatePayment'])->name('update-payment');
    Route::get('/{id}/download', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'download'])->name('download');
});
// Simple Invoice Routes (Complete CRUD)
Route::prefix('finance/simple-invoice')->name('finance.simple-invoice.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/pdf', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'pdf'])->name('pdf');
    Route::get('/{id}/download', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'download'])->name('download');
    Route::post('/{id}/update-payment', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'updatePayment'])->name('update-payment');
});

// Simple Invoice Routes (Complete CRUD)
Route::prefix('finance/simple-invoice')->name('finance.simple-invoice.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'destroy'])->name('destroy');
    Route::get('/{id}/pdf', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'pdf'])->name('pdf');
    Route::get('/{id}/download', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'download'])->name('download');
    Route::post('/{id}/update-payment', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'updatePayment'])->name('update-payment');
});

// Simple Invoice Detail Route (for modal view)
Route::get('/finance/simple-invoice/{id}/detail', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'detail'])->name('finance.simple-invoice.detail')->middleware('auth');
// Route Print Simple Invoice (9.5" x 11")
Route::get('/finance/simple-invoice/{id}/print', [App\Http\Controllers\Finance\SimpleInvoiceController::class, 'print'])->name('finance.simple-invoice.print')->middleware('auth');


Route::prefix('hs-codes')->group(function () {
    Route::get('/search', [HsCodeApiController::class, 'search']);
    Route::get('/validate/{code}', [HsCodeApiController::class, 'validate']);
    Route::get('/chapters', [HsCodeApiController::class, 'chapters']);
    Route::get('/chapter/{chapter}', [HsCodeApiController::class, 'byChapter']);
    Route::get('/{code}', [HsCodeApiController::class, 'show']);
    Route::get('/{code}/hierarchy', [HsCodeApiController::class, 'hierarchy']);
    Route::get('/{code}/children', [HsCodeApiController::class, 'children']);
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

// API endpoint for autocomplete (future use)
Route::get('/api/hs-codes/search', function(Request $request) {
    $query = $request->input('q', '');
    
    if (strlen($query) < 2) {
        return response()->json([]);
    }
    
    $results = DB::table('hs_codes')
        ->where('hs_code', 'LIKE', "%{$query}%")
        ->orWhere('description_id', 'LIKE', "%{$query}%")
        ->limit(10)
        ->get(['hs_code', 'description_id', 'hs_level']);
    
    return response()->json($results);
})->name('api.hs-codes.search');

// ============================================
// FIELD DOCUMENTATION ROUTES
// ============================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard Dokumentasi Lapangan
    Route::get('/field-docs', [App\Http\Controllers\Admin\FieldDocController::class, 'index'])
        ->name('field-docs.index');
    
    // Upload Form (bisa langsung atau via shipment number)
    Route::get('/field-docs/upload/{shipment?}', [App\Http\Controllers\Admin\FieldDocController::class, 'upload'])
        ->name('field-docs.upload');
    
    // Gallery per Shipment
    Route::get('/field-docs/gallery/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class, 'gallery'])
        ->name('field-docs.gallery');
    
    // QR Code Display & Download
    Route::get('/field-docs/qr/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class, 'qrCode'])
        ->name('field-docs.qr');
    
    Route::get('/field-docs/qr/{shipment}/download', [App\Http\Controllers\Admin\FieldDocController::class, 'downloadQr'])
        ->name('field-docs.qr-download');
    
    // Export PDF Report
    Route::get('/field-docs/export/{shipment}', [App\Http\Controllers\Admin\FieldDocController::class, 'exportPdf'])
        ->name('field-docs.export-pdf');
    
    // API Search Shipments (untuk autocomplete)
    Route::get('/field-docs/api/shipments/search', [App\Http\Controllers\Admin\FieldDocController::class, 'searchShipments'])
        ->name('field-docs.search-shipments');
});

// Field Officer Mobile Upload (bisa diberi middleware khusus)
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/upload/{shipment?}', [App\Http\Controllers\Admin\FieldDocController::class, 'mobileUpload'])
        ->name('field-upload');
});

// Field Documentation - Delete Photo Routes (admin & owner only)
Route::middleware(['auth', 'admin'])->prefix('admin/field-docs')->name('admin.field-docs.')->group(function () {
    Route::delete('/photo/{photo}', [App\Http\Controllers\Admin\FieldDocController::class, 'deletePhoto'])->name('delete-photo');
    Route::post('/photos/bulk-delete', [App\Http\Controllers\Admin\FieldDocController::class, 'bulkDeletePhotos'])->name('bulk-delete-photos');
});

// Field Documentation - Photo Delete Routes
Route::middleware(['auth'])->group(function () {
    Route::delete('/admin/field-docs/photo/{photo}', [App\Http\Controllers\Admin\FieldDocController::class, 'deletePhoto'])
        ->name('admin.field-docs.delete-photo');
    Route::post('/admin/field-docs/photos/bulk-delete', [App\Http\Controllers\Admin\FieldDocController::class, 'bulkDeletePhotos'])
        ->name('admin.field-docs.bulk-delete-photos');
    Route::get("/admin/field-docs/download-zip/{shipment}", [App\Http\Controllers\Admin\FieldDocController::class, "downloadZip"])
        ->name("admin.field-docs.download-zip");
});

// Temporary route untuk fix invoice - HAPUS SETELAH SELESAI
Route::get('/admin/fix-invoice-status/{invoice_number}', function($invoice_number) {
    $inv = App\Models\Invoice::where('invoice_number', $invoice_number)->first();
    if ($inv) {
        $statusBefore = $inv->status;
        $totalPaidBefore = $inv->total_paid;
        
        $inv->recalculateTotalPaid();
        $inv->refresh();
        
        return response()->json([
            'success' => true,
            'invoice' => $invoice_number,
            'before' => [
                'status' => $statusBefore,
                'total_paid' => number_format($totalPaidBefore ?? 0, 0, ',', '.')
            ],
            'after' => [
                'status' => $inv->status,
                'total_paid' => number_format($inv->total_paid, 0, ',', '.')
            ],
            'invoice_total' => number_format($inv->grand_total, 0, ',', '.'),
            'remaining' => number_format($inv->grand_total - $inv->total_paid, 0, ',', '.')
        ]);
    }
    return response()->json(['success' => false, 'message' => 'Invoice not found']);
})->middleware(['web', 'auth', 'admin']);

// Bank Reconciliation Routes
Route::middleware(['auth', 'verified'])->group(function () {
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
        foreach ($possiblePaths as $path) {
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $existingPath = $path;
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
        
        $fullPath = storage_path('app/public/' . $existingPath);
        $mimeType = $doc->mime_type ?? (function_exists('mime_content_type') ? mime_content_type($fullPath) : 'application/octet-stream');
        
        $inlineTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $disposition = in_array($mimeType, $inlineTypes) ? 'inline' : 'attachment';
        
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . ($doc->filename ?? basename($existingPath)) . '"',
        ]);
        
    })->name('document.view');
    
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
        foreach ($possiblePaths as $path) {
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $existingPath = $path;
                break;
            }
        }
        
        if (!$existingPath) {
            return response()->view('errors.document-not-found', ['document' => $doc], 404);
        }
        
        $fullPath = storage_path('app/public/' . $existingPath);
        return response()->download($fullPath, $doc->filename ?? basename($existingPath));
        
    })->name('document.download');
});

// API Search Customer untuk Simple Invoice
Route::get('/api/customers/search', function (Illuminate\Http\Request $request) {
    $query = $request->get('q', '');
    $customers = \App\Models\Customer::query()
        ->where('company_name', 'like', "%{$query}%")
        ->orWhere('phone', 'like', "%{$query}%")
        ->orWhere('city', 'like', "%{$query}%")
        ->orderBy('company_name')
        ->limit(20)
        ->get(['id', 'company_name', 'address', 'phone', 'city']);
    return response()->json($customers);
})->name('api.customers.search')->middleware('auth');
