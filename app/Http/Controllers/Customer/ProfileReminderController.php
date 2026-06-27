<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProfileReminderController extends Controller
{
    /**
     * Customer menutup banner pengingat ("Ingatkan nanti").
     * Catat waktunya supaya admin tahu banner sudah ditutup (bukan diabaikan diam).
     * Tidak dicatat saat admin sedang impersonate.
     */
    public function dismiss()
    {
        if (session()->has('impersonator_id')) {
            return response()->noContent();
        }

        $customer = Auth::user()?->customer;
        if ($customer && ! $customer->profile_reminder_dismissed_at) {
            $customer->forceFill(['profile_reminder_dismissed_at' => now()])->save();
        }

        return response()->noContent();
    }
}
