<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;

class ShipmentList extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
{
    $user = Auth::user()->loadMissing('customer');

    // Defensive guard: jika relasi customer belum siap / tidak ada
    if (!$user->customer) {
        return view('livewire.customer.shipment-list', [
            'shipments' => Shipment::whereRaw('1=0')->paginate(10),
        ])->layout('layouts.customer');
    }

    $customerId = $user->customer->id;

    $shipments = Shipment::where('customer_id', $customerId)
        ->where(function ($query) {
            if ($this->search) {
                $term = '%' . $this->search . '%';
                $query->where('awb_number', 'like', $term)
                      ->orWhere('origin', 'like', $term)
                      ->orWhere('destination', 'like', $term)
                      ->orWhere('status', 'like', $term)
                      ->orWhere('service_type', 'like', $term);
            }
        })
        ->latest()
        ->paginate(10);

    return view('livewire.customer.shipment-list', [
        'shipments' => $shipments
    ])->layout('layouts.customer');
}

}