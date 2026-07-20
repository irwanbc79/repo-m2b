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
                'testimonialCta' => null,
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

        // Ajakan isi testimoni: ada pengiriman selesai & belum ada testimoni terisi/approved.
        $testimonialCta = null;
        try {
            if ($stats['completed'] > 0) {
                $t = \App\Models\Testimonial::where('customer_id', $user->customer->id)
                    ->where('status', '!=', 'rejected')->latest()->first();
                if (! $t) {
                    $testimonialCta = 'invite';        // belum ada sama sekali
                } elseif ($t->status === 'pending' && ! $t->isFilled()) {
                    $testimonialCta = 'invite';        // ada record kosong dari follow-up
                } elseif ($t->status === 'pending') {
                    $testimonialCta = 'review';        // sudah diisi, menunggu
                }
                // approved → tidak perlu CTA
            }
        } catch (\Throwable $e) {
            \Log::warning('Dashboard testimonialCta gagal: ' . $e->getMessage());
        }

        return view('livewire.customer.dashboard', [
            'stats' => $stats,
            'shipments' => $shipments,
            'docRequests' => $docRequests,
            'testimonialCta' => $testimonialCta,
        ])->layout('layouts.customer');
    }
}