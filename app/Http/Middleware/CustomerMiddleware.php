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
        $isCustomer = $user->hasRole('customer') || $user->role === 'customer';

        if (!$isCustomer) {
            // Staff/Admin yang coba akses customer portal -> redirect ke admin
            if ($user->isAdminLevel() || $user->hasRole(['admin', 'staff', 'director', 'manager'])) {
                return redirect('/admin/dashboard');
            }
            
            abort(403, 'Anda tidak memiliki akses ke portal customer.');
        }

        return $next($request);
    }
}
