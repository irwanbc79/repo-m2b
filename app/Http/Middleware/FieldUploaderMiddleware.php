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

        // Hanya field_uploader yang boleh akses portal ini
        if ($user->hasRole(['field_uploader'])) {
            return $next($request);
        }

        // Admin yang mencoba akses /field diarahkan ke admin portal mereka
        if ($user->isAdminLevel()) {
            return redirect('/admin/field-docs');
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
