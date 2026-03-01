{{-- KPI Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    {{-- Revenue --}}
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-green-100 text-xs font-medium">💵 Revenue (Dibayar)</p>
                <p class="text-2xl font-black mt-1">Rp {{ number_format($kpi['revenue'] ?? 0, 0, ',', '.') }}</p>
            </div>
            @if(($kpi['revenue_change'] ?? 0) != 0)
                <span class="text-xs px-2 py-1 rounded-full {{ ($kpi['revenue_change'] ?? 0) >= 0 ? 'bg-green-400' : 'bg-red-400' }}">
                    {{ ($kpi['revenue_change'] ?? 0) >= 0 ? '▲' : '▼' }} {{ abs($kpi['revenue_change'] ?? 0) }}%
                </span>
            @endif
        </div>
        <p class="text-green-200 text-xs mt-2">vs periode sebelumnya</p>
    </div>

    {{-- Gross Profit --}}
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white">
        <p class="text-blue-100 text-xs font-medium">📈 Gross Profit</p>
        <p class="text-2xl font-black mt-1">Rp {{ number_format($kpi['gross_profit'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-blue-200 text-xs mt-2">Margin: {{ $kpi['margin'] ?? 0 }}%</p>
    </div>

    {{-- Shipments --}}
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-purple-100 text-xs font-medium">🚢 Total Shipment</p>
                <p class="text-2xl font-black mt-1">{{ $kpi['shipments'] ?? 0 }}</p>
            </div>
            @if(($kpi['shipments_change'] ?? 0) != 0)
                <span class="text-xs px-2 py-1 rounded-full {{ ($kpi['shipments_change'] ?? 0) >= 0 ? 'bg-purple-400' : 'bg-red-400' }}">
                    {{ ($kpi['shipments_change'] ?? 0) >= 0 ? '▲' : '▼' }} {{ abs($kpi['shipments_change'] ?? 0) }}%
                </span>
            @endif
        </div>
        <p class="text-purple-200 text-xs mt-2">{{ $kpi['completed'] ?? 0 }} selesai ({{ $kpi['completion_rate'] ?? 0 }}%)</p>
    </div>

    {{-- Net Cash Flow --}}
    <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white">
        <p class="text-amber-100 text-xs font-medium">💰 Net Cash Flow</p>
        <p class="text-2xl font-black mt-1 {{ ($kpi['net_cash'] ?? 0) < 0 ? 'text-red-200' : '' }}">
            Rp {{ number_format($kpi['net_cash'] ?? 0, 0, ',', '.') }}
        </p>
        <p class="text-amber-200 text-xs mt-2">In: {{ number_format(($kpi['cash_in'] ?? 0)/1000000, 1) }}jt | Out: {{ number_format(($kpi['cash_out'] ?? 0)/1000000, 1) }}jt</p>
    </div>
</div>

{{-- Second Row KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    {{-- AR Outstanding --}}
    <div class="bg-white border border-red-200 rounded-xl p-4">
        <p class="text-red-600 text-xs font-bold">📋 Piutang (AR)</p>
        <p class="text-xl font-black text-gray-800 mt-1">Rp {{ number_format($kpi['ar_outstanding'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-xs text-red-500 mt-1">⚠️ {{ $kpi['overdue_count'] ?? 0 }} overdue (Rp {{ number_format(($kpi['overdue_amount'] ?? 0)/1000000, 1) }}jt)</p>
    </div>

    {{-- AP Outstanding --}}
    <div class="bg-white border border-orange-200 rounded-xl p-4">
        <p class="text-orange-600 text-xs font-bold">📑 Hutang (AP)</p>
        <p class="text-xl font-black text-gray-800 mt-1">Rp {{ number_format($kpi['ap_outstanding'] ?? 0, 0, ',', '.') }}</p>
        <p class="text-xs text-orange-500 mt-1">Total belum dibayar ke vendor</p>
        @if(($kpi['ap_period'] ?? 0) > 0)
            <p class="text-xs text-orange-400 mt-0.5">Rp {{ number_format($kpi['ap_period'], 0, ',', '.') }} dari periode ini</p>
        @endif
    </div>

    {{-- Active Customers --}}
    <div class="bg-white border border-blue-200 rounded-xl p-4">
        <p class="text-blue-600 text-xs font-bold">👥 Customer Aktif</p>
        <p class="text-xl font-black text-gray-800 mt-1">{{ $kpi['active_customers'] ?? 0 }}</p>
        <p class="text-xs text-blue-500 mt-1">Dalam periode ini</p>
    </div>

    {{-- Active Vendors --}}
    <div class="bg-white border border-green-200 rounded-xl p-4">
        <p class="text-green-600 text-xs font-bold">🏭 Vendor Aktif</p>
        <p class="text-xl font-black text-gray-800 mt-1">{{ $kpi['active_vendors'] ?? 0 }}</p>
        <p class="text-xs text-green-500 mt-1">Dalam periode ini</p>
    </div>
</div>

{{-- Monthly Trend Chart --}}
<div class="bg-white border rounded-xl p-6">
    <h3 class="font-bold text-gray-800 mb-4">📈 Trend 6 Bulan Terakhir</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-200">
                    <th class="text-left py-2 font-bold text-gray-600">Bulan</th>
                    <th class="text-right py-2 font-bold text-gray-600">Revenue</th>
                    <th class="text-right py-2 font-bold text-gray-600">Cost</th>
                    <th class="text-right py-2 font-bold text-gray-600">Profit</th>
                    <th class="text-right py-2 font-bold text-gray-600">Shipment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $trend)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 font-medium">{{ $trend['month'] }}</td>
                    <td class="py-3 text-right text-green-600">Rp {{ number_format($trend['revenue'], 0, ',', '.') }}</td>
                    <td class="py-3 text-right text-red-600">Rp {{ number_format($trend['cost'], 0, ',', '.') }}</td>
                    <td class="py-3 text-right font-bold {{ $trend['profit'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        Rp {{ number_format($trend['profit'], 0, ',', '.') }}
                    </td>
                    <td class="py-3 text-right">{{ $trend['shipments'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    {{-- Simple Bar Visualization --}}
    <div class="mt-6 space-y-2">
        <p class="text-xs font-bold text-gray-500 mb-2">REVENUE TREND</p>
        @php $maxRevenue = $monthlyTrend->max('revenue') ?: 1; @endphp
        @foreach($monthlyTrend as $trend)
        <div class="flex items-center gap-2">
            <span class="text-xs w-16 text-gray-500">{{ \Carbon\Carbon::parse('1 ' . $trend['month'])->format('M') }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-green-400 to-green-600 h-full rounded-full transition-all" 
                     style="width: {{ ($trend['revenue'] / $maxRevenue) * 100 }}%"></div>
            </div>
            <span class="text-xs w-24 text-right text-gray-600">{{ number_format($trend['revenue']/1000000, 1) }}jt</span>
        </div>
        @endforeach
    </div>
</div>

{{-- AP Aging & Cash Flow Forecast --}}
<div class="grid md:grid-cols-2 gap-6">
    {{-- AP Aging --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">📑 Aging Hutang Vendor (AP)</h3>
        <div class="space-y-3">
            @php
                $agingItems = [
                    ['key' => 'current', 'label' => 'Belum Jatuh Tempo', 'color' => 'green', 'icon' => '✅'],
                    ['key' => 'overdue_30', 'label' => '1-30 Hari Overdue', 'color' => 'yellow', 'icon' => '⏳'],
                    ['key' => 'overdue_60', 'label' => '31-60 Hari Overdue', 'color' => 'orange', 'icon' => '⚠️'],
                    ['key' => 'overdue_90', 'label' => '>60 Hari Overdue', 'color' => 'red', 'icon' => '🔴'],
                ];
                $maxAging = collect($apAging)->max(fn($a) => $a->total ?? 0) ?: 1;
            @endphp
            @foreach($agingItems as $ai)
                @php $agingData = $apAging[$ai['key']] ?? null; @endphp
                <div class="p-3 bg-{{ $ai['color'] }}-50 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium text-{{ $ai['color'] }}-800 text-sm">{{ $ai['icon'] }} {{ $ai['label'] }}</p>
                            <p class="text-xs text-{{ $ai['color'] }}-600">{{ $agingData->count ?? 0 }} bill</p>
                        </div>
                        <p class="font-bold text-{{ $ai['color'] }}-700 text-sm">Rp {{ number_format($agingData->total ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="mt-2 bg-{{ $ai['color'] }}-200 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-{{ $ai['color'] }}-500 h-full rounded-full" 
                             style="width: {{ (($agingData->total ?? 0) / $maxAging) * 100 }}%"></div>
                    </div>
                </div>
            @endforeach
            <div class="mt-2 p-2 bg-gray-100 rounded-lg flex justify-between">
                <span class="text-xs font-bold text-gray-600">TOTAL AP</span>
                <span class="text-sm font-black text-gray-800">Rp {{ number_format(collect($apAging)->sum(fn($a) => $a->total ?? 0), 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Cash Flow Forecast --}}
    <div class="bg-white border rounded-xl p-6">
        <h3 class="font-bold text-gray-800 mb-4">🔮 Proyeksi Cash Flow</h3>
        <div class="space-y-3">
            @foreach($cashFlowForecast as $forecast)
            <div class="p-3 border rounded-lg {{ $forecast['net'] >= 0 ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div class="flex justify-between items-start mb-2">
                    <p class="font-bold text-gray-800 text-sm">{{ $forecast['label'] }} ke depan</p>
                    <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $forecast['net'] >= 0 ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                        {{ $forecast['net'] >= 0 ? '▲ Surplus' : '▼ Defisit' }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <div>
                        <p class="text-gray-500">Masuk</p>
                        <p class="font-bold text-green-600">{{ number_format($forecast['cash_in']/1000000, 1) }}jt</p>
                        <p class="text-gray-400">{{ $forecast['cash_in_count'] }} invoice</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Keluar</p>
                        <p class="font-bold text-red-600">{{ number_format($forecast['cash_out']/1000000, 1) }}jt</p>
                        <p class="text-gray-400">{{ $forecast['cash_out_count'] }} bill</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Net</p>
                        <p class="font-black {{ $forecast['net'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ number_format($forecast['net']/1000000, 1) }}jt
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-3">* Berdasarkan jatuh tempo invoice & vendor bill yang belum dibayar</p>
    </div>
</div>
