<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Mulai "Lihat sebagai customer": admin login sementara sebagai user customer
     * agar bisa melihat portal persis seperti customer (termasuk banner pengingat).
     */
    public function start(Customer $customer)
    {
        $admin = Auth::user();

        // Hanya admin level yang boleh impersonate.
        if (! $admin || ! $admin->isAdminLevel()) {
            abort(403, 'Tidak punya akses.');
        }

        $target = $customer->user;
        if (! $target) {
            return redirect()->route('admin.customers.index')
                ->with('error', 'Customer ini belum punya akun login.');
        }

        // Simpan identitas admin asli agar bisa kembali.
        session(['impersonator_id' => $admin->id]);

        Auth::login($target);

        return redirect()->route('customer.dashboard');
    }

    /**
     * Berhenti impersonate: kembali ke akun admin semula.
     */
    public function stop()
    {
        $impersonatorId = session('impersonator_id');

        if ($impersonatorId && $admin = User::find($impersonatorId)) {
            Auth::login($admin);
        }

        session()->forget('impersonator_id');

        return redirect()->route('admin.customers.index')
            ->with('message', 'Selesai melihat sebagai customer. Anda kembali sebagai admin.');
    }
}
