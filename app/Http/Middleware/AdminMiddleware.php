<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Hanya blok kalau field_uploader murni (tidak punya role admin/staff lain).
        // Staf yang merangkap petugas lapangan tetap boleh masuk portal admin.
        if ($user->isOnlyFieldUploader()) {
            return redirect('/field');
        }

        // Check if user has admin level access (level >= 60)
        if (!$user->isAdminLevel() && !$user->hasRole(['super_admin', 'admin', 'staff', 'director', 'manager', 'supervisor', 'staff_accounting', 'staff_operations', 'staff_sales', 'staff_ppjk', 'staff_documentation', 'cashier', 'auditor', 'finance', 'konsultan_pajak'])) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
