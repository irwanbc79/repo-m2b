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
        <div class="flex items-center gap-3 text-sm">
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">
                📦 {{ $todayStats['shipments_today'] }} Shipment Hari Ini
            </span>
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium">
                💰 {{ $todayStats['payments_today'] }} Pembayaran Hari Ini
            </span>
            {{-- Angka dashboard di-cache; tombol ini untuk saat butuh yang
                 benar-benar detik ini juga. --}}
            <button wire:click="refreshStats" wire:loading.attr="disabled"
                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition disabled:opacity-40"
                title="Perbarui angka">
                <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refreshStats" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>
    </div>

    {{-- ===== PERLU TINDAKAN =====================================================
         Menggantikan Alert Center lama. Dashboard sebelumnya melaporkan jumlah,
         bukan pekerjaan; blok ini menjawab "apa yang harus dikerjakan lebih
         dulu", diurutkan dari yang paling merugikan kalau didiamkan. --}}
    @if(count($actionItems) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Perlu Tindakan</h3>
            <span class="text-[11px] font-bold text-gray-400">{{ count($actionItems) }} hal</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($actionItems as $item)
            <a href="{{ $item['link'] }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition group">
                <span class="shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-base
                    {{ $item['level'] === 'danger' ? 'bg-red-50' : ($item['level'] === 'warning' ? 'bg-amber-50' : 'bg-blue-50') }}">
                    {{ $item['icon'] }}
                </span>
                <span class="shrink-0 w-12 text-right text-xl font-black tabular-nums
                    {{ $item['level'] === 'danger' ? 'text-red-600' : ($item['level'] === 'warning' ? 'text-amber-600' : 'text-blue-600') }}">
                    {{ $item['count'] }}
                </span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-bold text-gray-800 truncate">{{ $item['title'] }}</span>
                    <span class="block text-xs text-gray-500 truncate">{{ $item['message'] }}</span>
                </span>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </div>
    @else
    <div class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-100 rounded-xl text-sm text-emerald-800">
        <span>✅</span> <span class="font-semibold">Tidak ada yang perlu ditindaklanjuti hari ini.</span>
    </div>
    @endif

    {{-- ===== AKSES CEPAT (terlihat di semua device, terutama iPhone PWA) ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Akses Cepat
        </h3>
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
            <a href="{{ route('admin.quotations.index') }}"
               class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-blue-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">Penawaran</span>
            </a>
            <a href="{{ route('admin.shipments.index') }}"
               class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-teal-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center group-hover:bg-teal-200 transition-colors">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">Shipment</span>
            </a>
            <a href="{{ route('admin.invoices.index') }}"
               class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-purple-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">Invoicing</span>
            </a>
            <a href="{{ route('inbox.index') }}"
               class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-amber-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">Email Inbox</span>
            </a>
            <a href="{{ route('finance.simple-invoice.index') }}"
               class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-cyan-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center group-hover:bg-cyan-200 transition-colors">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="text-[11px] font-semibold text-gray-600 text-center leading-tight">Nota Sederhana</span>
            </a>
        </div>
    </div>

    {{-- Main Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- Total Shipments --}}
        <a href="{{ route('admin.shipments.index') }}" 
           title="{{ number_format($mainStats['total_shipments'] ?? 0) }} Total Shipment"
           class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:scale-[1.02] hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-blue-600 transition-colors">Shipment Baru</span>
                <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-800 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['current_shipments'] ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ \App\Support\NumberHelper::formatCompact($mainStats['total_shipments'] ?? 0) }} sepanjang waktu</p>
        </a>

        {{-- Active/Pending --}}
        <a href="{{ route('admin.shipments.index') }}" 
           title="{{ number_format($mainStats['active_shipments'] ?? 0) }} Shipment Aktif (Pending & In Transit)"
           class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:scale-[1.02] hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-yellow-600 transition-colors">Shipment Aktif</span>
                <div class="p-2 bg-yellow-100 rounded-lg group-hover:bg-yellow-200 transition-colors">
                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-yellow-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['active_shipments'] ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Berjalan saat ini</p>
        </a>

        {{-- Completed --}}
        <a href="{{ route('admin.shipments.index') }}" 
           title="{{ number_format($mainStats['completed_shipments'] ?? 0) }} Shipment Selesai"
           class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:scale-[1.02] hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-green-600 transition-colors">Shipment Selesai</span>
                <div class="p-2 bg-green-100 rounded-lg group-hover:bg-green-200 transition-colors">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-green-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['completed_period'] ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ \App\Support\NumberHelper::formatCompact($mainStats['completed_shipments'] ?? 0) }} sepanjang waktu</p>
        </a>

        {{-- Revenue --}}
        <div title="Total Pendapatan: Rp {{ number_format($mainStats['current_revenue'] ?? 0, 0, ',', '.') }}"
             class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pendapatan</span>
                <div class="p-2 bg-emerald-100 rounded-lg">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-black text-emerald-600 tracking-tight">{{ \App\Support\NumberHelper::formatCurrencyCompact($mainStats['current_revenue'] ?? 0) }}</p>
            @php $growth = $mainStats['revenue_growth'] ?? null; @endphp
            <p class="text-xs mt-1 font-semibold {{ $growth === null ? 'text-gray-400' : ($growth >= 0 ? 'text-green-600' : 'text-red-600') }}">
                @if($growth === null)
                    Tidak ada pembanding
                @else
                    {{ $growth >= 0 ? '↑' : '↓' }} {{ abs($growth) }}%
                    <span class="font-normal text-gray-400">{{ $comparisonLabel }}</span>
                @endif
            </p>
        </div>

        {{-- Customers --}}
        <a href="{{ route('admin.customers.index') }}" 
           title="{{ number_format($mainStats['total_customers'] ?? 0) }} Total Pelanggan"
           class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:scale-[1.02] hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-purple-600 transition-colors">Pelanggan</span>
                <div class="p-2 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition-colors">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-purple-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['total_customers'] ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">+{{ $mainStats['new_customers'] ?? 0 }} baru periode ini</p>
        </a>

        {{-- Vendors --}}
        <a href="{{ route('admin.vendors.index') }}" 
           title="{{ number_format($mainStats['total_vendors'] ?? 0) }} Total Vendor"
           class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:scale-[1.02] hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-orange-600 transition-colors">Vendor</span>
                <div class="p-2 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition-colors">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-orange-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['total_vendors'] ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">Partner aktif</p>
        </a>
    </div>

    {{-- ===== RINGKASAN KEUANGAN ==============================================
         Empat kartu gradien ini memakan ~180px ruang paling mahal di layar.
         Saat semuanya nol — keadaan yang paling sering — tidak ada gunanya
         berteriak sebesar itu, jadi diciutkan jadi satu baris tenang. --}}
    @php
        $adaAngkaKeuangan = ($financialStats['unpaid_invoices'] ?? 0) > 0
            || ($financialStats['overdue_invoices'] ?? 0) > 0
            || ($financialStats['cash_in_today'] ?? 0) > 0
            || ($financialStats['cash_out_today'] ?? 0) > 0;
    @endphp

    @if($adaAngkaKeuangan)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.invoices.index') }}"
           title="Total Tagihan Belum Lunas: Rp {{ number_format($financialStats['unpaid_amount'] ?? 0, 0, ',', '.') }}"
           class="bg-gradient-to-br from-red-500 to-red-600 p-5 rounded-xl text-white shadow-sm hover:scale-[1.02] hover:shadow-md transition-all duration-200 block">
            <p class="text-xs font-semibold text-red-100 uppercase tracking-wider">Tagihan Belum Lunas</p>
            <p class="text-2xl font-black mt-1 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($financialStats['unpaid_invoices'] ?? 0) }}</p>
            <p class="text-sm font-bold text-red-100 mt-1">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['unpaid_amount'] ?? 0) }}</p>
        </a>

        <a href="{{ route('admin.invoices.index') }}"
           title="Total Tagihan Jatuh Tempo: Rp {{ number_format($financialStats['overdue_amount'] ?? 0, 0, ',', '.') }}"
           class="bg-gradient-to-br from-orange-500 to-orange-600 p-5 rounded-xl text-white shadow-sm hover:scale-[1.02] hover:shadow-md transition-all duration-200 block">
            <p class="text-xs font-semibold text-orange-100 uppercase tracking-wider">Tagihan Jatuh Tempo</p>
            <p class="text-2xl font-black mt-1 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($financialStats['overdue_invoices'] ?? 0) }}</p>
            <p class="text-sm font-bold text-orange-100 mt-1">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['overdue_amount'] ?? 0) }}</p>
        </a>

        <div title="Kas Masuk Hari Ini: Rp {{ number_format($financialStats['cash_in_today'] ?? 0, 0, ',', '.') }}"
             class="bg-gradient-to-br from-green-500 to-green-600 p-5 rounded-xl text-white shadow-sm">
            <p class="text-xs font-semibold text-green-100 uppercase tracking-wider">Kas Masuk Hari Ini</p>
            <p class="text-2xl font-black mt-1 tracking-tight">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['cash_in_today'] ?? 0) }}</p>
        </div>

        <div title="Kas Keluar Hari Ini: Rp {{ number_format($financialStats['cash_out_today'] ?? 0, 0, ',', '.') }}"
             class="bg-gradient-to-br from-blue-500 to-blue-600 p-5 rounded-xl text-white shadow-sm">
            <p class="text-xs font-semibold text-blue-100 uppercase tracking-wider">Kas Keluar Hari Ini</p>
            <p class="text-2xl font-black mt-1 tracking-tight">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['cash_out_today'] ?? 0) }}</p>
        </div>
    </div>
    @else
    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-500">
        <span class="font-bold text-gray-400 uppercase tracking-wider">Keuangan</span>
        <span>Tidak ada tagihan belum lunas</span>
        <span>Tidak ada yang jatuh tempo</span>
        <span>Belum ada kas masuk/keluar hari ini</span>
        <a href="{{ route('admin.invoices.index') }}" class="ml-auto font-semibold text-blue-600 hover:underline">Buka Invoicing →</a>
    </div>
    @endif

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
        {{-- ===== CORONG PIPELINE =============================================
             Donut lama menghitung semua status sepanjang waktu; karena 70 dari
             80 sudah selesai, 87% lingkarannya hijau — enak dilihat, tidak bisa
             ditindaklanjuti. Yang dikerjakan orang operasional adalah yang
             BELUM selesai, jadi itu yang ditampilkan, berurut sesuai alur.
             Batangnya diberi label langsung sehingga identitas tiap tahap tidak
             bergantung pada warna saja. --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-baseline justify-between mb-1">
                <h3 class="text-lg font-bold text-gray-800">🚦 Pipeline Berjalan</h3>
                <span class="text-2xl font-black text-gray-800 tabular-nums">{{ $pipeline['total'] }}</span>
            </div>
            <p class="text-xs text-gray-400 mb-5">Belum selesai · {{ number_format($pipeline['completed']) }} sudah selesai sepanjang waktu</p>

            @if($pipeline['total'] > 0)
            <div class="space-y-3">
                @foreach($pipeline['stages'] as $stage)
                <a href="{{ route('admin.shipments.index', ['filterStatus' => $stage['status']]) }}" class="block group">
                    <div class="flex items-baseline justify-between mb-1">
                        <span class="text-xs font-bold text-gray-600 group-hover:text-gray-900">{{ $stage['label'] }}</span>
                        <span class="text-xs text-gray-400 tabular-nums">
                            <span class="font-black text-sm text-gray-700">{{ $stage['count'] }}</span> · {{ $stage['percent'] }}%
                        </span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all" style="width: {{ max($stage['percent'], $stage['count'] > 0 ? 3 : 0) }}%; background: {{ $stage['color'] }};"></div>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-sm text-center py-8">Tidak ada shipment berjalan</p>
            @endif
        </div>

        {{-- Top Customers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800">🏆 Top 5 Customers</h3>
            <p class="text-xs text-gray-400 mb-4">Berdasarkan nilai invoice periode ini</p>
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
                    <div class="text-right shrink-0">
                        <p class="text-sm font-black text-emerald-700 tabular-nums">{{ \App\Support\NumberHelper::formatCurrencyCompact($customer->invoices_sum_grand_total ?? 0) }}</p>
                        <p class="text-[11px] text-gray-400">{{ $customer->shipments_count }} job</p>
                    </div>
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

    {{-- ═══════════════════════════════════════════════════════════════════
         SECTION: Pertumbuhan Bisnis Multi-Tahun (Accurate + Portal)
         Data: 2024–2025 bulanan (Accurate), 2026+ live (Portal)
         * = data live dari portal M2B
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Pertumbuhan Revenue M2B</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Data historis 2024–2025 dari Accurate Accounting &bull;
                    <span class="text-blue-500 font-medium">* data live portal M2B</span>
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span> Revenue</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-emerald-400"></span> Laba Kotor</span>
            </div>
        </div>

        {{-- ===== LABA BERSIH ==================================================
             Angka paling menentukan di halaman ini sebelumnya tercetak 11px di
             pojok kartu tahun. Rugi yang menyusut konsisten adalah kabar baik,
             dan itu harus terbaca sekali lihat — bukan dicari. --}}
        @if($netProfit)
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8 mb-6 p-4 rounded-xl border {{ $netProfit['value'] >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200' }}">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider {{ $netProfit['value'] >= 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                    Laba Bersih {{ $netProfit['year'] }}
                </p>
                <p class="text-3xl font-black tracking-tight tabular-nums {{ $netProfit['value'] >= 0 ? 'text-emerald-700' : 'text-amber-800' }}">
                    {{ $netProfit['value'] >= 0 ? '+' : '−' }}Rp {{ number_format(abs($netProfit['value'])/1000000, 0, ',', '.') }}jt
                </p>
                @if($netProfit['previous'] !== null)
                <p class="text-xs mt-1 font-semibold {{ $netProfit['improving'] ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ $netProfit['improving'] ? '↑ Membaik' : '↓ Memburuk' }}
                    <span class="font-normal text-gray-500">
                        dari {{ $netProfit['previous'] >= 0 ? '+' : '−' }}Rp {{ number_format(abs($netProfit['previous'])/1000000, 0, ',', '.') }}jt tahun sebelumnya
                    </span>
                </p>
                @endif
            </div>

            {{-- Sparkline lima tahun terakhir. Rugi digambar TURUN dari garis nol,
                 bukan naik seperti laba — batang yang sama-sama menjulang untuk
                 laba dan rugi akan membaca terbalik walau warnanya beda. Tiap
                 batang diberi label angka supaya tidak bergantung warna saja. --}}
            <div class="flex items-stretch gap-3 sm:ml-auto">
                @php $skala = max(array_map(fn ($d) => abs($d['value']), $netProfit['series'])) ?: 1; @endphp
                @foreach($netProfit['series'] as $titik)
                @php $tinggi = max(round(abs($titik['value']) / $skala * 100), 6); @endphp
                <div class="flex flex-col items-center w-12" title="{{ $titik['year'] }}: {{ $titik['value'] >= 0 ? '+' : '−' }}Rp {{ number_format(abs($titik['value'])/1000000, 0, ',', '.') }}jt">
                    {{-- separuh atas: laba --}}
                    <div class="w-full h-7 flex flex-col justify-end items-center">
                        @if($titik['value'] >= 0)
                        <span class="text-[10px] font-bold tabular-nums text-emerald-700 leading-none mb-0.5">+{{ number_format($titik['value']/1000000, 0) }}</span>
                        <div class="w-full rounded-t bg-emerald-500" style="height: {{ $tinggi }}%"></div>
                        @endif
                    </div>
                    <div class="w-full border-t border-gray-300"></div>
                    {{-- separuh bawah: rugi --}}
                    <div class="w-full h-7 flex flex-col items-center">
                        @if($titik['value'] < 0)
                        <div class="w-full rounded-b bg-red-400" style="height: {{ $tinggi }}%"></div>
                        <span class="text-[10px] font-bold tabular-nums text-red-500 leading-none mt-0.5">−{{ number_format(abs($titik['value'])/1000000, 0) }}</span>
                        @endif
                    </div>
                    <span class="text-[10px] text-gray-400 tabular-nums mt-1">{{ $titik['year'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Annual Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            @foreach($growthData['annualSummary'] as $year => $data)
            @php
                $isPortal = ($year >= 2026);
                $rev = $data['revenue'];
                $gp  = $data['gross_profit'];
                $np  = $data['net_profit'];
            @endphp
            <div class="rounded-lg p-3 text-center {{ $isPortal ? 'bg-blue-50 border border-blue-200' : 'bg-gray-50 border border-gray-100' }}">
                <div class="text-xs font-bold {{ $isPortal ? 'text-blue-600' : 'text-gray-500' }} mb-1">
                    {{ $year }}{{ $isPortal ? ' *' : '' }}
                </div>
                <div class="text-sm font-bold text-gray-800">
                    {{ $rev >= 1000000000 ? number_format($rev/1000000000, 1) . 'M' : number_format($rev/1000000, 0) . 'jt' }}
                </div>
                <div class="text-xs {{ $gp >= 0 ? 'text-emerald-600' : 'text-red-500' }} mt-0.5">
                    @if($gp != 0)
                        GP: {{ $gp >= 0 ? '+' : '' }}{{ number_format($gp/1000000, 0) }}jt
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </div>
                @if($np != 0)
                <div class="text-xs {{ $np >= 0 ? 'text-emerald-500' : 'text-red-400' }} mt-0.5">
                    NP: {{ $np >= 0 ? '+' : '' }}{{ number_format($np/1000000, 0) }}jt
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Multi-year Revenue Chart --}}
        <canvas id="growthChart" height="100"></canvas>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', initCharts);
        document.addEventListener('livewire:navigated', initCharts);
        
        function initCharts() {
            // Label berhenti di bulan berjalan. Sebelumnya Sep–Des ikut digambar
            // nol sehingga grafik terbaca seolah pendapatan anjlok.
            const months = @json($chartData['labels']);
            const shipmentData = @json($chartData['shipments']);
            const revenueData = @json($chartData['revenue']);
            const revenueMedian = @json($chartData['revenue_median']);

            Chart.getChart('shipmentChart')?.destroy();
            Chart.getChart('revenueChart')?.destroy();
            Chart.getChart('statusChart')?.destroy();
            Chart.getChart('growthChart')?.destroy();

            // ── Growth Chart (multi-year) ──────────────────────────────────
            const growthLabels  = @json($growthData['labels']);
            const growthRevenue = @json($growthData['revenue']);
            const growthGross   = @json($growthData['gross']);

            // Colour bars: blue for Accurate data, lighter blue for portal (labels with *)
            const barColors = growthLabels.map(l =>
                l.includes('*') ? 'rgba(59,130,246,0.9)' : 'rgba(156,163,175,0.6)'
            );

            new Chart(document.getElementById('growthChart'), {
                type: 'bar',
                data: {
                    labels: growthLabels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: growthRevenue,
                            backgroundColor: barColors,
                            borderRadius: 4,
                            order: 2,
                        },
                        {
                            label: 'Laba Kotor',
                            data: growthGross,
                            type: 'line',
                            borderColor: 'rgb(52,211,153)',
                            backgroundColor: 'rgba(52,211,153,0.1)',
                            pointRadius: 3,
                            tension: 0.3,
                            fill: false,
                            order: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const v = ctx.raw;
                                    const sign = v < 0 ? '-' : '';
                                    return ` ${ctx.dataset.label}: ${sign}Rp ${Math.abs(v/1000000).toFixed(0)}jt`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(0) + 'jt' }
                        }
                    }
                }
            });

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
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.raw} shipment` } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // Satu bulan yang melonjak membuat bulan lain rata di dasar grafik,
            // jadi median dipasang sebagai garis pembanding: "bulan biasa segini".
            new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: revenueData,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 8,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Median bulanan',
                            data: months.map(() => revenueMedian),
                            borderColor: 'rgba(107, 114, 128, 0.7)',
                            borderWidth: 2,
                            borderDash: [5, 4],
                            pointRadius: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: Rp ${(ctx.raw/1000000).toFixed(1)}jt`
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + (v/1000000).toFixed(0) + 'jt' } },
                        x: { grid: { display: false } }
                    }
                }
            });

        }
    </script>
</div>
