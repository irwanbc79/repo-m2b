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
        // Belum login
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // ❌ STAFF / ADMIN DILARANG MASUK CUSTOMER
        if ($user->role !== 'customer') {
            return redirect('/admin/dashboard');
        }

        return $next($request);
    }
}
