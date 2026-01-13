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

    // 🔒 HARD RULE: arahkan berdasarkan role
    return match ($user->role) {
        'admin', 'staff', 'finance', 'accounting'
            => redirect()->intended('/admin/dashboard'),

        'customer'
            => redirect()->intended('/customer/dashboard'),

        default
            => redirect('/logout'), // role aneh = tendang keluar
    };
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
