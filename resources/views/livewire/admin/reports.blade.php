<div class="space-y-6">
    @section('header', 'Laporan / Analytics')

    {{-- Period Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-xs font-bold text-gray-500">PERIODE:</label>
                <input type="date" wire:model.live="startDate" class="border-gray-300 rounded-md shadow-sm text-sm">
                <span class="text-gray-400">s/d</span>
                <input type="date" wire:model.live="endDate" class="border-gray-300 rounded-md shadow-sm text-sm">
            </div>
            <div class="flex-1"></div>
            <button onclick="window.print()" class="bg-gray-800 hover:bg-black text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                🖨️ Cetak
            </button>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex border-b overflow-x-auto">
            @php
                $tabs = [
                    'executive' => ['icon' => '📊', 'label' => 'Executive Summary'],
                    'financial' => ['icon' => '💰', 'label' => 'Keuangan'],
                    'operations' => ['icon' => '🚢', 'label' => 'Operasional'],
                    'customers' => ['icon' => '👥', 'label' => 'Customer'],
                    'vendors' => ['icon' => '🏭', 'label' => 'Vendor'],
                    'services' => ['icon' => '📦', 'label' => 'Layanan'],
                ];
            @endphp
            @foreach($tabs as $key => $tab)
                <button wire:click="setTab('{{ $key }}')" 
                    class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $activeTab === $key ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    {{ $tab['icon'] }} {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        <div class="p-6">
            {{-- EXECUTIVE SUMMARY --}}
            @if($activeTab === 'executive')
                @include('livewire.admin.reports.executive', ['kpi' => $kpi ?? [], 'monthlyTrend' => $monthlyTrend ?? collect()])
            
            {{-- FINANCIAL --}}
            @elseif($activeTab === 'financial')
                @include('livewire.admin.reports.financial', [
                    'revenueByCustomer' => $revenueByCustomer ?? collect(),
                    'arAging' => $arAging ?? [],
                    'apByVendor' => $apByVendor ?? collect(),
                    'invoiceStatus' => $invoiceStatus ?? collect()
                ])
            
            {{-- OPERATIONS --}}
            @elseif($activeTab === 'operations')
                @include('livewire.admin.reports.operations', [
                    'shipments' => $shipments ?? collect(),
                    'byStatus' => $byStatus ?? collect(),
                    'byService' => $byService ?? collect(),
                    'byShipmentType' => $byShipmentType ?? collect(),
                    'topRoutes' => $topRoutes ?? collect(),
                    'summary' => $summary ?? [],
                    'customers' => $customers ?? collect()
                ])
            
            {{-- CUSTOMERS --}}
            @elseif($activeTab === 'customers')
                @include('livewire.admin.reports.customers', [
                    'customerPerformance' => $customerPerformance ?? collect(),
                    'newCustomers' => $newCustomers ?? 0,
                    'returningCustomers' => $returningCustomers ?? 0,
                    'totalCustomers' => $totalCustomers ?? 0
                ])
            
            {{-- VENDORS --}}
            @elseif($activeTab === 'vendors')
                @include('livewire.admin.reports.vendors', [
                    'vendorPerformance' => $vendorPerformance ?? collect(),
                    'byCategory' => $byCategory ?? collect(),
                    'totalVendors' => $totalVendors ?? 0
                ])
            
            {{-- SERVICES --}}
            @elseif($activeTab === 'services')
                @include('livewire.admin.reports.services', [
                    'servicePerformance' => $servicePerformance ?? collect(),
                    'shipmentTypePerformance' => $shipmentTypePerformance ?? collect(),
                    'containerAnalysis' => $containerAnalysis ?? collect(),
                    'topCommodities' => $topCommodities ?? collect()
                ])
            @endif
        </div>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            body * { visibility: hidden; }
            .space-y-6, .space-y-6 * { visibility: visible; }
            .space-y-6 { position: absolute; left: 0; top: 0; width: 100%; }
            button, [wire\:click] { display: none !important; }
        }
    </style>
</div>
