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
                'shipments' => [],
                'docRequests' => collect(),
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

        // Dokumen yang DIMINTA staf M2B (belum dipenuhi) — lintas semua shipment.
        // Aditif & aman: bila tabel belum ada, kembalikan koleksi kosong.
        $docRequests = collect();
        try {
            $docRequests = \App\Models\DocumentRequirement::query()
                ->whereHas('shipment', fn ($q) => $q->where('customer_id', $user->customer->id))
                ->where('status', 'requested')
                ->with('shipment:id,awb_number')
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->get()
                ->groupBy('shipment_id');
        } catch (\Throwable $e) {
            \Log::warning('Dashboard docRequests gagal: ' . $e->getMessage());
        }

        return view('livewire.customer.dashboard', [
            'stats' => $stats,
            'shipments' => $shipments,
            'docRequests' => $docRequests,
        ])->layout('layouts.customer');
    }
}