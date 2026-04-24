<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (!Auth::attempt($credentials, $request->filled('remember'))) {
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput();
    }

    $request->session()->regenerate();

    $user = Auth::user();

    // Arahkan berdasarkan role (mendukung roles array baru & legacy)
    // Hanya redirect ke /field kalau field_uploader adalah satu-satunya role.
    // Staf yang merangkap petugas lapangan tetap masuk portal admin.
    if ($user->isOnlyFieldUploader()) {
        return redirect('/field');
    }

    if ($user->hasRole(['customer', 'customer_viewer'])) {
        return redirect()->intended('/customer/dashboard');
    }

    if ($user->isAdminLevel() || $user->hasRole([
        'super_admin', 'director', 'manager', 'supervisor',
        'staff_accounting', 'staff_operations', 'staff_sales',
        'staff_ppjk', 'staff_documentation', 'cashier', 'auditor',
        'admin', 'staff', 'finance', 'accounting',
    ])) {
        return redirect()->intended('/admin/dashboard');
    }

    // Role tidak dikenal — tendang keluar
    Auth::logout();
    return redirect('/login')->withErrors(['email' => 'Akun Anda tidak memiliki akses.']);
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
