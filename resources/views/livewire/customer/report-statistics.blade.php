<div class="p-6 space-y-6">
    @section('header', 'Laporan & Statistik')

    {{-- Year Filter --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Statistik Pengiriman</h2>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Tahun:</label>
            <select wire:model.live="year" class="border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                @foreach($availableYears as $y)
                <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Total Shipment</p>
            <p class="text-2xl font-bold text-blue-600">{{ $summary['total_shipments'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Total Invoice</p>
            <p class="text-2xl font-bold text-purple-600">{{ $summary['total_invoices'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Invoice Lunas</p>
            <p class="text-2xl font-bold text-green-600">{{ $summary['total_paid'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Belum Lunas</p>
            <p class="text-2xl font-bold text-red-600">{{ $summary['total_unpaid'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Total Biaya</p>
            <p class="text-lg font-bold text-orange-600">IDR {{ number_format($summary['total_spent'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase">Rata-rata/Shipment</p>
            <p class="text-lg font-bold text-teal-600">IDR {{ number_format($summary['avg_per_shipment'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Monthly Shipments Chart --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Shipment per Bulan
            </h3>
            <canvas id="shipmentChart" height="200"></canvas>
        </div>

        {{-- Monthly Revenue Chart --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Biaya per Bulan (IDR)
            </h3>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>

    {{-- Status & Type Charts --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Shipment by Status --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Status Shipment</h3>
            <canvas id="statusChart" height="200"></canvas>
        </div>

        {{-- Shipment by Type --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Tipe Shipment</h3>
            <canvas id="typeChart" height="200"></canvas>
        </div>

        {{-- Top Routes --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top 5 Rute</h3>
            @if(count($topRoutes) > 0)
            <div class="space-y-3">
                @foreach($topRoutes as $index => $route)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-xs font-bold">{{ $index + 1 }}</span>
                        <span class="text-sm text-gray-700 truncate max-w-[150px]">{{ $route['route'] }}</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $route['count'] }}x</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-sm text-center py-8">Belum ada data rute</p>
            @endif
        </div>
    </div>

    {{-- Chart.js Script --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:navigated', initCharts);
        document.addEventListener('DOMContentLoaded', initCharts);
        
        // Re-init on Livewire update
        Livewire.hook('morph.updated', () => {
            setTimeout(initCharts, 100);
        });

        function initCharts() {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const shipmentData = @json(array_values($shipmentStats));
            const revenueData = @json(array_values($revenueStats));
            const statusData = @json($shipmentByStatus);
            const typeData = @json($shipmentByType);

            // Destroy existing charts
            Chart.getChart('shipmentChart')?.destroy();
            Chart.getChart('revenueChart')?.destroy();
            Chart.getChart('statusChart')?.destroy();
            Chart.getChart('typeChart')?.destroy();

            // Shipment Chart
            new Chart(document.getElementById('shipmentChart'), {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Jumlah Shipment',
                        data: shipmentData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            // Revenue Chart
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Total Biaya',
                        data: revenueData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { 
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'IDR ' + (value / 1000000).toFixed(0) + 'jt';
                                }
                            }
                        } 
                    }
                }
            });

            // Status Chart
            const statusLabels = {
                'pending': 'Pending',
                'in_progress': 'In Progress',
                'in_transit': 'In Transit',
                'completed': 'Completed',
                'cancelled': 'Cancelled'
            };
            const statusColors = {
                'pending': '#fbbf24',
                'in_progress': '#3b82f6',
                'in_transit': '#8b5cf6',
                'completed': '#22c55e',
                'cancelled': '#ef4444'
            };
            
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusData).map(k => statusLabels[k] || k),
                    datasets: [{
                        data: Object.values(statusData),
                        backgroundColor: Object.keys(statusData).map(k => statusColors[k] || '#9ca3af')
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
                }
            });

            // Type Chart
            const typeColors = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];
            new Chart(document.getElementById('typeChart'), {
                type: 'pie',
                data: {
                    labels: Object.keys(typeData).map(t => t ? t.toUpperCase() : 'N/A'),
                    datasets: [{
                        data: Object.values(typeData),
                        backgroundColor: typeColors.slice(0, Object.keys(typeData).length)
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
                }
            });
        }
    </script>
</div>
