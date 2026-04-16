<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Quotation;
use Carbon\Carbon;

class NotificationDropdown extends Component
{
    public $newBookings       = [];
    public $newCustomers      = [];
    public $dueInvoices       = [];
    public $approvedQuotations = [];
    public $totalNotif        = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    // Polling setiap 30 detik
    public function loadNotifications()
    {
        // 1. Booking Baru (Status Pending)
        $this->newBookings = Shipment::where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        // 2. Customer Baru (< 3 hari)
        $this->newCustomers = User::where('role', 'customer')
            ->where('created_at', '>=', Carbon::now()->subDays(3))
            ->latest()
            ->take(5)
            ->get();

        // 3. Tagihan Jatuh Tempo (Unpaid & Due Date <= H+3)
        try {
            $this->dueInvoices = Invoice::where('status', 'unpaid')
                ->where('due_date', '<=', Carbon::now()->addDays(3))
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $this->dueInvoices = collect([]);
        }

        // 4. Quotation yang baru disetujui customer (< 24 jam)
        try {
            $this->approvedQuotations = Quotation::where('approval_status', 'approved')
                ->where('approved_at', '>=', Carbon::now()->subHours(24))
                ->orderByDesc('approved_at')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $this->approvedQuotations = collect([]);
        }

        $this->totalNotif = count($this->newBookings)
            + count($this->newCustomers)
            + count($this->dueInvoices)
            + count($this->approvedQuotations);
    }

    public function render()
    {
        return view('livewire.admin.notification-dropdown');
    }
}