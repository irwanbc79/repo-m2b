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

        $isImpersonating = $request->session()->has('impersonator_id');

        // Akun nonaktif / menunggu persetujuan admin tidak boleh masuk portal.
        // Kecuali admin yang sedang impersonate (preview) -> tetap boleh lihat.
        if (! $user->is_active && ! $isImpersonating) {
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

        if (!$isCustomer && ! $isImpersonating) {
            // Non-customer yang coba akses customer portal -> redirect ke admin
            return redirect('/admin/dashboard');
        }

        // Catat bahwa customer "melihat" banner pengingat (kunjungan portal saat
        // data belum lengkap). Hanya untuk customer asli, bukan saat impersonate.
        if (! $isImpersonating) {
            $customer = $user->customer;
            if ($customer
                && ! $customer->profile_reminder_seen_at
                && ! $customer->profile_completed_at
                && $customer->dataQuality()['level'] !== 'good') {
                $customer->forceFill(['profile_reminder_seen_at' => now()])->save();
            }
        }

        return $next($request);
    }
}
