<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Shipment;
use App\Models\Invoice;
use Carbon\Carbon;

class ReportStatistics extends Component
{
    public $year;
    public $availableYears = [];
    
    public function mount()
    {
        $user = Auth::user();
        
        if ($user->customer) {
            // Get available years from shipments
            $years = Shipment::where('customer_id', $user->customer->id)
                ->selectRaw('YEAR(created_at) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();
            
            $this->availableYears = !empty($years) ? $years : [now()->year];
            
            // PERBAIKAN: Default ke tahun TERAKHIR yang ada datanya, bukan tahun sekarang
            $this->year = !empty($years) ? $years[0] : now()->year;
        } else {
            $this->availableYears = [now()->year];
            $this->year = now()->year;
        }
    }

    public function updatedYear()
    {
        // Trigger re-render when year changes
    }

    public function getShipmentStats()
    {
        $user = Auth::user();
        if (!$user->customer) {
            return $this->emptyStats();
        }

        $customerId = $user->customer->id;

        // Monthly shipment count
        $monthlyShipments = Shipment::where('customer_id', $customerId)
            ->whereYear('created_at', $this->year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill empty months
        $shipmentData = [];
        for ($i = 1; $i <= 12; $i++) {
            $shipmentData[$i] = $monthlyShipments[$i] ?? 0;
        }

        return $shipmentData;
    }

    public function getRevenueStats()
    {
        $user = Auth::user();
        if (!$user->customer) {
            return $this->emptyStats();
        }

        $customerId = $user->customer->id;

        // Monthly invoice total
        $monthlyRevenue = Invoice::where('customer_id', $customerId)
            ->whereYear('invoice_date', $this->year)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('MONTH(invoice_date) as month, SUM(grand_total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill empty months
        $revenueData = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenueData[$i] = (float)($monthlyRevenue[$i] ?? 0);
        }

        return $revenueData;
    }

    public function getShipmentByStatus()
    {
        $user = Auth::user();
        if (!$user->customer) {
            return [];
        }

        $customerId = $user->customer->id;

        return Shipment::where('customer_id', $customerId)
            ->whereYear('created_at', $this->year)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function getShipmentByType()
    {
        $user = Auth::user();
        if (!$user->customer) {
            return [];
        }

        $customerId = $user->customer->id;

        return Shipment::where('customer_id', $customerId)
            ->whereYear('created_at', $this->year)
            ->selectRaw('shipment_type, COUNT(*) as count')
            ->groupBy('shipment_type')
            ->pluck('count', 'shipment_type')
            ->toArray();
    }

    public function getSummaryStats()
    {
        $user = Auth::user();
        if (!$user->customer) {
            return [
                'total_shipments' => 0,
                'total_invoices' => 0,
                'total_paid' => 0,
                'total_unpaid' => 0,
                'total_spent' => 0,
                'avg_per_shipment' => 0,
            ];
        }

        $customerId = $user->customer->id;

        $totalShipments = Shipment::where('customer_id', $customerId)
            ->whereYear('created_at', $this->year)
            ->count();

        $invoiceStats = Invoice::where('customer_id', $customerId)
            ->whereYear('invoice_date', $this->year)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN status = "unpaid" THEN 1 ELSE 0 END) as unpaid,
                SUM(CASE WHEN status = "paid" THEN grand_total ELSE 0 END) as total_spent
            ')
            ->first();

        return [
            'total_shipments' => $totalShipments,
            'total_invoices' => $invoiceStats->total ?? 0,
            'total_paid' => $invoiceStats->paid ?? 0,
            'total_unpaid' => $invoiceStats->unpaid ?? 0,
            'total_spent' => $invoiceStats->total_spent ?? 0,
            'avg_per_shipment' => $totalShipments > 0 ? ($invoiceStats->total_spent ?? 0) / $totalShipments : 0,
        ];
    }

    public function getTopRoutes()
    {
        $user = Auth::user();
        if (!$user->customer) {
            return [];
        }

        $customerId = $user->customer->id;

        return Shipment::where('customer_id', $customerId)
            ->whereYear('created_at', $this->year)
            ->selectRaw('CONCAT(origin, " → ", destination) as route, COUNT(*) as count')
            ->groupBy('route')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function emptyStats()
    {
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $data[$i] = 0;
        }
        return $data;
    }

    public function render()
    {
        return view('livewire.customer.report-statistics', [
            'shipmentStats' => $this->getShipmentStats(),
            'revenueStats' => $this->getRevenueStats(),
            'shipmentByStatus' => $this->getShipmentByStatus(),
            'shipmentByType' => $this->getShipmentByType(),
            'summary' => $this->getSummaryStats(),
            'topRoutes' => $this->getTopRoutes(),
        ])->layout('layouts.customer');
    }
}
