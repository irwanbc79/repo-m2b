<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FieldUploaderMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin level juga boleh akses (untuk monitoring/testing)
        if ($user->isAdminLevel() || $user->hasRole(['field_uploader'])) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
