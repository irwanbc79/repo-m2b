<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Shipment;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Invoice;
use App\Models\JobCost;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Reports extends Component
{
    public $startDate;
    public $endDate;
    public $activeTab = 'executive';
    
    // Filters
    public $status = '';
    public $customerId = '';
    public $serviceType = '';
    public $shipmentType = '';

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $data = match($this->activeTab) {
            'executive' => $this->getExecutiveData(),
            'financial' => $this->getFinancialData(),
            'operations' => $this->getOperationsData(),
            'customers' => $this->getCustomerData(),
            'vendors' => $this->getVendorData(),
            'services' => $this->getServiceData(),
            default => $this->getExecutiveData(),
        };

        return view('livewire.admin.reports', array_merge($data, [
            'activeTab' => $this->activeTab,
            'customers' => Customer::orderBy('company_name')->get(),
        ]))->layout('layouts.admin');
    }

    // ========== EXECUTIVE SUMMARY ==========
    private function getExecutiveData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        
        // Previous period for comparison
        $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $prevStart = Carbon::parse($startDate)->subDays($daysDiff)->format('Y-m-d');
        $prevEnd = Carbon::parse($startDate)->subDay()->format('Y-m-d');

        // Current Period Stats
        $currentRevenue = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('type', 'commercial')
            ->whereIn('status', ['paid', 'partial'])
            ->sum('total_paid');

        $currentInvoiced = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('type', 'commercial')
            ->sum('grand_total');

        $currentCost = JobCost::whereHas('shipment', function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('amount');

        $currentShipments = Shipment::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedShipments = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')->count();

        // Previous Period Stats
        $prevRevenue = Invoice::whereBetween('invoice_date', [$prevStart, $prevEnd])
            ->where('type', 'commercial')
            ->whereIn('status', ['paid', 'partial'])
            ->sum('total_paid');

        $prevShipments = Shipment::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        // AR & AP
        $totalAR = Invoice::where('type', 'commercial')
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('SUM(grand_total - total_paid) as outstanding')
            ->value('outstanding') ?? 0;

        $totalAP = JobCost::where('status', 'unpaid')->sum('amount');

        // Cash Position
        $cashIn = CashTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'in')->sum('amount');
        $cashOut = CashTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'out')->sum('amount');

        // Overdue Invoices
        $overdueInvoices = Invoice::where('type', 'commercial')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->count();

        $overdueAmount = Invoice::where('type', 'commercial')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->selectRaw('SUM(grand_total - total_paid) as total')
            ->value('total') ?? 0;

        // Customer & Vendor Count
        $activeCustomers = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('customer_id')->count('customer_id');
        $activeVendors = JobCost::whereHas('shipment', function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->distinct('vendor_id')->count('vendor_id');

        // Monthly Trend (6 bulan)
        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            
            $revenue = Invoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                ->where('type', 'commercial')
                ->sum('grand_total');
            
            $cost = JobCost::whereHas('shipment', function($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('created_at', [$monthStart, $monthEnd]);
            })->sum('amount');
            
            $shipmentCount = Shipment::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            
            $monthlyTrend->push([
                'month' => $monthStart->format('M Y'),
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $revenue - $cost,
                'shipments' => $shipmentCount,
            ]);
        }

        return [
            'kpi' => [
                'revenue' => $currentRevenue,
                'revenue_change' => $prevRevenue > 0 ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0,
                'invoiced' => $currentInvoiced,
                'cost' => $currentCost,
                'gross_profit' => $currentInvoiced - $currentCost,
                'margin' => $currentInvoiced > 0 ? round((($currentInvoiced - $currentCost) / $currentInvoiced) * 100, 1) : 0,
                'shipments' => $currentShipments,
                'shipments_change' => $prevShipments > 0 ? round((($currentShipments - $prevShipments) / $prevShipments) * 100, 1) : 0,
                'completed' => $completedShipments,
                'completion_rate' => $currentShipments > 0 ? round(($completedShipments / $currentShipments) * 100, 1) : 0,
                'ar_outstanding' => $totalAR,
                'ap_outstanding' => $totalAP,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_cash' => $cashIn - $cashOut,
                'overdue_count' => $overdueInvoices,
                'overdue_amount' => $overdueAmount,
                'active_customers' => $activeCustomers,
                'active_vendors' => $activeVendors,
            ],
            'monthlyTrend' => $monthlyTrend,
        ];
    }

    // ========== FINANCIAL REPORT ==========
    private function getFinancialData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        // Revenue by Customer
        $revenueByCustomer = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('type', 'commercial')
            ->select('customer_id', DB::raw('SUM(grand_total) as total'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('customer_id')
            ->with('customer')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // AR Aging
        $arAging = [
            'current' => Invoice::where('type', 'commercial')
                ->whereIn('status', ['unpaid', 'partial'])
                ->where('due_date', '>=', now())
                ->selectRaw('SUM(grand_total - total_paid) as total, COUNT(*) as count')
                ->first(),
            'overdue_30' => Invoice::where('type', 'commercial')
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereBetween('due_date', [now()->subDays(30), now()->subDay()])
                ->selectRaw('SUM(grand_total - total_paid) as total, COUNT(*) as count')
                ->first(),
            'overdue_60' => Invoice::where('type', 'commercial')
                ->whereIn('status', ['unpaid', 'partial'])
                ->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)])
                ->selectRaw('SUM(grand_total - total_paid) as total, COUNT(*) as count')
                ->first(),
            'overdue_90' => Invoice::where('type', 'commercial')
                ->whereIn('status', ['unpaid', 'partial'])
                ->where('due_date', '<', now()->subDays(60))
                ->selectRaw('SUM(grand_total - total_paid) as total, COUNT(*) as count')
                ->first(),
        ];

        // AP by Vendor
        $apByVendor = JobCost::where('status', 'unpaid')
            ->select('vendor_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('vendor_id')
            ->with('vendor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Invoice Status Summary
        $invoiceStatus = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('type', 'commercial')
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'revenueByCustomer' => $revenueByCustomer,
            'arAging' => $arAging,
            'apByVendor' => $apByVendor,
            'invoiceStatus' => $invoiceStatus,
        ];
    }

    // ========== OPERATIONS REPORT ==========
    private function getOperationsData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $query = Shipment::with('customer')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }
        if (!empty($this->customerId)) {
            $query->where('customer_id', $this->customerId);
        }
        if (!empty($this->serviceType)) {
            $query->where('service_type', $this->serviceType);
        }
        if (!empty($this->shipmentType)) {
            $query->where('shipment_type', $this->shipmentType);
        }

        $shipments = $query->latest()->get();

        // By Status
        $byStatus = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // By Service Type
        $byService = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('service_type', DB::raw('COUNT(*) as count'))
            ->groupBy('service_type')
            ->pluck('count', 'service_type');

        // By Shipment Type
        $byShipmentType = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('shipment_type', DB::raw('COUNT(*) as count'))
            ->groupBy('shipment_type')
            ->pluck('count', 'shipment_type');

        // Top Routes
        $topRoutes = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('origin', 'destination', DB::raw('COUNT(*) as count'))
            ->groupBy('origin', 'destination')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Summary
        $summary = [
            'total_count' => $shipments->count(),
            'completed_count' => $shipments->where('status', 'completed')->count(),
            'active_count' => $shipments->whereIn('status', ['pending', 'in_progress', 'on_board', 'customs_released'])->count(),
            'cancelled_count' => $shipments->where('status', 'cancel')->count(),
        ];

        return [
            'shipments' => $shipments,
            'byStatus' => $byStatus,
            'byService' => $byService,
            'byShipmentType' => $byShipmentType,
            'topRoutes' => $topRoutes,
            'summary' => $summary,
        ];
    }

    // ========== CUSTOMER ANALYTICS ==========
    private function getCustomerData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        // Customer Performance
        $customerPerformance = Customer::withCount(['shipments' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->with(['invoices' => function($q) use ($startDate, $endDate) {
            $q->whereBetween('invoice_date', [$startDate, $endDate])
              ->where('type', 'commercial');
        }])
        ->get()
        ->map(function($customer) {
            $revenue = $customer->invoices->sum('grand_total');
            $paid = $customer->invoices->sum('total_paid');
            $outstanding = $revenue - $paid;
            
            return [
                'id' => $customer->id,
                'code' => $customer->customer_code,
                'name' => $customer->company_name,
                'shipments' => $customer->shipments_count,
                'revenue' => $revenue,
                'paid' => $paid,
                'outstanding' => $outstanding,
                'payment_terms' => $customer->payment_terms,
            ];
        })
        ->filter(fn($c) => $c['shipments'] > 0 || $c['revenue'] > 0)
        ->sortByDesc('revenue')
        ->values();

        // New vs Returning
        $allCustomerIds = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->pluck('customer_id')->unique();
        
        $existingCustomerIds = Shipment::where('created_at', '<', $startDate)
            ->pluck('customer_id')->unique();
        
        $newCustomers = $allCustomerIds->diff($existingCustomerIds)->count();
        $returningCustomers = $allCustomerIds->intersect($existingCustomerIds)->count();

        return [
            'customerPerformance' => $customerPerformance,
            'newCustomers' => $newCustomers,
            'returningCustomers' => $returningCustomers,
            'totalCustomers' => Customer::count(),
        ];
    }

    // ========== VENDOR ANALYTICS ==========
    private function getVendorData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        // Vendor Performance - Query langsung dari job_costs
        $vendorPerformance = JobCost::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('vendor_id')
            ->select('vendor_id', 
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(amount) as total_cost'),
                DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as paid'),
                DB::raw('SUM(CASE WHEN status != "paid" THEN amount ELSE 0 END) as unpaid')
            )
            ->groupBy('vendor_id')
            ->orderByDesc('total_cost')
            ->get()
            ->map(function($item) {
                $vendor = Vendor::find($item->vendor_id);
                return [
                    'id' => $item->vendor_id,
                    'code' => $vendor->code ?? '-',
                    'name' => $vendor->name ?? 'Unknown',
                    'category' => $vendor->category ?? '-',
                    'job_count' => $item->job_count,
                    'total_cost' => $item->total_cost,
                    'paid' => $item->paid,
                    'unpaid' => $item->unpaid,
                ];
            });

        // By Category
        $byCategory = Vendor::select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        return [
            'vendorPerformance' => $vendorPerformance,
            'byCategory' => $byCategory,
            'totalVendors' => Vendor::count(),
        ];
    }

    // ========== SERVICE/PRODUCT ANALYTICS ==========
    private function getServiceData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        // Service Type Performance
        $servicePerformance = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('service_type')
            ->selectRaw('COUNT(*) as shipment_count')
            ->groupBy('service_type')
            ->get()
            ->map(function($item) use ($startDate, $endDate) {
                $shipmentIds = Shipment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('service_type', $item->service_type)
                    ->pluck('id');
                
                $revenue = Invoice::whereIn('shipment_id', $shipmentIds)
                    ->where('type', 'commercial')
                    ->sum('grand_total');
                
                $cost = JobCost::whereIn('shipment_id', $shipmentIds)->sum('amount');
                
                return [
                    'service_type' => $item->service_type,
                    'shipment_count' => $item->shipment_count,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $revenue - $cost,
                    'margin' => $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // Shipment Type Performance (Air/Sea/Land)
        $shipmentTypePerformance = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('shipment_type')
            ->selectRaw('COUNT(*) as shipment_count')
            ->groupBy('shipment_type')
            ->get()
            ->map(function($item) use ($startDate, $endDate) {
                $shipmentIds = Shipment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('shipment_type', $item->shipment_type)
                    ->pluck('id');
                
                $revenue = Invoice::whereIn('shipment_id', $shipmentIds)
                    ->where('type', 'commercial')
                    ->sum('grand_total');
                
                $cost = JobCost::whereIn('shipment_id', $shipmentIds)->sum('amount');
                
                return [
                    'shipment_type' => $item->shipment_type,
                    'shipment_count' => $item->shipment_count,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $revenue - $cost,
                    'margin' => $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // Container Mode Analysis
        $containerAnalysis = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->select('container_mode', DB::raw('COUNT(*) as count'))
            ->groupBy('container_mode')
            ->pluck('count', 'container_mode');

        // Top Commodities (menggunakan HS Code dengan deskripsi)
        $topCommodities = Shipment::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('hs_code')
            ->where('hs_code', '!=', '')
            ->select('hs_code', DB::raw('COUNT(*) as count'))
            ->groupBy('hs_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function($item) use ($startDate, $endDate) {
                $hsInfo = \App\Models\HsCode::where('hs_code', $item->hs_code)->first();
                
                // Ambil detail shipment untuk HS Code ini
                $shipments = \App\Models\Shipment::whereBetween('created_at', [$startDate, $endDate])
                    ->where('hs_code', $item->hs_code)
                    ->with('customer')
                    ->get();
                
                return (object)[
                    'hs_code' => $item->hs_code,
                    'description' => $hsInfo->description_id ?? $item->hs_code,
                    'description_en' => $hsInfo->description_en ?? '',
                    'count' => $item->count,
                    'customers' => $shipments->pluck('customer.company_name')->filter()->unique()->take(3)->values(),
                    'origins' => $shipments->pluck('origin')->filter()->unique()->take(3)->values(),
                    'total_pieces' => $shipments->sum('pieces'),
                    'commodities' => $shipments->pluck('commodity')->filter()->unique()->take(2)->values(),
                ];
            });

        return [
            'servicePerformance' => $servicePerformance,
            'shipmentTypePerformance' => $shipmentTypePerformance,
            'containerAnalysis' => $containerAnalysis,
            'topCommodities' => $topCommodities,
        ];
    }
}
