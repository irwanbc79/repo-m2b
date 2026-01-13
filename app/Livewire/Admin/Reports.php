<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\Customer;

class Reports extends Component
{
    public $startDate;
    public $endDate;
    public $status = '';
    public $customerId = '';

    public function mount()
    {
        // Default: Tanggal 1 bulan ini sampai hari ini
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function render()
    {
        // Query Dasar
        $query = Shipment::with('customer')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);

        // Filter Tambahan
        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }
        if (!empty($this->customerId)) {
            $query->where('customer_id', $this->customerId);
        }

        $shipments = $query->latest()->get();
        $customers = Customer::orderBy('company_name')->get();

        // HITUNG RINGKASAN BARU (Sesuai Request)
        // Kita pakai collection filtering agar tidak query DB berulang kali
        $summary = [
            'total_count' => $shipments->count(),

            // Total Selesai
            'completed_count' => $shipments->where('status', 'completed')->count(),

            // Total Masih Jalan (Pending/In Progress/In Transit)
            'active_count' => $shipments->whereIn('status', ['pending', 'in_progress', 'in_transit'])->count(),
        ];

        return view('livewire.admin.reports', [
            'shipments' => $shipments,
            'customers' => $customers,
            'summary' => $summary
        ])->layout('layouts.admin');
    }
}