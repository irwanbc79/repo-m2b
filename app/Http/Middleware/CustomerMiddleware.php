<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Akun nonaktif / menunggu persetujuan admin tidak boleh masuk portal.
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda belum aktif / masih menunggu persetujuan admin.',
            ]);
        }

        // Customer harus punya role 'customer'
        // Cek di roles array atau role single
        $primaryRole = $user->getPrimaryRole();
        $isCustomer = $primaryRole === 'customer' || $user->hasRole('customer');

        if (!$isCustomer) {
            // Non-customer yang coba akses customer portal -> redirect ke admin
            return redirect('/admin/dashboard');
        }

        return $next($request);
    }
}
