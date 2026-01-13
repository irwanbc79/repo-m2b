<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Invoice; // Pastikan Model Invoice ada
use Carbon\Carbon;

class NotificationDropdown extends Component
{
    public $newBookings = [];
    public $newCustomers = [];
    public $dueInvoices = [];
    public $totalNotif = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    // Polling setiap 30 detik agar notifikasi masuk otomatis tanpa refresh
    public function loadNotifications()
    {
        // 1. Cek Booking Baru (Status Pending)
        $this->newBookings = Shipment::where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        // 2. Cek Customer Baru (Registrasi < 3 hari yang lalu)
        $this->newCustomers = User::where('role', 'customer')
            ->where('created_at', '>=', Carbon::now()->subDays(3))
            ->latest()
            ->take(5)
            ->get();

        // 3. Cek Tagihan Jatuh Tempo (Unpaid & Due Date <= H+3)
        // Pastikan tabel invoices ada kolom 'status' dan 'due_date'
        try {
            $this->dueInvoices = Invoice::where('status', 'unpaid')
                ->where('due_date', '<=', Carbon::now()->addDays(3))
                ->orderBy('due_date', 'asc') // Yang paling mepet/lewat ditaruh atas
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $this->dueInvoices = collect([]); // Fallback jika tabel belum siap
        }

        $this->totalNotif = count($this->newBookings) + count($this->newCustomers) + count($this->dueInvoices);
    }

    public function render()
    {
        return view('livewire.admin.notification-dropdown');
    }
}