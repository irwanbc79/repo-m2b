<div class="space-y-6">
    @section("header", "Manage Shipments")

    {{-- Toast Notifications --}}
    @if (session()->has("message"))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-2" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        {{ session("message") }}
        <button @click="show = false" class="ml-auto">&times;</button>
    </div>
    @endif

    @if (session()->has("error"))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-2" x-data="{ show: true }" x-show="show">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ session("error") }}
        <button @click="show = false" class="ml-auto">&times;</button>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        {{-- Total Card --}}
        <div wire:click="$set('filterStatus', '')" 
             title="{{ number_format($stats['total'] ?? 0) }} Total Shipment"
             class="cursor-pointer bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center hover:scale-[1.02] hover:shadow-md transition-all duration-200 {{ $filterStatus === '' ? 'ring-2 ring-gray-400 border-transparent' : '' }}">
            <p class="text-2xl font-black text-gray-800 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($stats["total"] ?? 0) }}</p>
            <p class="text-xs font-semibold text-gray-500 mt-0.5">Total</p>
        </div>

        {{-- Pending Card --}}
        <div wire:click="$set('filterStatus', 'pending')" 
             title="{{ number_format($stats['pending'] ?? 0) }} Shipment Menunggu (Klik untuk filter)"
             class="cursor-pointer bg-yellow-50 rounded-xl p-4 shadow-sm border border-yellow-100 text-center hover:scale-[1.02] hover:shadow-md transition-all duration-200 {{ $filterStatus === 'pending' ? 'ring-2 ring-yellow-400 border-transparent' : '' }}">
            <p class="text-2xl font-black text-yellow-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($stats["pending"] ?? 0) }}</p>
            <p class="text-xs font-semibold text-yellow-700 mt-0.5">Menunggu</p>
        </div>

        {{-- In Progress Card --}}
        <div wire:click="$set('filterStatus', 'in_progress')" 
             title="{{ number_format($stats['in_progress'] ?? 0) }} Shipment Dalam Proses (Klik untuk filter)"
             class="cursor-pointer bg-blue-50 rounded-xl p-4 shadow-sm border border-blue-100 text-center hover:scale-[1.02] hover:shadow-md transition-all duration-200 {{ $filterStatus === 'in_progress' ? 'ring-2 ring-blue-400 border-transparent' : '' }}">
            <p class="text-2xl font-black text-blue-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($stats["in_progress"] ?? 0) }}</p>
            <p class="text-xs font-semibold text-blue-700 mt-0.5">Proses</p>
        </div>

        {{-- In Transit Card --}}
        <div wire:click="$set('filterStatus', 'in_transit')" 
             title="{{ number_format($stats['in_transit'] ?? 0) }} Shipment Dalam Perjalanan (Klik untuk filter)"
             class="cursor-pointer bg-purple-50 rounded-xl p-4 shadow-sm border border-purple-100 text-center hover:scale-[1.02] hover:shadow-md transition-all duration-200 {{ $filterStatus === 'in_transit' ? 'ring-2 ring-purple-400 border-transparent' : '' }}">
            <p class="text-2xl font-black text-purple-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($stats["in_transit"] ?? 0) }}</p>
            <p class="text-xs font-semibold text-purple-700 mt-0.5">Perjalanan</p>
        </div>

        {{-- Completed Card --}}
        <div wire:click="$set('filterStatus', 'completed')" 
             title="{{ number_format($stats['completed'] ?? 0) }} Shipment Selesai (Klik untuk filter)"
             class="cursor-pointer bg-green-50 rounded-xl p-4 shadow-sm border border-green-100 text-center hover:scale-[1.02] hover:shadow-md transition-all duration-200 {{ $filterStatus === 'completed' ? 'ring-2 ring-green-400 border-transparent' : '' }}">
            <p class="text-2xl font-black text-green-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($stats["completed"] ?? 0) }}</p>
            <p class="text-xs font-semibold text-green-700 mt-0.5">Selesai</p>
        </div>

        {{-- This Month Card --}}
        <div title="{{ number_format($stats['this_month'] ?? 0) }} Shipment dibuat bulan ini"
             class="bg-indigo-50 rounded-xl p-4 shadow-sm border border-indigo-100 text-center">
            <p class="text-2xl font-black text-indigo-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($stats["this_month"] ?? 0) }}</p>
            <p class="text-xs font-semibold text-indigo-700 mt-0.5">Bulan Ini</p>
        </div>

        {{-- Total Ton Card --}}
        <div title="Total Berat: {{ number_format(($stats['total_weight'] ?? 0) / 1000, 2) }} Ton ({{ number_format($stats['total_weight'] ?? 0, 2) }} Kg)"
             class="bg-orange-50 rounded-xl p-4 shadow-sm border border-orange-100 text-center group relative">
            <p class="text-2xl font-black text-orange-600 tracking-tight flex items-baseline justify-center gap-0.5">
                <span>{{ \App\Support\NumberHelper::formatCompact(($stats["total_weight"] ?? 0) / 1000) }}</span>
            </p>
            <p class="text-xs font-semibold text-orange-700 mt-0.5">Total Ton</p>
        </div>

        {{-- Total CBM Card --}}
        <div title="Total Volume: {{ number_format($stats['total_volume'] ?? 0, 2) }} CBM"
             class="bg-teal-50 rounded-xl p-4 shadow-sm border border-teal-100 text-center group relative">
            <p class="text-2xl font-black text-teal-600 tracking-tight flex items-baseline justify-center gap-0.5">
                <span>{{ \App\Support\NumberHelper::formatCompact($stats["total_volume"] ?? 0) }}</span>
            </p>
            <p class="text-xs font-semibold text-teal-700 mt-0.5">Total CBM</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Header with Search & Filters --}}
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col gap-4">
                {{-- Search (full width, prominen) --}}
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari No. Shipment, referensi, atau nama customer..." class="w-full pl-11 pr-10 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    @if($search)
                    <button type="button" wire:click="$set('search','')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" title="Bersihkan pencarian">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    @endif
                </div>

                {{-- Filters + Actions

                     Enam penyaring memakan hampir 200px tinggi padahal jarang
                     diubah dalam satu sesi kerja. Sekarang dilipat, dan saat
                     terlipat penyaring yang sedang aktif tetap tampil sebagai
                     chip — supaya tidak ada yang bingung "kenapa datanya cuma
                     sedikit" gara-gara saringan tersembunyi. --}}
                @php
                    $filterAktif = collect([
                        'filterStatus' => ['Status', ['pending' => 'Pending', 'in_progress' => 'In Progress', 'in_transit' => 'In Transit', 'completed' => 'Completed', 'cancel' => 'Cancelled'][$filterStatus] ?? $filterStatus],
                        'filterShipmentType' => ['Moda', ['air' => 'Air', 'sea' => 'Sea', 'land' => 'Land'][$filterShipmentType] ?? $filterShipmentType],
                        'filterServiceType' => ['Layanan', ['import' => 'Import', 'export' => 'Export', 'domestic' => 'Domestic'][$filterServiceType] ?? $filterServiceType],
                        'filterLaneStatus' => ['Jalur', ['green' => 'Jalur Hijau', 'red' => 'Jalur Merah'][$filterLaneStatus] ?? $filterLaneStatus],
                        'filterCustomerData' => ['Data Customer', 'Perlu Dilengkapi'],
                    ])->filter(fn ($label, $prop) => filled($this->{$prop}));
                @endphp

                <div x-data="{ open: {{ $filterAktif->isNotEmpty() ? 'true' : 'false' }} }">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" @click="open = !open"
                            class="flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50"
                            :aria-expanded="open ? 'true' : 'false'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-1.447.894l-4-2A1 1 0 019 17v-4.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                            Filter
                            @if($filterAktif->isNotEmpty())
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-blue-600 text-white text-[10px] font-black flex items-center justify-center">{{ $filterAktif->count() }}</span>
                            @endif
                            <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        {{-- Chip penyaring aktif: hanya perlu saat panelnya terlipat. --}}
                        <div x-show="!open" class="flex flex-wrap items-center gap-2">
                            @foreach($filterAktif as $prop => $info)
                            <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-xs font-semibold text-blue-800">
                                <span class="text-blue-500 font-medium">{{ $info[0] }}:</span> {{ $info[1] }}
                                <button type="button" wire:click="$set('{{ $prop }}', '')" class="text-blue-400 hover:text-blue-700" aria-label="Hapus penyaring {{ $info[0] }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </span>
                            @endforeach
                        </div>

                        @if($filterAktif->isNotEmpty() || filled($search))
                        <button type="button" wire:click="resetFilters" class="text-xs font-semibold text-gray-500 hover:text-red-600 underline underline-offset-2">
                            Bersihkan
                        </button>
                        @endif

                        @if(count($selectedShipments ?? []) > 0)
                        <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-xl">
                            <span class="text-sm text-blue-700 font-medium">{{ count($selectedShipments) }} dipilih</span>
                            <select wire:model="bulkStatus" class="text-sm border-0 bg-transparent focus:ring-0 text-blue-700">
                                <option value="">Ubah Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="in_transit">In Transit</option>
                                <option value="completed">Completed</option>
                            </select>
                            <button wire:click="bulkUpdateStatus" class="text-xs bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700">Apply</button>
                        </div>
                        @endif

                        {{-- Tombol aksi (rata kanan) --}}
                        <div class="flex items-center gap-3 ml-auto">
                            <button wire:click="exportExcel" class="flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 text-sm font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                            </button>

                            @if(auth()->user()->hasPermission('shipment.create'))
                            <button wire:click="create" class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Shipment
                            </button>
                            @endif
                        </div>
                    </div>

                    <div x-show="open" x-cloak x-collapse class="flex flex-wrap items-center gap-3 pt-3">
                    <select wire:model.live="filterStatus" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="in_transit">In Transit</option>
                        <option value="completed">Completed</option>
                        <option value="cancel">Cancelled</option>
                    </select>

                    <select wire:model.live="filterShipmentType" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Shipment Type</option>
                        <option value="air">✈️ Air</option>
                        <option value="sea">🚢 Sea</option>
                        <option value="land">🚛 Land</option>
                    </select>


                    <select wire:model.live="filterServiceType" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Service Type</option>
                        <option value="import">📥 Import</option>
                        <option value="export">📤 Export</option>
                        <option value="domestic">🏠 Domestic</option>
                    </select>

                    <select wire:model.live="filterLaneStatus" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Semua Jalur</option>
                        <option value="green">🟩 Jalur Hijau</option>
                        <option value="red">🟥 Jalur Merah</option>
                    </select>

                    <select wire:model.live="filterCustomerData" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Semua Data Customer</option>
                        <option value="attention">⚠️ Customer Perlu Dilengkapi</option>
                    </select>

                    <select wire:model.live="perPage" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm" aria-label="Jumlah baris per halaman">
                        <option value="10">10 baris</option>
                        <option value="25">25 baris</option>
                        <option value="50">50 baris</option>
                    </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th wire:click="sortBy('created_at')" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase cursor-pointer hover:bg-gray-100">
                            <div class="flex items-center gap-1">Reference @if($sortField === 'created_at')<svg class="w-4 h-4 {{ $sortDirection === 'asc' ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>@endif</div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Route</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Cargo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">HS Code / Description</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Est. Tiba / Berangkat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Docs</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shipments as $shipment)
                    <tr class="hover:bg-gray-50 transition-colors {{ in_array($shipment->id, $selectedShipments ?? []) ? 'bg-blue-50' : '' }}" {{ $shipment->status === 'cancel' ? 'bg-gray-100 opacity-60' : '' }}>
                        <td class="px-4 py-3">
                            <input type="checkbox" wire:model.live="selectedShipments" value="{{ $shipment->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="px-4 py-3">
                            @php
                                // Awalan nomor (IMP/EXP/DOM) dipisah jadi badge berwarna:
                                // jenis pekerjaan langsung kebaca tanpa mengeja nomornya,
                                // dan sisa nomor tidak lagi pecah tiga baris.
                                $refPrefix = null;
                                $refRest = $shipment->awb_number ?: 'N/A';
                                if ($shipment->awb_number && str_contains($shipment->awb_number, '-')) {
                                    [$refPrefix, $refRest] = explode('-', $shipment->awb_number, 2);
                                    $refPrefix = strtoupper($refPrefix);
                                }
                                $refStyle = match ($refPrefix) {
                                    'IMP' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'EXP' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'DOM' => 'bg-violet-50 text-violet-700 border-violet-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <div class="flex items-baseline gap-1.5">
                                @if($refPrefix)
                                <span class="shrink-0 px-1.5 py-0.5 text-[10px] font-black rounded border tracking-wide {{ $refStyle }}">{{ $refPrefix }}</span>
                                @endif
                                <p class="font-semibold text-gray-800 whitespace-nowrap">{{ $refRest }}</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $shipment->created_at->format('d M Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <p class="font-medium text-gray-800">{{ $shipment->customer->company_name ?? 'N/A' }}</p>
                                @if($shipment->customer && ($dq = $shipment->customer->dataQuality())['level'] !== 'good')
                                <span title="Data customer perlu dilengkapi: {{ implode(' • ', $dq['issues']) }}"
                                    class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded-full border cursor-help shrink-0
                                    {{ $dq['level'] === 'bad' ? 'bg-red-100 text-red-700 border-red-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                                    {{ $dq['score'] }}%
                                </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500">{{ $shipment->customer->customer_code ?? '' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-lg {{ $shipment->shipment_type === 'air' ? 'bg-sky-100 text-sky-700' : ($shipment->shipment_type === 'sea' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">{{ strtoupper($shipment->shipment_type ?? 'N/A') }}</span>
                            <p class="text-xs text-gray-600 mt-1">{{ $shipment->origin ?? '-' }} → {{ $shipment->destination ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-800">{{ number_format($shipment->weight ?? 0, 0) }} Kg</p>
                            <p class="text-xs text-gray-500">{{ number_format($shipment->volume ?? 0, 3) }} CBM</p>
                            <p class="text-xs text-gray-400">{{ $shipment->pieces ?? 0 }} {{ $shipment->package_type ?? "pcs" }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-700 line-clamp-1">{{ $shipment->container_info ?: ($shipment->commodity ?: "-") }}</div>
                            @if($shipment->hs_code)
                                @php
                                    $hsInfo = \DB::table('hs_codes')->where('hs_code', $shipment->hs_code)->first();
                                @endphp
                                <div x-data="{ showTip: false }" class="relative inline-block">
                                    <div @mouseenter="showTip = true" @mouseleave="showTip = false" 
                                         class="text-xs font-mono text-blue-600 mt-1 cursor-help inline-flex items-center gap-1">
                                        <span>HS: {{ $shipment->hs_code }}</span>
                                        @if($hsInfo)
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        @endif
                                    </div>
                                    @if($hsInfo)
                                    <div x-show="showTip" x-cloak 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 transform scale-95"
                                         x-transition:enter-end="opacity-100 transform scale-100"
                                         class="absolute z-50 bottom-full left-0 mb-2 w-72 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-xl">
                                        <div class="font-bold text-yellow-300 mb-1">{{ $shipment->hs_code }}</div>
                                        <div class="mb-2">
                                            <div class="text-gray-300 text-[10px] uppercase tracking-wide">Indonesia:</div>
                                            <div class="text-white">{{ $hsInfo->description_id ?: '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-300 text-[10px] uppercase tracking-wide">English:</div>
                                            <div class="text-gray-200 italic">{{ $hsInfo->description_en ?: '-' }}</div>
                                        </div>
                                        @if($hsInfo->import_duty)
                                        <div class="mt-2 pt-2 border-t border-gray-700 flex gap-3">
                                            <span class="text-green-400">BM: {{ $hsInfo->import_duty }}{{ is_numeric($hsInfo->import_duty) ? '%' : '' }}</span>
                                            @if($hsInfo->export_duty && $hsInfo->export_duty != '-')
                                            <span class="text-orange-400">BK: {{ $hsInfo->export_duty }}</span>
                                            @endif
                                        </div>
                                        @endif
                                        <div class="absolute bottom-0 left-4 transform translate-y-full">
                                            <div class="border-8 border-transparent border-t-gray-900"></div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($shipment->estimated_arrival)
                                @php
                                    $etaBerjalan = !in_array($shipment->status, ['completed', 'cancel']);
                                    $etaIsLate = $shipment->estimated_arrival->isPast() && $etaBerjalan;
                                    // Dua hari ke depan = masih bisa dikejar; lewat dari itu
                                    // sudah jadi keluhan customer. Karena itu diberi warna
                                    // sendiri, bukan disamakan dengan tanggal yang masih jauh.
                                    $etaSegera = !$etaIsLate && $etaBerjalan
                                        && $shipment->estimated_arrival->lte(now()->addDays(2));
                                    $etaWarna = $etaIsLate ? 'text-red-600' : ($etaSegera ? 'text-amber-600' : 'text-gray-800');
                                    $etaRevisionCount = $shipment->etaRevisions->count();
                                    $latestEtaRevision = $shipment->etaRevisions->first();

                                    // Satu kolom dipakai dua arah: ekspor berangkat, sisanya tiba.
                                    $etaAdalahKeberangkatan = $shipment->service_type === 'export'
                                        || str_starts_with(strtoupper($shipment->awb_number ?? ''), 'EXP');
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <span class="shrink-0 px-1.5 py-0.5 rounded border text-[10px] font-black tracking-wide {{ $etaAdalahKeberangkatan ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}"
                                        title="{{ $etaAdalahKeberangkatan ? 'Estimasi tanggal berangkat' : 'Estimasi tanggal tiba' }}">
                                        {{ $etaAdalahKeberangkatan ? 'ETD' : 'ETA' }}
                                    </span>
                                    <p class="text-sm font-semibold {{ $etaWarna }}">
                                        {{ $shipment->estimated_arrival->format('d M Y') }}
                                    </p>
                                    @if($etaRevisionCount > 0)
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" @keydown.escape.window="open = false"
                                            class="relative inline-flex w-5 h-5 items-center justify-center rounded-full {{ ($latestEtaRevision->change_days ?? 0) > 3 ? 'bg-red-100 text-red-700' : (($latestEtaRevision->change_days ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700') }}"
                                            aria-label="Lihat {{ $etaRevisionCount }} riwayat revisi ETA"
                                            title="{{ $latestEtaRevision ? sprintf(
                                                '%s → %s (%+d hari) · %s. Klik untuk riwayat lengkap.',
                                                $latestEtaRevision->previous_eta?->format('d M Y') ?? 'Belum diisi',
                                                $latestEtaRevision->revised_eta->format('d M Y'),
                                                $latestEtaRevision->change_days,
                                                $latestEtaRevision->reason_label
                                            ) : 'Klik untuk riwayat revisi' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="absolute -top-2 -right-2 min-w-4 h-4 px-1 rounded-full bg-blue-900 text-white text-[9px] font-black flex items-center justify-center">{{ $etaRevisionCount }}</span>
                                        </button>
                                        <div x-show="open" x-cloak @click.away="open = false"
                                            class="absolute z-50 right-0 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-2xl whitespace-normal">
                                            <div class="flex items-center justify-between mb-3">
                                                <p class="text-xs font-black text-slate-800">Riwayat Revisi ETA</p>
                                                <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">{{ $etaRevisionCount }} revisi</span>
                                            </div>
                                            @foreach($shipment->etaRevisions->take(3) as $revision)
                                            <div class="border-l-2 {{ $revision->change_days > 0 ? 'border-amber-400' : 'border-blue-400' }} pl-3 py-1.5 mb-2 last:mb-0">
                                                <p class="text-xs font-bold text-slate-800">
                                                    {{ $revision->previous_eta?->format('d M Y') ?? 'Belum diisi' }}
                                                    <span class="text-slate-400 mx-1">→</span>
                                                    {{ $revision->revised_eta->format('d M Y') }}
                                                    <span class="{{ $revision->change_days > 0 ? 'text-amber-700' : 'text-blue-700' }}">
                                                        ({{ $revision->change_days > 0 ? '+' : '' }}{{ $revision->change_days }} hari)
                                                    </span>
                                                </p>
                                                <p class="text-[11px] text-slate-600 mt-0.5">{{ $revision->reason_label }}</p>
                                                <p class="text-[10px] text-slate-400 mt-0.5">
                                                    {{ $revision->source_party ?: 'Sumber tidak dicatat' }} ·
                                                    {{ $revision->creator?->name ?: 'Sistem' }} ·
                                                    {{ $revision->information_received_at->format('d M Y H:i') }}
                                                </p>
                                                @if($revision->reason_notes)
                                                <p class="text-[10px] text-slate-500 mt-1 italic">{{ \Illuminate\Support\Str::limit($revision->reason_notes, 120) }}</p>
                                                @endif
                                                <div class="flex items-center justify-between gap-2 mt-2">
                                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $revision->customer_visible ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                        {{ $revision->customer_visible ? ($revision->viewed_at ? 'Dilihat customer' : 'Tampil di customer') : 'Internal M2B' }}
                                                    </span>
                                                    @if(auth()->user()->hasPermission('shipment.edit'))
                                                    <button type="button" wire:click="openEtaPublicationModal({{ $revision->id }})"
                                                        class="text-[10px] font-bold text-blue-700 hover:text-blue-900 hover:underline">
                                                        Atur Publikasi
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                            @if($etaRevisionCount > 3)
                                            <p class="text-[10px] text-slate-400 mt-2">Menampilkan 3 revisi terbaru.</p>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    @if(auth()->user()->hasPermission('shipment.edit'))
                                    <button wire:click="openEtaRevisionModal({{ $shipment->id }})"
                                        class="text-[10px] font-bold text-blue-700 hover:text-blue-900 hover:underline"
                                        title="Catat perubahan estimasi tanggal tiba">
                                        Revisi
                                    </button>
                                    @endif
                                </div>
                                <p class="text-[11px] {{ $etaIsLate ? 'text-red-500 font-medium' : ($etaSegera ? 'text-amber-600 font-medium' : 'text-gray-400') }}">
                                    {{ $etaIsLate ? 'Lewat estimasi' : $shipment->estimated_arrival->diffForHumans() }}
                                </p>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $docCount = $shipment->documents->count() ?? 0; @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg {{ $docCount > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $docCount }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'document_collection' => 'bg-indigo-100 text-indigo-700', 'in_progress' => 'bg-blue-100 text-blue-700', 'in_transit' => 'bg-purple-100 text-purple-700', 'completed' => 'bg-green-100 text-green-700', 'cancel' => 'bg-red-100 text-red-700'];
                            @endphp
                            @php
                                // Label pendek supaya chip tidak pecah dua baris dan
                                // menaikkan tinggi seluruh baris tabel.
                                $statusLabels = [
                                    'pending' => 'Pending',
                                    'document_collection' => 'Dokumen',
                                    'in_progress' => 'In Progress',
                                    'in_transit' => 'In Transit',
                                    'completed' => 'Selesai',
                                    'cancel' => 'Batal',
                                ];
                            @endphp
                            <span class="inline-block whitespace-nowrap px-3 py-1 text-xs font-semibold rounded-full {{ $statusColors[$shipment->status] ?? 'bg-gray-100 text-gray-700' }}"
                                title="{{ ucfirst(str_replace('_', ' ', $shipment->status ?? 'N/A')) }}">{{ $statusLabels[$shipment->status] ?? ucfirst(str_replace('_', ' ', $shipment->status ?? 'N/A')) }}</span>
                            @php
                                $lane = $shipment->lane_status ?: $shipment->computeLaneStatusFromDocuments();
                            @endphp
                            @if($shipment->status !== 'pending' && $lane)
                            <div class="flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold mt-1 w-fit {{ $lane == 'green' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                <span>{{ $lane == 'green' ? '🟩' : '🟥' }}</span>
                                <span>{{ $lane == 'green' ? 'Jalur Hijau' : 'Jalur Merah' }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="relative flex items-center justify-center gap-1.5" x-data="{ menuOpen: false }">
                                <button wire:click="quickView({{ $shipment->id }})" class="px-2.5 py-1 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1 transition" title="Quick View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Lihat</span>
                                </button>
                                <button @click="menuOpen = !menuOpen" type="button" class="p-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-lg border border-gray-200 transition" title="Menu Aksi Lainnya">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                </button>
                                <div x-show="menuOpen" @click.away="menuOpen = false" x-cloak class="absolute right-0 top-full mt-1 flex items-center gap-1 bg-white rounded-xl shadow-xl border border-gray-100 z-50 p-1.5">
                                <a href="{{ url('/admin/shipments/' . $shipment->id) }}" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </a>
                                @if(auth()->user()->hasPermission('shipment.edit'))
                                <button wire:click="edit({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="openPrintDoModal({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg" title="Print DO">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('shipment.delete'))
                                <button wire:click="confirmDelete({{ $shipment->id }})" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endif
                            {{-- Tombol Cancel / View Cancel Reason --}}
                            @if($shipment->status === 'cancel')
                                {{-- Jika sudah cancel: tampilkan tombol view alasan --}}
                                <button wire:click="viewCancelReason({{ $shipment->id }})" 
                                    class="p-2 text-orange-500 hover:text-orange-700 hover:bg-orange-50 rounded-lg" 
                                    title="Lihat Alasan Pembatalan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                            @else
                                {{-- Jika belum cancel: tampilkan tombol batalkan --}}
                                <button wire:click="openCancelModal({{ $shipment->id }})" 
                                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" 
                                    title="Batalkan Shipment">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </button>
                            @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <p class="text-gray-500 font-medium">Tidak ada data shipment</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <p class="text-sm text-gray-600">Menampilkan {{ $shipments->firstItem() ?? 0 }} - {{ $shipments->lastItem() ?? 0 }} dari {{ $shipments->total() ?? 0 }} shipment</p>
                {{ $shipments->links() }}
            </div>
        </div>
    </div>

    {{-- Quick View Modal --}}
    @if($showQuickView && $quickViewShipment)
    @php
        $quickRevisions = $quickViewShipment->etaRevisions;
        $quickLatestRevision = $quickRevisions->first();
        $quickRequirements = $quickViewShipment->documentRequirements;
        $quickMandatory = $quickRequirements->where('is_mandatory', true)->where('status', '!=', 'waived');
        $quickFulfilled = $quickMandatory->where('status', 'fulfilled')->count();
        $quickRequiredTotal = $quickMandatory->count();
        $quickReadiness = $quickRequiredTotal > 0 ? (int) round(($quickFulfilled / $quickRequiredTotal) * 100) : 100;
        $quickNextDocument = $quickMandatory->first(fn ($item) => $item->status !== 'fulfilled');
        $quickEtaLate = $quickViewShipment->estimated_arrival
            && $quickViewShipment->estimated_arrival->isPast()
            && !in_array($quickViewShipment->status, ['completed', 'cancel']);
        $quickStatusLabels = [
            'pending' => 'Menunggu',
            'in_progress' => 'Diproses',
            'in_transit' => 'Dalam perjalanan',
            'completed' => 'Selesai',
            'cancel' => 'Dibatalkan',
        ];
    @endphp
    <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeQuickView">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[92vh] overflow-y-auto ring-1 ring-white/20">
            <div class="px-6 py-5 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white/95 backdrop-blur z-10">
                <div>
                    <p class="text-[10px] font-black tracking-[0.2em] text-blue-700 uppercase">Ringkasan operasional</p>
                    <h3 class="text-xl font-black text-slate-900 mt-0.5">{{ $quickViewShipment->awb_number ?? 'N/A' }}</h3>
                    <p class="text-xs text-slate-500">{{ $quickViewShipment->customer->company_name ?? 'Customer belum ditentukan' }}</p>
                </div>
                <button wire:click="closeQuickView" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg" aria-label="Tutup ringkasan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-5">
                {{-- Control strip: satu pandangan untuk ETA, dokumen, dan tindakan berikutnya --}}
                <div class="grid grid-cols-1 md:grid-cols-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-950 text-white">
                    <div class="p-4 border-b md:border-b-0 md:border-r border-white/10">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ETA terkini</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <p class="text-xl font-black {{ $quickEtaLate ? 'text-red-300' : 'text-white' }}">{{ $quickViewShipment->estimated_arrival?->format('d M Y') ?? 'Belum diisi' }}</p>
                            @if($quickRevisions->count())
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-400 text-slate-950">Revisi {{ $quickRevisions->count() }}×</span>
                            @endif
                        </div>
                        <p class="text-[11px] mt-1 {{ $quickEtaLate ? 'text-red-300' : 'text-slate-400' }}">
                            {{ $quickEtaLate ? 'Melewati estimasi' : ($quickLatestRevision ? (($quickLatestRevision->change_days > 0 ? '+' : '').$quickLatestRevision->change_days.' hari dari ETA sebelumnya') : 'Belum pernah direvisi') }}
                        </p>
                    </div>
                    <div class="p-4 border-b md:border-b-0 md:border-r border-white/10">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kesiapan dokumen</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <p class="text-xl font-black">{{ $quickFulfilled }}/{{ $quickRequiredTotal }}</p>
                            <span class="text-xs font-bold text-emerald-300">{{ $quickReadiness }}%</span>
                        </div>
                        <div class="h-1.5 bg-white/10 rounded-full mt-2 overflow-hidden"><div class="h-full bg-emerald-400 rounded-full" style="width: {{ $quickReadiness }}%"></div></div>
                    </div>
                    <div class="p-4 bg-blue-900/40">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-blue-200">Tindakan berikutnya</p>
                        <p class="text-sm font-bold mt-1 leading-snug">
                            {{ $quickNextDocument ? 'Lengkapi '.$quickNextDocument->doc_type : ($quickViewShipment->status === 'completed' ? 'Shipment telah selesai' : 'Pantau progres dan ETA') }}
                        </p>
                        <p class="text-[11px] text-blue-200 mt-1">{{ $quickNextDocument ? 'Dokumen wajib belum tersedia' : 'Tidak ada blocker dokumen wajib' }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @php $statusColors = ['pending' => 'bg-yellow-100 text-yellow-800', 'in_progress' => 'bg-blue-100 text-blue-800', 'in_transit' => 'bg-purple-100 text-purple-800', 'completed' => 'bg-green-100 text-green-800', 'cancel' => 'bg-red-100 text-red-800']; @endphp
                    <span class="px-3 py-1 text-xs font-black rounded-full {{ $statusColors[$quickViewShipment->status] ?? 'bg-gray-100 text-gray-700' }}">{{ $quickStatusLabels[$quickViewShipment->status] ?? ucfirst(str_replace('_', ' ', $quickViewShipment->status ?? 'N/A')) }}</span>
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-700">{{ strtoupper($quickViewShipment->shipment_type ?? 'N/A') }} · {{ strtoupper($quickViewShipment->container_mode ?? '-') }}</span>
                    @if($quickLatestRevision)
                        <span class="px-3 py-1 text-xs font-bold rounded-full {{ $quickLatestRevision->customer_visible ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $quickLatestRevision->customer_visible ? ($quickLatestRevision->viewed_at ? 'Sudah dilihat customer' : 'Dipublikasikan ke customer') : 'Revisi internal' }}
                        </span>
                    @endif
                </div>

                <div class="rounded-xl border border-slate-200 divide-y divide-slate-100">
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 p-4">
                        <div><p class="text-[10px] font-bold uppercase text-slate-400">Asal</p><p class="font-bold text-slate-900">{{ $quickViewShipment->origin ?? '-' }}</p></div>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-4-4 4 4-4 4"/></svg>
                        <div class="text-right"><p class="text-[10px] font-bold uppercase text-slate-400">Tujuan</p><p class="font-bold text-slate-900">{{ $quickViewShipment->destination ?? '-' }}</p></div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 text-sm">
                        <div><p class="text-[10px] font-bold uppercase text-slate-400">Muatan</p><p class="font-semibold text-slate-800">{{ \Illuminate\Support\Str::limit($quickViewShipment->container_info ?: ($quickViewShipment->commodity ?: '-'), 32) }}</p></div>
                        <div><p class="text-[10px] font-bold uppercase text-slate-400">Berat</p><p class="font-semibold text-slate-800">{{ number_format($quickViewShipment->weight ?? 0, 0) }} kg</p></div>
                        <div><p class="text-[10px] font-bold uppercase text-slate-400">Volume</p><p class="font-semibold text-slate-800">{{ number_format($quickViewShipment->volume ?? 0, 3) }} CBM</p></div>
                        <div><p class="text-[10px] font-bold uppercase text-slate-400">File</p><p class="font-semibold text-slate-800">{{ $quickViewShipment->documents->count() }} dokumen</p></div>
                    </div>
                </div>

                @if($quickLatestRevision)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Revisi ETA terbaru</p>
                            <p class="text-sm font-bold text-slate-900 mt-1">{{ $quickLatestRevision->previous_eta?->format('d M Y') ?? 'Belum diisi' }} → {{ $quickLatestRevision->revised_eta->format('d M Y') }} · {{ $quickLatestRevision->reason_label }}</p>
                            <p class="text-xs text-slate-600 mt-1">{{ $quickLatestRevision->source_party ?: 'Sumber belum dicatat' }}{{ $quickLatestRevision->reason_notes ? ' · '.\Illuminate\Support\Str::limit($quickLatestRevision->reason_notes, 120) : '' }}</p>
                        </div>
                        <span class="text-[10px] font-bold text-amber-800 whitespace-nowrap">{{ $quickLatestRevision->information_received_at->format('d M, H:i') }}</span>
                    </div>
                </div>
                @endif

                <div class="flex flex-col-reverse sm:flex-row gap-3 pt-1">
                    <a href="{{ route('admin.shipments.show', $quickViewShipment->id) }}" class="flex-1 py-2.5 bg-blue-700 text-white text-center rounded-xl font-bold hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Buka Detail Operasional</a>
                    @if(auth()->user()->hasPermission('shipment.edit'))
                    <button wire:click="reviseEtaFromQuickView({{ $quickViewShipment->id }})" class="px-5 py-2.5 border border-amber-300 bg-amber-50 text-amber-800 rounded-xl font-bold hover:bg-amber-100">Revisi ETA</button>
                    <button wire:click="edit({{ $quickViewShipment->id }})" class="px-5 py-2.5 border border-slate-300 text-slate-700 rounded-xl font-bold hover:bg-slate-50">Edit Data</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Shipment?</h3>
            <p class="text-gray-600 mb-6">Data shipment dan dokumen terkait akan dihapus permanen.</p>
            <div class="flex gap-3 justify-center">
                <button wire:click="cancelDelete" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-semibold">Batal</button>
                <button wire:click="deleteShipment" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm overflow-y-auto">
        <div style="position: relative; z-index: 10;" class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[92vh] overflow-hidden flex flex-col animate-fade-in-up border-t-4 border-blue-900">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-slate-50 to-blue-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-900 text-white flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 8l-8-4-8 4m16 0l-8 4m8-4v8l-8 4m0-8L4 8m8 4v8M4 8v8l8 4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">{{ $isEditing ? 'Edit Shipment' : 'Buat Shipment Baru' }}</h3>
                        <p class="text-xs text-slate-500">Lengkapi rute, layanan, kargo, dan estimasi tiba.</p>
                    </div>
                </div>
                <button wire:click="closeModal" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white transition" title="Tutup"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            
            <div class="p-6 overflow-y-auto bg-slate-50/50">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Left Column --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                            <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-black">01</span>
                            <div><h4 class="text-sm font-black text-slate-800">Rute & Layanan</h4><p class="text-[11px] text-slate-500">Identitas job dan arah pergerakan barang</p></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Nomor Shipment <span class="font-normal text-slate-400">(opsional)</span></label>
                            <input type="text" wire:model="form.awb_number" class="w-full border-slate-300 rounded-xl text-sm bg-slate-50" placeholder="Otomatis dibuat jika dikosongkan">
                            @error('form.awb_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Customer <span class="text-red-500">*</span></label>
                            <!-- Searchable Dropdown with Alpine.js -->
                            <div x-data="{
                                open: false,
                                search: '',
                                selected: @entangle('form.customer_id'),
                                selectedName: '',
                                customers: {{ Js::from($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->company_name])) }},
                                get filteredCustomers() {
                                    if (!this.search) return this.customers;
                                    return this.customers.filter(c => 
                                        c.name.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                selectCustomer(customer) {
                                    this.selected = customer.id;
                                    this.selectedName = customer.name;
                                    this.search = '';
                                    this.open = false;
                                },
                                init() {
                                    if (this.selected) {
                                        const found = this.customers.find(c => c.id == this.selected);
                                        if (found) this.selectedName = found.name;
                                    }
                                    this.$watch('selected', (value) => {
                                        const found = this.customers.find(c => c.id == value);
                                        this.selectedName = found ? found.name : '';
                                    });
                                }
                            }" class="relative">
                                <!-- Display Selected / Search Input -->
                                <div @click="open = !open" 
                                     class="w-full border border-slate-300 rounded-lg text-sm bg-white cursor-pointer flex items-center justify-between px-3 py-2 hover:border-blue-400 transition">
                                    <span x-text="selectedName || 'Pilih customer'" :class="selectedName ? 'text-slate-800' : 'text-slate-400'"></span>
                                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                                
                                <!-- Dropdown Panel -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     @click.away="open = false"
                                     class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-hidden">
                                    
                                    <!-- Search Input -->
                                    <div class="p-2 border-b border-slate-100 sticky top-0 bg-white">
                                        <div class="relative">
                                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                            <input x-model="search" 
                                                   @click.stop
                                                   type="text" 
                                                   placeholder="🔍 Ketik untuk mencari..." 
                                                   class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   autofocus>
                                        </div>
                                    </div>
                                    
                                    <!-- Options List -->
                                    <div class="overflow-y-auto max-h-48">
                                        <template x-for="customer in filteredCustomers" :key="customer.id">
                                            <div @click="selectCustomer(customer)"
                                                 :class="selected == customer.id ? 'bg-blue-50 text-blue-700' : 'hover:bg-slate-50'"
                                                 class="px-3 py-2 cursor-pointer text-sm flex items-center gap-2 transition">
                                                <span x-show="selected == customer.id" class="text-blue-500">✓</span>
                                                <span x-text="customer.name"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredCustomers.length === 0" class="px-3 py-4 text-sm text-slate-400 text-center">
                                            Tidak ada customer yang cocok
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden input for Livewire -->
                                <input type="hidden" x-model="selected">
                            </div>
                            @error('form.customer_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-blue-800">
                            <span>Asal</span><span class="flex-1 h-px bg-blue-100"></span><span>→</span><span class="flex-1 h-px bg-blue-100"></span><span>Tujuan</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-600 mb-1">Origin <span class="text-red-500">*</span></label><input type="text" wire:model="form.origin" class="w-full border-slate-300 rounded-xl text-sm" placeholder="Shanghai, China">@error('form.origin') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror</div>
                            <div><label class="block text-xs font-bold text-slate-600 mb-1">Destination <span class="text-red-500">*</span></label><input type="text" wire:model="form.destination" class="w-full border-slate-300 rounded-xl text-sm" placeholder="Belawan, Indonesia">@error('form.destination') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Jenis Layanan <span class="text-red-500">*</span></label>
                                <select wire:model="form.service_type" class="w-full border-slate-300 rounded-xl text-sm">
                                    <option value="import">Import</option>
                                    <option value="export">Export</option>
                                    <option value="domestic">Domestic</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Moda Transportasi</label>
                                <select wire:model="form.shipment_type" class="w-full border-slate-300 rounded-xl text-sm">
                                    <option value="sea">🚢 Sea</option>
                                    <option value="air">✈️ Air</option>
                                    <option value="land">🚛 Land</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-slate-600 block mb-1">Est. Tgl Tiba</label>
                                <input type="date" wire:model="form.estimated_arrival" class="w-full border-slate-300 rounded-xl text-sm bg-white">
                                @error('form.estimated_arrival') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600 block mb-1">Status Jalur</label>
                                <select wire:model="form.lane_status" class="w-full border-slate-300 rounded-xl text-sm">
                                    <option value="">Otomatis / Belum ada</option>
                                    <option value="green">🟩 Jalur Hijau</option>
                                    <option value="red">🟥 Jalur Merah</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-600 block mb-1">Catatan Operasional</label>
                            <textarea wire:model="form.notes" rows="2" class="w-full border-slate-300 rounded-xl text-sm resize-none" placeholder="Instruksi khusus, PIC, atau informasi penting shipment"></textarea>
                        </div>

                        @if($isEditing)
                        <div class="flex items-center gap-3 bg-green-50 p-3 rounded-xl border border-green-200">
                            <input type="checkbox" wire:model="mark_as_completed" id="markCompleted" class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                            <label for="markCompleted" class="text-sm font-bold text-green-800 cursor-pointer select-none">Tandai shipment selesai</label>
                        </div>
                        @endif
                    </div>

                    {{-- Right Column (Cargo) --}}
                    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                            <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-black">02</span>
                            <div><h4 class="text-sm font-black text-slate-800">Detail Kargo</h4><p class="text-[11px] text-slate-500">Kemasan, ukuran, dan klasifikasi barang</p></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="text-xs font-bold text-slate-500 block mb-1">Mode</label><select wire:model="form.container_mode" class="w-full border-slate-300 rounded-lg text-sm"><option value="LCL">LCL</option><option value="FCL">FCL</option></select></div>
                            <div><label class="text-xs font-bold text-slate-500 block mb-1">Details</label><input type="text" wire:model="form.container_info" class="w-full border-slate-300 rounded-lg text-sm"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="text-xs font-bold text-slate-500 block mb-1">Qty</label><input type="number" wire:model="form.pieces" class="w-full border-slate-300 rounded-lg text-sm"></div>
                            <div>
                                <label class="text-xs font-bold text-slate-500 block mb-1">Jenis Kemasan</label>
                                <select wire:model="form.package_type" class="w-full border-slate-300 rounded-lg text-sm">
                                    <option value="">-- Pilih Jenis Kemasan --</option>
                                    <optgroup label="📦 Packaging">
                                        <option value="Ctn">Ctn - Cartons</option>
                                        <option value="Box">Box - Kotak</option>
                                        <option value="Pkgs">Pkgs - Packages</option>
                                        <option value="Plt">Plt - Pallet</option>
                                        <option value="Crate">Crate - Krat</option>
                                        <option value="Case">Case - Peti</option>
                                        <option value="Skid">Skid - Alas Kayu</option>
                                    </optgroup>
                                    <optgroup label="🔗 Bundle/Gulungan">
                                        <option value="Bdl">Bdl - Bundle</option>
                                        <option value="Bale">Bale - Bal</option>
                                        <option value="Coil">Coil - Gulungan</option>
                                        <option value="Roll">Roll - Roll</option>
                                        <option value="Reel">Reel - Kumparan</option>
                                    </optgroup>
                                    <optgroup label="🔢 Satuan">
                                        <option value="Pcs">Pcs - Pieces</option>
                                        <option value="Unit">Unit - Unit</option>
                                        <option value="Set">Set - Set</option>
                                        <option value="Pair">Pair - Pasang</option>
                                        <option value="Dozen">Dozen - Lusin</option>
                                        <option value="Ea">Ea - Each</option>
                                    </optgroup>
                                    <optgroup label="🛢️ Wadah/Container">
                                        <option value="Bag">Bag - Tas</option>
                                        <option value="Sack">Sack - Karung</option>
                                        <option value="Drum">Drum - Drum</option>
                                        <option value="Barrel">Barrel - Barel</option>
                                        <option value="IBC">IBC - IBC Tank</option>
                                        <option value="Jerrycan">Jerrycan - Jerigen</option>
                                        <option value="Bottle">Bottle - Botol</option>
                                        <option value="Can">Can - Kaleng</option>
                                        <option value="Cylinder">Cylinder - Tabung Gas</option>
                                        <option value="Tubes">Tubes - Tabung</option>
                                        <option value="Tote">Tote - Tote Bag</option>
                                    </optgroup>
                                    <optgroup label="⚖️ Berat">
                                        <option value="Kg">Kg - Kilogram</option>
                                        <option value="Ton">Ton - Metric Ton</option>
                                        <option value="MT">MT - Metric Ton</option>
                                        <option value="Lbs">Lbs - Pounds</option>
                                        <option value="Gram">Gram - Gram</option>
                                    </optgroup>
                                    <optgroup label="📐 Volume">
                                        <option value="M3">M3 - Cubic Meter</option>
                                        <option value="CBM">CBM - Cubic Meter</option>
                                        <option value="Ltr">Ltr - Liter</option>
                                        <option value="Gal">Gal - Gallon</option>
                                        <option value="CFT">CFT - Cubic Feet</option>
                                    </optgroup>
                                    <optgroup label="📏 Panjang/Luas">
                                        <option value="Mtr">Mtr - Meter</option>
                                        <option value="Ft">Ft - Feet</option>
                                        <option value="Yard">Yard - Yard</option>
                                        <option value="SQM">SQM - Square Meter</option>
                                        <option value="SQF">SQF - Square Feet</option>
                                    </optgroup>
                                    <optgroup label="🚢 Logistik">
                                        <option value="TEU">TEU - 20ft Container</option>
                                        <option value="FEU">FEU - 40ft Container</option>
                                        <option value="Lot">Lot - Lot</option>
                                        <option value="Shipment">Shipment - Pengiriman</option>
                                    </optgroup>
                                    <optgroup label="📋 Lainnya">
                                        <option value="Other">Other - Lainnya</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div><label class="text-xs font-bold text-slate-500 block mb-1">Weight (Kg)</label><input type="number" wire:model="form.weight" class="w-full border-slate-300 rounded-lg text-sm"></div>
                        <div><label class="text-xs font-bold text-slate-500 block mb-1">Volume (CBM)</label><input type="number" step="0.001" wire:model="form.volume" class="w-full border-slate-300 rounded-lg text-sm" placeholder="0.000"></div>
                        <div>
                            <label class="text-xs font-bold text-slate-500 block mb-1">Commodity / Uraian Barang</label>
                            <input type="text" wire:model="form.commodity" class="w-full border-slate-300 rounded-lg text-sm" placeholder="Contoh: Spare parts mesin">
                        </div>
                        <div x-data="hsCodeAutocomplete()" class="relative">
                            <label class="text-xs font-bold text-slate-500 block mb-1">HS Code</label>
                            <input 
                                type="text" 
                                x-model="search"
                                @input.debounce.300ms="fetchResults"
                                @focus="showDropdown = true"
                                @click.away="showDropdown = false"
                                wire:model="form.hs_code" 
                                class="w-full border-slate-300 rounded-lg text-sm font-mono" 
                                placeholder="Ketik HS Code atau deskripsi..." 
                                maxlength="12"
                                autocomplete="off"
                            >
                            <!-- Dropdown Results -->
                            <div x-show="showDropdown && results.length > 0" 
                                 x-cloak
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <template x-for="item in results" :key="item.hs_code">
                                    <div @click="selectItem(item)" 
                                         class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm font-bold text-blue-600" x-text="item.hs_code"></span>
                                            <span class="text-xs px-1.5 py-0.5 rounded" 
                                                  :class="item.hs_level == 4 ? 'bg-green-100 text-green-700' : (item.hs_level == 6 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')"
                                                  x-text="item.hs_level + ' digit'"></span>
                                        </div>
                                        <p class="text-xs text-gray-600 mt-0.5 line-clamp-2" x-text="item.description_id"></p>
                                    </div>
                                </template>
                            </div>
                            <!-- Selected Description -->
                            <p class="text-xs text-gray-400 mt-1" x-show="!selectedDesc">Ketik minimal 2 karakter untuk mencari</p>
                            <p class="text-xs text-green-600 mt-1" x-show="selectedDesc" x-text="selectedDesc"></p>
                        </div>
                        
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button wire:click="closeModal" class="px-4 py-2 bg-white border border-slate-300 rounded-xl text-slate-600 font-semibold hover:bg-slate-50">Batal</button>
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="px-6 py-2 bg-blue-900 text-white rounded-xl text-sm font-bold hover:bg-blue-800 disabled:opacity-60 transition shadow-md flex items-center gap-2">
                    <span wire:loading.remove wire:target="save">{{ $isEditing ? 'Simpan Perubahan' : 'Buat Shipment' }}</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ETA REVISION MODAL --}}
    @if($showEtaRevisionModal && $etaRevisionShipment)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm overflow-y-auto">
        <div style="position: relative; z-index: 10;" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-hidden flex flex-col border-t-4 border-blue-900">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-slate-50 to-blue-50">
                <div>
                    <h3 class="text-lg font-black text-slate-900">Revisi ETA Shipment</h3>
                    <p class="text-xs text-slate-500">{{ $etaRevisionShipment->awb_number }} · {{ $etaRevisionShipment->customer?->company_name }}</p>
                </div>
                <button wire:click="closeEtaRevisionModal" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-white" title="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-5">
                <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ETA Saat Ini</p>
                        <p class="text-lg font-black text-slate-800">{{ $etaRevisionShipment->estimated_arrival?->format('d M Y') ?? 'Belum diisi' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Jumlah Revisi</p>
                        <p class="text-lg font-black text-blue-900">{{ $etaRevisionShipment->etaRevisions->count() }} kali</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">ETA Terbaru <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="etaRevisedDate" class="w-full border-slate-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('etaRevisedDate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Informasi Diterima <span class="text-red-500">*</span></label>
                        <input type="datetime-local" wire:model="etaInformationReceivedAt" class="w-full border-slate-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500">
                        @error('etaInformationReceivedAt') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Alasan Revisi <span class="text-red-500">*</span></label>
                    <select wire:model="etaReasonCode" class="w-full border-slate-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Pilih alasan revisi</option>
                        @foreach($etaReasonOptions as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('etaReasonCode') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Sumber Informasi</label>
                    <input type="text" wire:model="etaSourceParty" class="w-full border-slate-300 rounded-xl text-sm" placeholder="Contoh: Shipping Line Maersk / Vendor ABC">
                    @error('etaSourceParty') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Keterangan Tambahan</label>
                    <textarea wire:model="etaReasonNotes" rows="3" class="w-full border-slate-300 rounded-xl text-sm resize-none" placeholder="Nomor voyage, alasan operasional, atau tindak lanjut yang diperlukan"></textarea>
                    @error('etaReasonNotes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Bukti dari Shipping/Vendor <span class="font-normal text-slate-400">(opsional, maks. 5 MB)</span></label>
                    <input type="file" wire:model="etaEvidence" accept=".pdf,.jpg,.jpeg,.png,.webp" class="w-full text-sm border border-slate-300 rounded-xl p-2 bg-white">
                    <div wire:loading wire:target="etaEvidence" class="text-xs text-blue-600 mt-1">Mengunggah bukti...</div>
                    @error('etaEvidence') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div x-data="{ publish: @entangle('etaCustomerVisible') }" class="space-y-3">
                    <label class="flex items-start gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100 cursor-pointer">
                        <input type="checkbox" wire:model="etaCustomerVisible" x-model="publish" class="mt-0.5 rounded border-blue-300 text-blue-700 focus:ring-blue-500">
                        <span>
                            <span class="block text-sm font-bold text-blue-900">Publikasikan pembaruan ke Customer</span>
                            <span class="block text-xs text-blue-700">Customer akan melihat tanggal baru, alasan, dan pesan pada portal.</span>
                        </span>
                    </label>
                    <div x-show="publish" x-cloak>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Pesan untuk Customer <span class="text-red-500">*</span></label>
                        <textarea wire:model="etaCustomerMessage" rows="3" class="w-full border-blue-200 rounded-xl text-sm resize-none bg-blue-50/40" placeholder="Contoh: Estimasi tiba diperbarui berdasarkan perubahan jadwal dari shipping line. Tim M2B terus memantau pengiriman."></textarea>
                        @error('etaCustomerMessage') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" wire:model="etaEvidenceCustomerVisible" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500" @if(!$etaEvidence) disabled @endif>
                    <span>
                        <span class="block text-sm font-bold text-slate-800">Bagikan dokumen bukti kepada customer</span>
                        <span class="block text-xs text-slate-500">Terpisah dari publikasi revisi. Default evidence tetap internal M2B.</span>
                    </span>
                </label>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    ETA lama tetap tersimpan sebagai histori. Setelah disimpan, ETA aktif shipment akan diperbarui dan tidak dapat dihapus dari modal ini.
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button wire:click="closeEtaRevisionModal" class="px-4 py-2 bg-white border border-slate-300 rounded-xl text-slate-600 font-semibold hover:bg-slate-100">Batal</button>
                <button wire:click="saveEtaRevision" wire:loading.attr="disabled" class="px-5 py-2 bg-blue-900 text-white rounded-xl text-sm font-bold hover:bg-blue-800 disabled:opacity-60 shadow-md">
                    <span wire:loading.remove wire:target="saveEtaRevision">Simpan Revisi ETA</span>
                    <span wire:loading wire:target="saveEtaRevision">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ETA PUBLICATION MODAL: hanya visibilitas customer, histori tetap terkunci --}}
    @if($showEtaPublicationModal && $etaPublicationRevision)
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
        <div style="position: relative; z-index: 10;" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border-t-4 border-emerald-600">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black tracking-[0.16em] text-emerald-700 uppercase">Visibilitas customer</p>
                    <h3 class="text-lg font-black text-slate-900">Atur Publikasi Revisi ETA</h3>
                    <p class="text-xs text-slate-500">{{ $etaPublicationRevision->shipment?->awb_number }} · {{ $etaPublicationRevision->shipment?->customer?->company_name }}</p>
                </div>
                <button wire:click="closeEtaPublicationModal" class="p-2 rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Tutup">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                    <p class="text-xs font-black text-slate-900">
                        {{ $etaPublicationRevision->previous_eta?->format('d M Y') ?? 'Belum diisi' }}
                        <span class="text-slate-400 mx-1">→</span>
                        {{ $etaPublicationRevision->revised_eta->format('d M Y') }}
                    </p>
                    <p class="text-xs text-slate-600 mt-1">{{ $etaPublicationRevision->reason_label }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Tanggal, alasan, dan sumber dikunci untuk menjaga audit trail.</p>
                </div>

                <div x-data="{ publish: @entangle('publicationCustomerVisible') }" class="space-y-3">
                    <label class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 cursor-pointer">
                        <input type="checkbox" wire:model="publicationCustomerVisible" x-model="publish" class="mt-0.5 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-500">
                        <span>
                            <span class="block text-sm font-bold text-emerald-900">Tampilkan revisi ini kepada customer</span>
                            <span class="block text-xs text-emerald-700">Hanya customer pemilik shipment yang dapat melihat pembaruan.</span>
                        </span>
                    </label>

                    <div x-show="publish" x-cloak>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <label class="block text-xs font-bold text-slate-600">Pesan untuk Customer <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="applyEtaPublicationTemplate" class="text-[10px] font-bold text-blue-700 hover:text-blue-900 hover:underline">Gunakan Template</button>
                                <button type="button" wire:click="resetEtaPublicationMessage" class="text-[10px] font-bold text-slate-400 hover:text-red-600 hover:underline">Kosongkan</button>
                            </div>
                        </div>
                        <textarea wire:model="publicationCustomerMessage" rows="3" class="w-full border-emerald-200 rounded-xl text-sm resize-none bg-emerald-50/30" placeholder="Jelaskan perubahan jadwal dan tindak lanjut M2B."></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">Template mengikuti alasan revisi dan tetap dapat diedit sebelum dipublikasikan.</p>
                        @error('publicationCustomerMessage') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($etaPublicationRevision->sourceDocument)
                <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" wire:model="publicationEvidenceVisible" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    <span>
                        <span class="block text-sm font-bold text-slate-800">Bagikan bukti kepada customer</span>
                        <span class="block text-xs text-slate-500">{{ $etaPublicationRevision->sourceDocument->filename }}</span>
                    </span>
                </label>
                @error('publicationEvidenceVisible') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                @endif

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    Menonaktifkan publikasi akan menyembunyikan revisi dari portal customer tanpa menghapus histori internal.
                </div>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end gap-3">
                <button wire:click="closeEtaPublicationModal" class="px-4 py-2 bg-white border border-slate-300 rounded-xl text-slate-600 font-bold">Batal</button>
                <button wire:click="saveEtaPublication" wire:loading.attr="disabled" class="px-5 py-2 bg-emerald-700 text-white rounded-xl text-sm font-bold hover:bg-emerald-800 disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveEtaPublication">Simpan Publikasi</span>
                    <span wire:loading wire:target="saveEtaPublication">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    
    {{-- UPLOAD EVIDENCE MODAL (Dibiarkan sama) --}}
    @if($uploadingShipmentId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-xl w-full max-w-md shadow-2xl animate-fade-in-up border-t-4 border-purple-600">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="font-bold text-lg text-purple-900">Upload Internal Evidence </h3>
                <button wire:click="$set('uploadingShipmentId', null)" class="text-gray-400 hover:text-red-500">&times;</button>
            </div>
            <div class="space-y-4">
                <div class="border-2 border-dashed border-purple-200 rounded-lg p-4 bg-purple-50 text-center relative hover:bg-purple-50 transition">
                    <input type="file" wire:model="internal_photo" class="w-full text-xs text-slate-500 mx-auto">
                    <p class="text-xs text-gray-500 mt-2">JPG, PNG, PDF (Max 10MB)</p>
                </div>
                <div><input type="text" wire:model="internal_note" placeholder="Keterangan..." class="w-full border-purple-200 rounded-lg px-3 py-2 text-sm"></div>
            </div>
            <div class="mt-6 flex justify-end gap-3 pt-4 border-t">
                <button wire:click="$set('uploadingShipmentId', null)" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 text-sm hover:bg-gray-50 transition">Batal</button>
                <button wire:click="uploadInternalEvidence" class="px-4 py-2 bg-purple-700 text-white rounded-lg text-sm font-bold hover:bg-purple-800 transition shadow-md flex items-center disabled:opacity-50 disabled:cursor-not-allowed" @if(!$internal_photo) disabled @endif>
                    <span wire:loading.remove wire:target="uploadInternalEvidence">Upload</span>
                    <span wire:loading wire:target="uploadInternalEvidence">Uploading...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL CETAK SURAT JALAN --}}
    @if($showPrintDoModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl border-t-4 border-orange-500 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center px-6 py-4 border-b bg-orange-50">
                <div>
                    <h3 class="font-bold text-lg text-orange-900">Cetak Surat Jalan / DO</h3>
                    @if($printDoShipment)
                    <p class="text-sm text-gray-600">{{ $printDoShipment->awb_number }} - {{ $printDoShipment->customer->company_name ?? '-' }}</p>
                    @endif
                </div>
                <button wire:click="closePrintDoModal" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <label class="block text-sm font-bold text-blue-800 mb-2">Jumlah Container / Kendaraan</label>
                    <div class="flex items-center gap-3">
                        <input type="number" wire:model.live.debounce.500ms="printDoContainerCount"
                               min="1" max="100"
                               class="w-24 border-blue-300 rounded-lg text-lg font-bold text-center focus:border-blue-500 focus:ring-blue-500"
                               placeholder="1">
                        <span class="text-sm text-blue-800">Surat Jalan</span>
                    </div>
                    <p class="text-xs text-blue-600 mt-2 text-center">Akan mencetak <strong>{{ $printDoContainerCount }}</strong> lembar surat jalan</p>
                </div>

                {{-- Penerima & Alamat Bongkar --}}
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg space-y-3">
                    <div>
                        <label class="block text-sm font-bold text-green-800 mb-1">
                            Nama Penerima Barang
                            <span class="font-normal text-green-600 ml-1">(opsional — isi jika berbeda dari importir)</span>
                        </label>
                        @if($printDoShipment)
                        <p class="text-xs text-green-600 mb-1">
                            Default: <strong>{{ $printDoShipment->customer->company_name ?? '-' }}</strong>
                        </p>
                        @endif
                        <input type="text" wire:model="printDoNamaPenerima"
                               class="w-full border-green-300 rounded-lg text-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="Nama perusahaan / penerima fisik barang...">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-green-800 mb-1">
                            Alamat Bongkar
                        </label>
                        <p class="text-xs text-green-600 mb-1">Pre-filled dari data customer. Edit jika lokasi bongkar berbeda.</p>
                        <textarea wire:model="printDoAlamatBongkar"
                                  rows="2"
                                  class="w-full border-green-300 rounded-lg text-sm focus:border-green-500 focus:ring-green-500"
                                  placeholder="Masukkan alamat lengkap lokasi bongkar..."></textarea>
                    </div>
                </div>
                
                <div class="space-y-4">
                    @foreach($printDoContainers as $index => $container)
                    <div class="p-4 border-2 border-orange-200 rounded-xl bg-orange-50/50">
                        <h4 class="font-bold text-sm text-orange-800 mb-4 flex items-center gap-2">
                            <span class="bg-orange-500 text-white w-7 h-7 rounded-full flex items-center justify-center text-sm">{{ $index + 1 }}</span>
                            Surat Jalan ke-{{ $index + 1 }}
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Container</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.no_container" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="TINU1234567 (opsional)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Polisi Truk</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.no_polisi" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="BK 1234 XX (opsional)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nama Supir</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.nama_supir" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="Nama lengkap (opsional)">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">No. Segel / Seal</label>
                                <input type="text" wire:model="printDoContainers.{{ $index }}.no_seal" 
                                       class="w-full border-gray-300 rounded-lg text-sm focus:border-orange-500 focus:ring-orange-500" 
                                       placeholder="SEAL123456 (opsional)">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-100 border-t flex justify-between items-center">
                <p class="text-xs text-gray-500">* Semua field opsional, bisa dikosongkan</p>
                <div class="flex gap-3">
                    <button wire:click="closePrintDoModal" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 font-medium hover:bg-white">Batal</button>
                    <button wire:click="printDo" class="px-6 py-2 bg-orange-600 text-white rounded-lg font-bold hover:bg-orange-700 transition shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak {{ $printDoContainerCount }} Surat Jalan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <script>
    function hsCodeAutocomplete() {
        return {
            search: "",
            results: [],
            showDropdown: false,
            selectedDesc: "",
            async fetchResults() {
                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }
                try {
                    const response = await fetch(`/api/hs-codes/search?q=${encodeURIComponent(this.search)}`);
                    this.results = await response.json();
                } catch (e) {
                    this.results = [];
                }
            },
            selectItem(item) {
                this.search = item.hs_code;
                this.selectedDesc = item.description_id;
                this.showDropdown = false;
                this.$wire.set("form.hs_code", item.hs_code);
            }
        }
    }
    </script>
    @script
    <script>
        $wire.on('openPrintWindow', (event) => {
            window.dispatchEvent(new CustomEvent('open-print-preview', { detail: { url: event.url } }));
        });
    </script>
    @endscript

    {{-- Cancel Shipment Modal --}}
    @if($showCancelModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-cancel-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeCancelModal" 
                 aria-hidden="true"></div>

            {{-- Center modal --}}
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Modal panel --}}
            <div style="position: relative; z-index: 10;" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-cancel-title">
                                🚫 Batalkan Shipment
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Apakah Anda yakin ingin membatalkan shipment ini? 
                                    Shipment yang dibatalkan tidak dapat diedit lagi.
                                </p>
                                
                                <div class="mt-4">
                                    <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                        Alasan Pembatalan <span class="text-gray-400">(opsional)</span>
                                    </label>
                                    <textarea 
                                        wire:model="cancellationReason" 
                                        id="cancellation_reason"
                                        rows="3" 
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                        placeholder="Contoh: Permintaan customer, kesalahan input, dll..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button 
                        wire:click="confirmCancel" 
                        type="button" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                        Ya, Batalkan Shipment
                    </button>
                    <button 
                        wire:click="closeCancelModal" 
                        type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

{{-- MODAL VIEW CANCEL REASON --}}
@if($showCancelReasonModal && $cancelReasonShipment)
<div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="closeCancelReasonModal"></div>
        
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Shipment Dibatalkan
                    </h3>
                    <button wire:click="closeCancelReasonModal" class="text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            {{-- Body --}}
            <div class="p-6 space-y-4">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">No. Referensi</p>
                    <p class="text-lg font-bold text-gray-800">{{ $cancelReasonShipment->awb_number }}</p>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Dibatalkan Oleh</p>
                        <p class="text-sm font-medium text-gray-800">{{ $cancelReasonShipment->cancelledBy->name ?? 'Unknown' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Waktu Pembatalan</p>
                        <p class="text-sm font-medium text-gray-800">{{ $cancelReasonShipment->cancelled_at ? \Carbon\Carbon::parse($cancelReasonShipment->cancelled_at)->format('d M Y, H:i') : '-' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Alasan Pembatalan</p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-1">
                            <p class="text-sm text-red-800">{{ $cancelReasonShipment->cancellation_reason ?: 'Tidak ada alasan yang dicatat.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 rounded-b-2xl">
                <button wire:click="closeCancelReasonModal" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg font-semibold hover:bg-gray-700 transition">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif

    {{-- PRINT PREVIEW MODAL --}}
    <div x-data="{ show: false, url: '' }" 
         x-on:open-print-preview.window="show = true; url = $event.detail.url"
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow-2xl flex flex-col" style="height: 90vh;">
            {{-- Header --}}
            <div class="p-4 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h3 class="font-bold text-lg text-orange-800 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Preview Surat Jalan
                </h3>
                <div class="flex items-center gap-2">
                    <button @click="$refs.printFrame.contentWindow.print()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Cetak
                    </button>
                    <a :href="url" target="_blank" class="border border-gray-300 hover:bg-gray-100 text-gray-600 px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition" title="Buka di Tab Baru">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    <button @click="show = false; url = ''" class="text-gray-400 hover:text-red-500 text-2xl leading-none px-1">&times;</button>
                </div>
            </div>
            {{-- Iframe --}}
            <div class="flex-1 overflow-hidden">
                <iframe x-ref="printFrame" :src="url" class="w-full h-full border-0 rounded-b-xl"></iframe>
            </div>
        </div>
    </div>
</div>
