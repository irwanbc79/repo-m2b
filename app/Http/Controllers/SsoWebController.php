<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SsoWebController extends Controller
{
    /**
     * Validasi SSO token dari mobile app, login user, redirect ke halaman tujuan.
     * Token valid 60 detik dan hanya bisa dipakai sekali.
     */
    public function login(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect('/login')->withErrors(['email' => 'Token SSO tidak ditemukan.']);
        }

        $data = Cache::pull("sso_token:{$token}"); // pull = ambil + hapus sekaligus

        if (!$data) {
            return redirect('/login')->withErrors(['email' => 'Token SSO tidak valid atau sudah kedaluwarsa.']);
        }

        $user = User::find($data['user_id']);

        if (!$user || !$user->is_active) {
            return redirect('/login')->withErrors(['email' => 'Akun tidak ditemukan atau tidak aktif.']);
        }

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        // Hanya izinkan redirect ke path internal (cegah open redirect)
        $redirect = $data['redirect'];
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/admin/dashboard';
        }

        return redirect($redirect);
    }
}
