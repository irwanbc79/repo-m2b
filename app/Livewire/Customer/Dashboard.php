<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Shipment;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        // SECURITY CHECK: Pastikan user punya profil customer
        // Jika tidak ada, tampilkan dashboard kosong (JANGAN CRASH)
        if (!$user->customer) {
            return view('livewire.customer.dashboard', [
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'completed' => 0
                ],
                'shipments' => []
            ])->layout('layouts.customer');
        }

        // Ambil Statistik
        $stats = [
            'total' => $user->customer->shipments()->count(),
            'active' => $user->customer->shipments()->whereIn('status', ['pending', 'in_progress', 'in_transit'])->count(),
            'completed' => $user->customer->shipments()->where('status', 'completed')->count(),
        ];

        // Ambil 5 Shipment Terakhir
        $shipments = $user->customer->shipments()->latest()->take(5)->get();

        return view('livewire.customer.dashboard', [
            'stats' => $stats,
            'shipments' => $shipments
        ])->layout('layouts.customer');
    }
}