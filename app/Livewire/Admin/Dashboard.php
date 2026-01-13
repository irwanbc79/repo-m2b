<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\CashTransaction;
use App\Models\Vendor;
use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $period = 'month';
    public $startDate;
    public $endDate;
    public $showCustomRange = false;

    public function mount()
    {
        // Initialize default dates
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        
        // Check access - only admin and director
        $user = Auth::user();
        $allowedRoles = ['admin', 'director', 'super_admin'];
        
        if (!$user->hasRole($allowedRoles)) {
            // Redirect atau tampilkan dashboard terbatas
        }
    }

    public function updatedPeriod($value)
    {
        if ($value === "custom") {
            $this->showCustomRange = true;
        } else {
            $this->showCustomRange = false;
            $now = now();
            switch($value) {
                case "today":
                    $this->startDate = $now->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
                case "week":
                    $this->startDate = $now->copy()->startOfWeek()->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
                case "month":
                    $this->startDate = $now->copy()->startOfMonth()->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
                case "year":
                    $this->startDate = $now->copy()->startOfYear()->format("Y-m-d");
                    $this->endDate = $now->format("Y-m-d");
                    break;
            }
        }
    }

    public function applyCustomRange()
    {
        if ($this->startDate && $this->endDate) {
            $this->period = "custom";
            $this->showCustomRange = true;
        }
    }

    public function getDateRange()
    {
        if ($this->period === "custom" && $this->startDate && $this->endDate) {
            return [
                "start" => \Carbon\Carbon::parse($this->startDate)->startOfDay(),
                "end" => \Carbon\Carbon::parse($this->endDate)->endOfDay()
            ];
        }
        
        $now = \Carbon\Carbon::now();
        return match($this->period) {
            "today" => ["start" => $now->copy()->startOfDay(), "end" => $now->copy()->endOfDay()],
            "week" => ["start" => $now->copy()->startOfWeek(), "end" => $now->copy()->endOfDay()],
            "month" => ["start" => $now->copy()->startOfMonth(), "end" => $now->copy()->endOfDay()],
            "year" => ["start" => $now->copy()->startOfYear(), "end" => $now->copy()->endOfDay()],
            default => ["start" => $now->copy()->startOfMonth(), "end" => $now->copy()->endOfDay()]
        };
    }

    public function getFormattedDateRange()
    {
        $range = $this->getDateRange();
        return $range["start"]->format("d M Y") . " - " . $range["end"]->format("d M Y");
    }

    public function getMainStats()
    {
        $dateRange = $this->getDateRange();
        $startDate = $dateRange["start"];
        $endDate = $dateRange["end"];

        $currentShipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->count();
        $currentRevenue = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        $prevStart = match($this->period) {
            'week' => $startDate->copy()->subWeek(),
            'month' => $startDate->copy()->subMonth(),
            'year' => $startDate->copy()->subYear(),
            default => $startDate->copy()->subMonth()
        };
        $prevEnd = $startDate->copy()->subDay();

        $prevShipments = Shipment::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevRevenue = Invoice::whereBetween('invoice_date', [$prevStart, $prevEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        return [
            'total_shipments' => Shipment::count(),
            'current_shipments' => $currentShipments,
            'shipment_growth' => $prevShipments > 0 ? round((($currentShipments - $prevShipments) / $prevShipments) * 100, 1) : 0,
            'active_shipments' => Shipment::whereIn('status', ['pending', 'in_progress', 'in_transit'])->count(),
            'completed_shipments' => Shipment::where('status', 'completed')->count(),
            'total_customers' => Customer::whereHas('user', fn($q) => $q->where('role', 'customer'))->count(),
            'new_customers' => Customer::where('created_at', '>=', $startDate)->count(),
            'current_revenue' => $currentRevenue,
            'revenue_growth' => $prevRevenue > 0 ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0,
            'total_vendors' => Vendor::count(),
        ];
    }

    public function getFinancialStats()
    {
        return [
            'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
            'unpaid_amount' => Invoice::where('status', 'unpaid')->sum('grand_total'),
            'overdue_invoices' => Invoice::where('status', 'unpaid')->where('due_date', '<', now())->count(),
            'overdue_amount' => Invoice::where('status', 'unpaid')->where('due_date', '<', now())->sum('grand_total'),
            'paid_this_month' => Invoice::where('status', 'paid')
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('grand_total'),
            'cash_in_today' => CashTransaction::whereDate('transaction_date', today())->where('type', 'in')->sum('amount'),
            'cash_out_today' => CashTransaction::whereDate('transaction_date', today())->where('type', 'out')->sum('amount'),
        ];
    }

    public function getAlerts()
    {
        $alerts = [];

        $overdueCount = Invoice::where('status', 'unpaid')->where('due_date', '<', now())->count();
        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'exclamation',
                'title' => "{$overdueCount} Invoice Overdue",
                'message' => 'Ada invoice yang sudah lewat jatuh tempo',
                'link' => route('admin.invoices.index'),
            ];
        }

        $pendingOld = Shipment::where('status', 'pending')->where('created_at', '<', now()->subDays(3))->count();
        if ($pendingOld > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'clock',
                'title' => "{$pendingOld} Shipment Pending Lama",
                'message' => 'Shipment pending lebih dari 3 hari',
                'link' => route('admin.shipments.index'),
            ];
        }

        $unreadEmails = Email::where('is_read', false)->count();
        if ($unreadEmails > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'mail',
                'title' => "{$unreadEmails} Email Belum Dibaca",
                'message' => 'Ada email baru yang perlu diproses',
                'link' => route('admin.inbox.index'),
            ];
        }

        $pendingProofs = Invoice::where('status', 'unpaid')->whereNotNull('payment_proof')->count();
        if ($pendingProofs > 0) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'document',
                'title' => "{$pendingProofs} Bukti Bayar Menunggu",
                'message' => 'Ada bukti pembayaran yang perlu diverifikasi',
                'link' => route('admin.invoices.index'),
            ];
        }

        return $alerts;
    }

    public function getMonthlyChartData()
    {
        $year = now()->year;
        
        $shipments = Shipment::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $revenue = Invoice::whereYear('invoice_date', $year)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('MONTH(invoice_date) as month, SUM(grand_total) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $shipmentData = [];
        $revenueData = [];
        for ($i = 1; $i <= 12; $i++) {
            $shipmentData[] = $shipments[$i] ?? 0;
            $revenueData[] = (float)($revenue[$i] ?? 0);
        }

        return ['shipments' => $shipmentData, 'revenue' => $revenueData];
    }

    public function getTopCustomers()
    {
        return Customer::withCount(['shipments' => fn($q) => $q->whereYear('created_at', now()->year)])
            ->having('shipments_count', '>', 0)
            ->orderBy('shipments_count', 'desc')
            ->limit(5)
            ->get();
    }

    public function getRecentShipments()
    {
        return Shipment::with('customer')->latest()->take(5)->get();
    }

    public function getShipmentsByStatus()
    {
        return Shipment::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getTodayStats()
    {
        return [
            'shipments_today' => Shipment::whereDate('created_at', today())->count(),
            'invoices_today' => Invoice::whereDate('invoice_date', today())->count(),
            'payments_today' => Invoice::whereDate('payment_date', today())->where('status', 'paid')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'mainStats' => $this->getMainStats(),
            'financialStats' => $this->getFinancialStats(),
            'alerts' => $this->getAlerts(),
            'chartData' => $this->getMonthlyChartData(),
            'topCustomers' => $this->getTopCustomers(),
            'recentShipments' => $this->getRecentShipments(),
            'shipmentsByStatus' => $this->getShipmentsByStatus(),
            'todayStats' => $this->getTodayStats(),
        ])->layout('layouts.admin');
    }
}
