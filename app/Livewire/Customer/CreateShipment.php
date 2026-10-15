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
    public $volume;
    public $notes;
    public $hs_code;

    public function mount()
    {
        $user = Auth::user();
        abort_unless($user && $user->canEditCustomerPortal() && $user->getCustomerId(), 403, 'Akun View-Only tidak diizinkan membuat booking baru.');

        if (request()->has('origin')) {
            $this->origin = request()->get('origin');
        }
        if (request()->has('destination')) {
            $this->destination = request()->get('destination');
        }
        if (request()->has('hs_code')) {
            $this->hs_code = request()->get('hs_code');
        }
        if (request()->has('weight')) {
            $this->weight = request()->get('weight');
        }
        if (request()->has('volume')) {
            $this->volume = request()->get('volume');
        }
    }

    // Rules Validasi
    protected $rules = [
        'origin' => 'required|string|max:255',
        'destination' => 'required|string|max:255',
        'service_type' => 'required',
        'shipment_type' => 'required',
        'container_mode' => 'required',
        'pieces' => 'required|numeric|min:1',
        'package_type' => 'required',
        'weight' => 'nullable|numeric|min:0',
        'volume' => 'nullable|numeric|min:0',
    ];

    public function setQuickRoute($origin, $destination)
    {
        $this->origin = $origin;
        $this->destination = $destination;
    }

    public function save()
    {
        $user = Auth::user();
        $customerId = $user?->getCustomerId();
        abort_unless($user && $user->canEditCustomerPortal() && $customerId, 403, 'Akun View-Only tidak diizinkan membuat booking baru.');

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
        $payload = [
            'customer_id' => $customerId,
            'awb_number' => $refNumber, // <--- SUDAH SESUAI PREFIX
            'origin' => $this->origin,
            'destination' => $this->destination,
            'service_type' => $this->service_type,
            'shipment_type' => $this->shipment_type,
            'pieces' => $this->pieces,
            'weight' => $this->weight ?: 0,
            'volume' => $this->volume ?: 0,
            'status' => 'pending', 
            'notes' => $this->notes,
            'hs_code' => $this->hs_code,
        ];

        foreach ([
            'container_mode' => $this->container_mode,
            'container_info' => $this->container_info,
            'package_type' => $this->package_type,
        ] as $col => $val) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('shipments', $col)) {
                $payload[$col] = $val;
            }
        }

        $shipment = Shipment::create($payload);

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