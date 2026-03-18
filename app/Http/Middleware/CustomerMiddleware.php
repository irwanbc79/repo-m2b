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
