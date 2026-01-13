<div class="space-y-8">
    @section('header', 'Dashboard Overview')
    
    {{-- Header Selamat Datang --}}
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-m2b-primary flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Halo, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-gray-600 mt-1">Selamat datang di M2B Portal. Pantau kiriman logistik Anda secara real-time.</p>
        </div>
        <div class="text-left md:text-right bg-gray-50 px-5 py-3 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Customer Code</p>
            <p class="text-xl font-black text-m2b-primary font-mono tracking-tight">{{ auth()->user()->customer->customer_code ?? '-' }}</p>
        </div>
    </div>

    {{-- Statistik Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 transition hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Shipments</p>
                    <h3 class="text-4xl font-black text-m2b-primary mt-2">{{ $stats['total'] }}</h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-m2b-primary">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h8a1 1 0 001-1v-1z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 16H13M21 16v-1a1 1 0 00-1-1h-6v1a1 1 0 001 1h6z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 transition hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active / In Transit</p>
                    <h3 class="text-4xl font-black text-m2b-accent mt-2">{{ $stats['active'] }}</h3>
                </div>
                <div class="p-3 bg-red-50 rounded-xl text-m2b-accent">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 transition hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Completed</p>
                    <h3 class="text-4xl font-black text-green-600 mt-2">{{ $stats['completed'] }}</h3>
                </div>
                <div class="p-3 bg-green-50 rounded-xl text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Recent Shipments (Full Width) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Recent Activity
            </h3>
            <a href="{{ route('customer.shipments.index') }}" class="text-xs text-m2b-primary hover:text-m2b-accent font-bold uppercase tracking-wide flex items-center gap-1 border border-blue-100 px-3 py-1.5 rounded-lg hover:bg-white transition">
                View All Shipments <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-white text-gray-500 font-bold uppercase text-[10px] tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Reference No</th>
                        <th class="px-6 py-4">Route Info</th>
                        <th class="px-6 py-4">Est. Arrival</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($shipments as $shipment)
                    <tr class="hover:bg-blue-50/50 transition duration-150 group">
                        <td class="px-6 py-4">
                            <span class="font-black text-m2b-primary text-sm block">{{ $shipment->awb_number }}</span>
                            <span class="text-[10px] text-gray-400">{{ $shipment->service_type }} • {{ $shipment->container_mode }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-xs font-medium text-gray-700">
                                <span>{{ $shipment->origin }}</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                <span>{{ $shipment->destination }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($shipment->estimated_arrival)
                                <span class="font-mono text-gray-600 text-xs">{{ \Carbon\Carbon::parse($shipment->estimated_arrival)->format('d M Y') }}</span>
                            @else
                                <span class="text-gray-400 text-xs italic">TBA</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                @if($shipment->status == 'completed') bg-green-100 text-green-700 border-green-200
                                @elseif($shipment->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                                @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('customer.shipment.show', $shipment->id) }}" class="text-xs font-bold text-gray-400 group-hover:text-m2b-accent hover:underline transition flex items-center justify-end gap-1">
                                Track Details <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm italic">
                            Belum ada data pengiriman terbaru.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>