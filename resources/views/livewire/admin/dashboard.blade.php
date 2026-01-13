<div class="space-y-6">
    @section('header', 'Admin Dashboard')

    {{-- Period Filter & Today Stats --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <h2 class="text-xl font-bold text-gray-800">Overview</h2>
            <select wire:model.live="period" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500">
                <option value="today">Hari Ini</option>
                <option value="week">Minggu Ini</option>
                <option value="month">Bulan Ini</option>
                <option value="year">Tahun Ini</option>
                <option value="custom">Custom Range</option>
            </select>
            
            {{-- Custom Date Range Picker --}}
            @if($showCustomRange || $period === "custom")
            <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg border">
                <div class="flex items-center gap-1">
                    <label class="text-xs text-gray-500">Dari:</label>
                    <input type="date" wire:model="startDate" class="text-sm border-gray-300 rounded focus:ring-blue-500 px-2 py-1">
                </div>
                <span class="text-gray-400">→</span>
                <div class="flex items-center gap-1">
                    <label class="text-xs text-gray-500">Sampai:</label>
                    <input type="date" wire:model="endDate" class="text-sm border-gray-300 rounded focus:ring-blue-500 px-2 py-1">
                </div>
                <button wire:click="applyCustomRange" class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Terapkan
                </button>
            </div>
            @endif
            
            {{-- Show selected date range --}}
            @if($period === "custom" && $startDate && $endDate)
            <span class="text-xs text-gray-500 bg-blue-50 px-2 py-1 rounded">
                📅 {{ \Carbon\Carbon::parse($startDate)->format("d M Y") }} - {{ \Carbon\Carbon::parse($endDate)->format("d M Y") }}
            </span>
            @endif
        </div>
        <div class="flex items-center gap-4 text-sm">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                📦 {{ $todayStats['shipments_today'] }} Shipment Hari Ini
            </span>
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium">
                💰 {{ $todayStats['payments_today'] }} Pembayaran Hari Ini
            </span>
        </div>
    </div>

    {{-- Alert Center --}}
    @if(count($alerts) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($alerts as $alert)
        <a href="{{ $alert['link'] }}" class="block p-4 rounded-xl border-l-4 transition hover:shadow-md
            {{ $alert['type'] === 'danger' ? 'bg-red-50 border-red-500' : '' }}
            {{ $alert['type'] === 'warning' ? 'bg-yellow-50 border-yellow-500' : '' }}
            {{ $alert['type'] === 'info' ? 'bg-blue-50 border-blue-500' : '' }}
            {{ $alert['type'] === 'success' ? 'bg-green-50 border-green-500' : '' }}">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-full
                    {{ $alert['type'] === 'danger' ? 'bg-red-100' : '' }}
                    {{ $alert['type'] === 'warning' ? 'bg-yellow-100' : '' }}
                    {{ $alert['type'] === 'info' ? 'bg-blue-100' : '' }}
                    {{ $alert['type'] === 'success' ? 'bg-green-100' : '' }}">
                    @if($alert['icon'] === 'exclamation')
                    <svg class="w-5 h-5 {{ $alert['type'] === 'danger' ? 'text-red-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    @elseif($alert['icon'] === 'clock')
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif($alert['icon'] === 'mail')
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    @else
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    @endif
                </div>
                <div>
                    <p class="font-bold text-sm text-gray-800">{{ $alert['title'] }}</p>
                    <p class="text-xs text-gray-500">{{ $alert['message'] }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif

    {{-- Main Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- Total Shipments --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Total Shipment</span>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-800">{{ $mainStats['total_shipments'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $mainStats['current_shipments'] }} periode ini</p>
        </div>

        {{-- Active/Pending --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Active</span>
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-yellow-600">{{ $mainStats['active_shipments'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending & In Transit</p>
        </div>

        {{-- Completed --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Completed</span>
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-green-600">{{ $mainStats['completed_shipments'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Selesai semua</p>
        </div>

        {{-- Revenue --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Revenue</span>
                <div class="p-2 bg-emerald-100 rounded-lg">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-black text-emerald-600">{{ number_format($mainStats['current_revenue']/1000000, 1) }}jt</p>
            @if($mainStats['revenue_growth'] != 0)
            <p class="text-xs mt-1 {{ $mainStats['revenue_growth'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $mainStats['revenue_growth'] > 0 ? '↑' : '↓' }} {{ abs($mainStats['revenue_growth']) }}%
            </p>
            @endif
        </div>

        {{-- Customers --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Customers</span>
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-purple-600">{{ $mainStats['total_customers'] }}</p>
            <p class="text-xs text-gray-500 mt-1">+{{ $mainStats['new_customers'] }} baru</p>
        </div>

        {{-- Vendors --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Vendors</span>
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-orange-600">{{ $mainStats['total_vendors'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Partner aktif</p>
        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-red-500 to-red-600 p-5 rounded-xl text-white">
            <p class="text-xs font-medium text-red-100 uppercase">Invoice Belum Lunas</p>
            <p class="text-2xl font-black mt-1">{{ $financialStats['unpaid_invoices'] }}</p>
            <p class="text-sm text-red-100 mt-1">IDR {{ number_format($financialStats['unpaid_amount']/1000000, 1) }} jt</p>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-5 rounded-xl text-white">
            <p class="text-xs font-medium text-orange-100 uppercase">Invoice Overdue</p>
            <p class="text-2xl font-black mt-1">{{ $financialStats['overdue_invoices'] }}</p>
            <p class="text-sm text-orange-100 mt-1">IDR {{ number_format($financialStats['overdue_amount']/1000000, 1) }} jt</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 p-5 rounded-xl text-white">
            <p class="text-xs font-medium text-green-100 uppercase">Kas Masuk Hari Ini</p>
            <p class="text-2xl font-black mt-1">IDR {{ number_format($financialStats['cash_in_today']/1000000, 1) }}jt</p>
            <p class="text-sm text-green-100 mt-1">Cash In Today</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-5 rounded-xl text-white">
            <p class="text-xs font-medium text-blue-100 uppercase">Kas Keluar Hari Ini</p>
            <p class="text-2xl font-black mt-1">IDR {{ number_format($financialStats['cash_out_today']/1000000, 1) }}jt</p>
            <p class="text-sm text-blue-100 mt-1">Cash Out Today</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Shipment Chart --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📦 Shipment per Bulan ({{ now()->year }})</h3>
            <canvas id="shipmentChart" height="200"></canvas>
        </div>

        {{-- Revenue Chart --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">💰 Revenue per Bulan ({{ now()->year }})</h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    {{-- Bottom Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Shipment Status Pie --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📊 Status Shipment</h3>
            <canvas id="statusChart" height="200"></canvas>
        </div>

        {{-- Top Customers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">🏆 Top 5 Customers</h3>
            @if($topCustomers->count() > 0)
            <div class="space-y-3">
                @foreach($topCustomers as $index => $customer)
                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 flex items-center justify-center rounded-full text-xs font-bold
                            {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="font-medium text-sm text-gray-800 truncate max-w-[150px]">{{ $customer->company_name }}</p>
                            <p class="text-xs text-gray-400">{{ $customer->customer_code }}</p>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-blue-600">{{ $customer->shipments_count }}x</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-sm text-center py-8">Belum ada data</p>
            @endif
        </div>

        {{-- Recent Shipments --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">🚚 Shipment Terbaru</h3>
                <a href="{{ route('admin.shipments.index') }}" class="text-xs text-blue-600 hover:underline">Lihat Semua →</a>
            </div>
            <div class="space-y-3">
                @foreach($recentShipments as $shipment)
                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50">
                    <div>
                        <p class="font-mono font-bold text-sm text-blue-600">{{ $shipment->awb_number }}</p>
                        <p class="text-xs text-gray-400">{{ $shipment->customer->company_name ?? '-' }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-bold rounded
                        {{ $shipment->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $shipment->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $shipment->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $shipment->status === 'in_transit' ? 'bg-purple-100 text-purple-700' : '' }}">
                        {{ strtoupper(str_replace('_', ' ', $shipment->status)) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', initCharts);
        document.addEventListener('livewire:navigated', initCharts);
        
        function initCharts() {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const shipmentData = @json($chartData['shipments']);
            const revenueData = @json($chartData['revenue']);
            const statusData = @json($shipmentsByStatus);

            Chart.getChart('shipmentChart')?.destroy();
            Chart.getChart('revenueChart')?.destroy();
            Chart.getChart('statusChart')?.destroy();

            new Chart(document.getElementById('shipmentChart'), {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Shipments',
                        data: shipmentData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderRadius: 6
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });

            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Revenue',
                        data: revenueData,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { 
                    responsive: true, 
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => 'IDR ' + (v/1000000).toFixed(0) + 'jt' } } }
                }
            });

            const statusLabels = { pending: 'Pending', in_progress: 'In Progress', in_transit: 'In Transit', completed: 'Completed', cancelled: 'Cancelled' };
            const statusColors = { pending: '#fbbf24', in_progress: '#3b82f6', in_transit: '#8b5cf6', completed: '#22c55e', cancelled: '#ef4444' };
            
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusData).map(k => statusLabels[k] || k),
                    datasets: [{ data: Object.values(statusData), backgroundColor: Object.keys(statusData).map(k => statusColors[k] || '#9ca3af') }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
            });
        }
    </script>
</div>
