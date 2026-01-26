<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">My Shipments</h2>
            <p class="text-gray-500 text-sm">Manage and track your ongoing shipment orders.</p>
        </div>
        
        <a href="{{ route('customer.shipments.create') }}" class="bg-m2b-primary hover:bg-blue-900 text-white px-6 py-3 rounded-lg font-bold shadow-md transition transform hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create New Booking
        </a>
    </div>

    {{-- Search Bar --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="relative flex-1">
            <input wire:model.live="search" type="text" placeholder="Search by AWB, Origin, Destination..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-m2b-primary transition">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 font-bold uppercase text-xs border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4">Ref Number / Route</th>
                        <th class="px-6 py-4">Service Info</th>
                        <th class="px-6 py-4">Cargo / HS Code</th>
                        <th class="px-6 py-4">Est. Arrival</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shipments as $shipment)
                    <tr class="hover:bg-blue-50 transition duration-150">
                        {{-- Ref No & Route --}}
                        <td class="px-6 py-4">
                            <div class="font-bold text-m2b-primary text-base">{{ $shipment->awb_number }}</div>
                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                <span>{{ $shipment->origin }}</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                <span>{{ $shipment->destination }}</span>
                            </div>
                        </td>

                        {{-- Service Info --}}
                        <td class="px-6 py-4">
                            <span class="capitalize font-medium text-gray-700">{{ $shipment->service_type }}</span>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $shipment->container_mode }} • {{ $shipment->shipment_type }}</div>
                        </td>

                        {{-- Cargo / HS Code --}}
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700">{{ $shipment->container_info ?: ($shipment->commodity ?: "-") }}</div>
                            @if($shipment->hs_code)
                                <div class="text-xs font-mono text-blue-600 mt-1">HS: {{ $shipment->hs_code }}</div>
                            @endif
                        </td>

                        {{-- Est Arrival --}}
                        <td class="px-6 py-4">
                            @if($shipment->estimated_arrival)
                                <div class="font-mono text-gray-600">{{ \Carbon\Carbon::parse($shipment->estimated_arrival)->format('d M Y') }}</div>
                            @else
                                <span class="text-gray-400 italic text-xs">TBA</span>
                            @endif
                        </td>

                        {{-- STATUS COLUMN (CLEAN VERSION) --}}
                        <td class="px-6 py-4 align-top">
                            <div class="flex flex-col items-start gap-2">
                                {{-- 1. Status Utama --}}
                                @php
                                    $st = strtolower($shipment->status);
                                    $lane = $shipment->lane_status;
                                @endphp

                                <span class="px-3 py-1 rounded-full text-xs font-bold border whitespace-nowrap
                                    @if($st == 'completed') bg-green-100 text-green-800 border-green-200
                                    @elseif($st == 'pending') bg-yellow-100 text-yellow-800 border-yellow-200
                                    @elseif($st == 'cancelled') bg-red-100 text-red-800 border-red-200
                                    @else bg-blue-100 text-blue-800 border-blue-200 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $shipment->status)) }}
                                </span>

                                {{-- 2. Status Jalur (Logic: Muncul jika TIDAK pending DAN data lane_status ada) --}}
                                @if($st !== 'pending' && !empty($lane))
                                    <div class="flex items-center gap-1.5 px-2 py-1 rounded border text-[10px] font-bold uppercase tracking-wider w-fit shadow-sm animate-pulse
                                        {{ $lane == 'green' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                                        {{-- Icon --}}
                                        <span class="text-xs">{{ $lane == 'green' ? '🟩' : '🟥' }}</span>
                                        {{-- Text --}}
                                        <span>{{ $lane == 'green' ? 'Jalur Hijau' : 'Jalur Merah' }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Action Button --}}
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('customer.shipment.show', $shipment->id) }}" class="inline-flex items-center gap-1 text-m2b-primary hover:text-blue-900 font-bold text-xs border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Track & Docs
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 bg-gray-50">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-base font-medium">No shipments found.</p>
                                <p class="text-xs mt-1 mb-4">You haven't created any bookings yet.</p>
                                <a href="{{ route('customer.shipments.create') }}" class="text-blue-600 hover:underline text-sm">Create your first booking</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $shipments->links() }}
        </div>
    </div>
</div>