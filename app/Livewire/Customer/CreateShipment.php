<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\ShipmentCreated; // Opsional: Jika ada email notifikasi

class CreateShipment extends Component
{
    // Data Form
    public $origin;
    public $destination;
    public $service_type = 'import'; // Default
    public $shipment_type = 'sea';   // Default
    public $container_mode = 'LCL';  // Default
    public $container_info;
    public $pieces;
    public $package_type = 'Colli';
    public $weight;
    public $notes;

    // Rules Validasi
    protected $rules = [
        'origin' => 'required|string|max:255',
        'destination' => 'required|string|max:255',
        'service_type' => 'required',
        'shipment_type' => 'required',
        'container_mode' => 'required',
        'pieces' => 'required|numeric|min:1',
        'package_type' => 'required',
        'weight' => 'required|numeric|min:0.1',
    ];

    public function save()
    {
        $this->validate();

        // --- LOGIKA BARU: SESUAIKAN PREFIX DENGAN SERVICE TYPE ---
        $prefix = match($this->service_type) {
            'import' => 'IMP',
            'export' => 'EXP',
            'domestic' => 'DOM',
            default => 'BKG' // Fallback jika aneh-aneh
        };

        // Format: IMP-YYMMDD-XXX
        $refNumber = $prefix . '-' . date('ymd') . '-' . rand(100, 999);

        // Simpan ke Database
        $shipment = Shipment::create([
            'customer_id' => Auth::user()->customer->id,
            'awb_number' => $refNumber, // <--- SUDAH SESUAI PREFIX
            'origin' => $this->origin,
            'destination' => $this->destination,
            'service_type' => $this->service_type,
            'shipment_type' => $this->shipment_type,
            'container_mode' => $this->container_mode,
            'container_info' => $this->container_info,
            'pieces' => $this->pieces,
            'package_type' => $this->package_type,
            'weight' => $this->weight,
            'status' => 'pending', 
            'notes' => $this->notes,
        ]);

        // Opsional: Kirim Email Notifikasi ke Admin
        // try { Mail::to('admin@m2b.co.id')->send(new NewBookingAlert($shipment)); } catch (\Exception $e) {}

        session()->flash('message', 'Booking berhasil dibuat! Ref No: ' . $refNumber);
        return redirect()->route('customer.shipments.index');
    }

    public function render()
    {
        return view('livewire.customer.create-shipment')->layout('layouts.customer');
    }
}