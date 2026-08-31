<div class="space-y-6">
    @section('header', 'Admin Dashboard')

    {{-- Period Filter & Today Stats --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 lg:p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Kiri: Judul & Filter Periode --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 pr-2 border-r border-gray-200">
                    <span class="text-xl">📊</span>
                    <h2 class="text-base font-black text-gray-800 tracking-tight">Ringkasan Eksekutif</h2>
                </div>

                {{-- Dropdown Periode --}}
                <div class="relative flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
                    <svg class="w-4 h-4 text-gray-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <select wire:model.live="period" class="bg-transparent border-0 text-sm font-semibold text-gray-700 py-0 pl-0 pr-6 focus:ring-0 cursor-pointer">
                        <option value="today">⚡ Hari Ini</option>
                        <option value="week">📅 Minggu Ini</option>
                        <option value="month">🗓️ Bulan Ini</option>
                        <option value="year">📆 Tahun Ini</option>
                        <option value="custom">🎯 Custom Range...</option>
                    </select>
                </div>

                {{-- Custom Date Range Picker --}}
                @if($showCustomRange || $period === "custom")
                <div class="flex items-center gap-2 bg-blue-50/80 px-3 py-1.5 rounded-xl border border-blue-200 animate-fade-in">
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] font-medium text-blue-700">Dari:</span>
                        <input type="date" wire:model="startDate" class="text-xs border-gray-300 rounded-lg focus:ring-blue-500 px-2 py-1 bg-white text-gray-700">
                    </div>
                    <span class="text-blue-400 text-xs">-</span>
                    <div class="flex items-center gap-1">
                        <span class="text-[11px] font-medium text-blue-700">Sampai:</span>
                        <input type="date" wire:model="endDate" class="text-xs border-gray-300 rounded-lg focus:ring-blue-500 px-2 py-1 bg-white text-gray-700">
                    </div>
                    <button wire:click="applyCustomRange" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                        Terapkan
                    </button>
                </div>
                @endif

                {{-- Rentang Tanggal Aktif --}}
                @if($period === "custom" && $startDate && $endDate)
                <span class="text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg">
                    📅 {{ \Carbon\Carbon::parse($startDate)->translatedFormat("d M Y") }} &minus; {{ \Carbon\Carbon::parse($endDate)->translatedFormat("d M Y") }}
                </span>
                @endif
            </div>

            {{-- Kanan: Status Hari Ini & Tombol Refresh --}}
            <div class="flex flex-wrap items-center gap-2.5 text-xs">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-100 text-blue-800 rounded-xl font-bold">
                    <span>📦</span>
                    <span>{{ $todayStats['shipments_today'] }} Shipment Hari Ini</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl font-bold">
                    <span>💰</span>
                    <span>{{ $todayStats['payments_today'] }} Pembayaran Hari Ini</span>
                </span>
                
                {{-- Tombol Refresh Cache --}}
                <button wire:click="refreshStats" wire:loading.attr="disabled"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50 hover:border-blue-200 rounded-xl font-semibold transition disabled:opacity-40"
                    title="Perbarui data detik ini">
                    <svg class="w-3.5 h-3.5" wire:loading.class="animate-spin text-blue-600" wire:target="refreshStats" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== PERLU TINDAKAN (ACTION ITEMS) ===== --}}
    @if(count($actionItems) > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50/70 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider">Perlu Tindakan Prioritas</h3>
            </div>
            <span class="text-xs font-bold text-gray-500 bg-white border border-gray-200 px-2.5 py-0.5 rounded-full">{{ count($actionItems) }} hal</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($actionItems as $item)
            <a href="{{ $item['link'] }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/80 transition group">
                <span class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg
                    {{ $item['level'] === 'danger' ? 'bg-rose-50 border border-rose-200' : ($item['level'] === 'warning' ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200') }}">
                    {{ $item['icon'] }}
                </span>
                <span class="shrink-0 w-12 text-right text-xl font-black tabular-nums
                    {{ $item['level'] === 'danger' ? 'text-rose-600' : ($item['level'] === 'warning' ? 'text-amber-600' : 'text-blue-600') }}">
                    {{ $item['count'] }}
                </span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm font-bold text-gray-800 group-hover:text-blue-600 transition-colors truncate">{{ $item['title'] }}</span>
                    <span class="block text-xs text-gray-500 truncate">{{ $item['message'] }}</span>
                </span>
                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-600 group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </div>
    @else
    <div class="flex items-center gap-2.5 px-5 py-3.5 bg-emerald-50/80 border border-emerald-200 rounded-2xl text-sm text-emerald-900 shadow-sm">
        <span class="text-base">✨</span>
        <span class="font-semibold">Semua tugas beres! Tidak ada kendala operasional yang perlu ditindaklanjuti saat ini.</span>
    </div>
    @endif

    {{-- ===== AKSES CEPAT (QUICK ACCESS) ===== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3.5 flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Navigasi Cepat
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <a href="{{ route('admin.quotations.index') }}"
               class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition group">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-800 block group-hover:text-blue-600 transition">Penawaran</span>
                    <span class="text-[10px] text-gray-400">Quotation CRM</span>
                </div>
            </a>
            <a href="{{ route('admin.shipments.index') }}"
               class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-teal-200 hover:bg-teal-50/50 transition group">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center group-hover:bg-teal-600 group-hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-800 block group-hover:text-teal-600 transition">Shipment</span>
                    <span class="text-[10px] text-gray-400">Tracking Logistik</span>
                </div>
            </a>
            <a href="{{ route('admin.invoices.index') }}"
               class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50/50 transition group">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-800 block group-hover:text-purple-600 transition">Invoicing</span>
                    <span class="text-[10px] text-gray-400">Tagihan & Kasir</span>
                </div>
            </a>
            <a href="{{ route('inbox.index') }}"
               class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50/50 transition group">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-600 group-hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-800 block group-hover:text-amber-600 transition">Email Inbox</span>
                    <span class="text-[10px] text-gray-400">Komunikasi Klien</span>
                </div>
            </a>
            <a href="{{ route('finance.simple-invoice.index') }}"
               class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-cyan-200 hover:bg-cyan-50/50 transition group">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:bg-cyan-600 group-hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-800 block group-hover:text-cyan-600 transition">Nota Sederhana</span>
                    <span class="text-[10px] text-gray-400">Kwitansi Cepat</span>
                </div>
            </a>
        </div>
    </div>

    {{-- ===== MAIN STATS CARDS (KARTU STATISTIK UTAMA) ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- Total Shipments Baru --}}
        <a href="{{ route('admin.shipments.index') }}" 
           title="{{ number_format($mainStats['total_shipments'] ?? 0) }} Total Shipment Aktif (Sepanjang Waktu)"
           class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-blue-200 hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-blue-600 transition-colors">Shipment Periode</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-gray-800 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['current_shipments'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">{{ \App\Support\NumberHelper::formatCompact($mainStats['total_shipments'] ?? 0) }} valid total</p>
        </a>

        {{-- Active Shipments --}}
        <a href="{{ route('admin.shipments.index') }}" 
           title="{{ number_format($mainStats['active_shipments'] ?? 0) }} Shipment Berjalan Saat Ini"
           class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-amber-200 hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-amber-600 transition-colors">Shipment Aktif</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['active_shipments'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">Berjalan saat ini</p>
        </a>

        {{-- Completed --}}
        <a href="{{ route('admin.shipments.index') }}" 
           title="{{ number_format($mainStats['completed_shipments'] ?? 0) }} Shipment Selesai"
           class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-emerald-200 hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-emerald-600 transition-colors">Selesai Periode</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-emerald-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['completed_period'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">{{ \App\Support\NumberHelper::formatCompact($mainStats['completed_shipments'] ?? 0) }} total selesai</p>
        </a>

        {{-- Revenue --}}
        <div title="Total Pendapatan: Rp {{ number_format($mainStats['current_revenue'] ?? 0, 0, ',', '.') }}"
             class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pendapatan</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-xl font-black text-emerald-700 tracking-tight">{{ \App\Support\NumberHelper::formatCurrencyCompact($mainStats['current_revenue'] ?? 0) }}</p>
            @php $growth = $mainStats['revenue_growth'] ?? null; @endphp
            <p class="text-xs mt-1 font-semibold {{ $growth === null ? 'text-gray-400' : ($growth >= 0 ? 'text-emerald-600' : 'text-rose-600') }}">
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
           title="{{ number_format($mainStats['total_customers'] ?? 0) }} Total Pelanggan Aktif"
           class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-purple-200 hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-purple-600 transition-colors">Pelanggan</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-purple-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['total_customers'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">+{{ $mainStats['new_customers'] ?? 0 }} baru periode ini</p>
        </a>

        {{-- Vendors --}}
        <a href="{{ route('admin.vendors.index') }}" 
           title="{{ number_format($mainStats['total_vendors'] ?? 0) }} Total Vendor Partner"
           class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-indigo-200 hover:shadow-md transition-all duration-200 block group">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider group-hover:text-indigo-600 transition-colors">Vendor</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-indigo-600 tracking-tight">{{ \App\Support\NumberHelper::formatCompact($mainStats['total_vendors'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">Partner terdaftar</p>
        </a>
    </div>

    {{-- ===== RINGKASAN KEUANGAN ELEGAN (CLEAN EXECUTIVE CARDS) ===== --}}
    @php
        $adaAngkaKeuangan = ($financialStats['unpaid_invoices'] ?? 0) > 0
            || ($financialStats['overdue_invoices'] ?? 0) > 0
            || ($financialStats['cash_in_today'] ?? 0) > 0
            || ($financialStats['cash_out_today'] ?? 0) > 0;
    @endphp

    @if($adaAngkaKeuangan)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Tagihan Belum Lunas --}}
        <a href="{{ route('admin.invoices.index') }}"
           title="Total Tagihan Belum Lunas: Rp {{ number_format($financialStats['unpaid_amount'] ?? 0, 0, ',', '.') }}"
           class="bg-white border-l-4 border-l-rose-500 border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md hover:border-rose-200 transition-all block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Tagihan Belum Lunas</span>
                <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 font-bold text-xs">Unpaid</span>
            </div>
            <p class="text-2xl font-black text-rose-600 mt-1.5 tracking-tight tabular-nums">{{ \App\Support\NumberHelper::formatCompact($financialStats['unpaid_invoices'] ?? 0) }} <span class="text-xs font-semibold text-gray-400">invoice</span></p>
            <p class="text-sm font-bold text-gray-700 mt-1">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['unpaid_amount'] ?? 0) }}</p>
        </a>

        {{-- Tagihan Jatuh Tempo --}}
        <a href="{{ route('admin.invoices.index') }}"
           title="Total Tagihan Jatuh Tempo: Rp {{ number_format($financialStats['overdue_amount'] ?? 0, 0, ',', '.') }}"
           class="bg-white border-l-4 border-l-amber-500 border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-200 transition-all block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Jatuh Tempo</span>
                <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 font-bold text-xs">Overdue</span>
            </div>
            <p class="text-2xl font-black text-amber-600 mt-1.5 tracking-tight tabular-nums">{{ \App\Support\NumberHelper::formatCompact($financialStats['overdue_invoices'] ?? 0) }} <span class="text-xs font-semibold text-gray-400">invoice</span></p>
            <p class="text-sm font-bold text-gray-700 mt-1">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['overdue_amount'] ?? 0) }}</p>
        </a>

        {{-- Kas Masuk Hari Ini --}}
        <div title="Kas Masuk Hari Ini: Rp {{ number_format($financialStats['cash_in_today'] ?? 0, 0, ',', '.') }}"
             class="bg-white border-l-4 border-l-emerald-500 border border-gray-100 p-5 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kas Masuk Hari Ini</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-bold text-xs">Inflow</span>
            </div>
            <p class="text-2xl font-black text-emerald-600 mt-1.5 tracking-tight tabular-nums">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['cash_in_today'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">Pemasukan kas masuk</p>
        </div>

        {{-- Kas Keluar Hari Ini --}}
        <div title="Kas Keluar Hari Ini: Rp {{ number_format($financialStats['cash_out_today'] ?? 0, 0, ',', '.') }}"
             class="bg-white border-l-4 border-l-blue-500 border border-gray-100 p-5 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Kas Keluar Hari Ini</span>
                <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 font-bold text-xs">Outflow</span>
            </div>
            <p class="text-2xl font-black text-blue-600 mt-1.5 tracking-tight tabular-nums">{{ \App\Support\NumberHelper::formatCurrencyCompact($financialStats['cash_out_today'] ?? 0) }}</p>
            <p class="text-xs text-gray-400 mt-1 font-medium">Pengeluaran kas operasional</p>
        </div>
    </div>
    @else
    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 px-5 py-3 bg-gray-50/90 border border-gray-200 rounded-2xl text-xs text-gray-500">
        <span class="font-bold text-gray-600 uppercase tracking-wider flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Keuangan Sehat
        </span>
        <span>Tidak ada tagihan belum lunas</span>
        <span>Tidak ada tagihan jatuh tempo</span>
        <span>Belum ada kas keluar/masuk hari ini</span>
        <a href="{{ route('admin.invoices.index') }}" class="ml-auto font-bold text-blue-600 hover:underline">Buka Invoicing &rarr;</a>
    </div>
    @endif

    {{-- ===== CHARTS ROW (GRAFIK BULANAN TAHUN BERJALAN) ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Shipment Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-black text-gray-800 tracking-tight flex items-center gap-2">
                        <span>📦</span> Shipment per Bulan ({{ now()->year }})
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">Jumlah shipment valid bulanan (tanpa shipment batal)</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">Tahun Berjalan</span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="shipmentChart"></canvas>
            </div>
        </div>

        {{-- Revenue Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-black text-gray-800 tracking-tight flex items-center gap-2">
                        <span>💰</span> Revenue per Bulan ({{ now()->year }})
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">Total omset invoice resmi & garis median bulanan</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Rupiah</span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    {{-- ===== BOTTOM ROW: PIPELINE, TOP CUSTOMERS, RECENT SHIPMENTS ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Pipeline Berjalan --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-baseline justify-between mb-1">
                    <h3 class="text-base font-black text-gray-800 tracking-tight flex items-center gap-2">
                        <span>🚦</span> Pipeline Berjalan
                    </h3>
                    <span class="text-2xl font-black text-gray-800 tabular-nums">{{ $pipeline['total'] }}</span>
                </div>
                <p class="text-xs text-gray-400 mb-5">Job aktif berlangsung · {{ number_format($pipeline['completed']) }} job selesai</p>

                @if($pipeline['total'] > 0)
                <div class="space-y-3.5">
                    @foreach($pipeline['stages'] as $stage)
                    <a href="{{ route('admin.shipments.index', ['filterStatus' => $stage['status']]) }}" class="block group">
                        <div class="flex items-baseline justify-between mb-1.5">
                            <span class="text-xs font-bold text-gray-700 group-hover:text-blue-600 transition">{{ $stage['label'] }}</span>
                            <span class="text-xs text-gray-400 tabular-nums">
                                <span class="font-black text-sm text-gray-800">{{ $stage['count'] }}</span> · {{ $stage['percent'] }}%
                            </span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width: {{ max($stage['percent'], $stage['count'] > 0 ? 4 : 0) }}%; background: {{ $stage['color'] }};"></div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="text-center py-10">
                    <span class="text-3xl">☕</span>
                    <p class="text-gray-400 text-xs mt-2 font-medium">Tidak ada shipment aktif yang berjalan.</p>
                </div>
                @endif
            </div>
            <div class="pt-4 mt-4 border-t border-gray-100 text-right">
                <a href="{{ route('admin.shipments.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">Buka Semua Shipment &rarr;</a>
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-base font-black text-gray-800 tracking-tight flex items-center gap-2">
                        <span>🏆</span> Top 5 Customers
                    </h3>
                    <span class="text-xs font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-700">Periode Ini</span>
                </div>
                <p class="text-xs text-gray-400 mb-4">Diurutkan berdasarkan nilai invoice masuk</p>

                @if($topCustomers->count() > 0)
                <div class="space-y-3">
                    @foreach($topCustomers as $index => $customer)
                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-gray-50 hover:border-gray-200 hover:bg-gray-50/60 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 flex items-center justify-center rounded-lg text-xs font-black
                                {{ $index === 0 ? 'bg-amber-100 text-amber-800 ring-2 ring-amber-300' : ($index === 1 ? 'bg-slate-200 text-slate-700' : ($index === 2 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600')) }}">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0 max-w-[130px] sm:max-w-[170px]">
                                <p class="font-bold text-xs text-gray-800 truncate" title="{{ $customer->company_name }}">{{ $customer->company_name }}</p>
                                <p class="text-[10px] text-gray-400">{{ $customer->customer_code }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-black text-emerald-700 tabular-nums">{{ \App\Support\NumberHelper::formatCurrencyCompact($customer->invoices_sum_grand_total ?? 0) }}</p>
                            <p class="text-[10px] font-semibold text-gray-400">{{ $customer->shipments_count }} job valid</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-10">
                    <span class="text-3xl">📭</span>
                    <p class="text-gray-400 text-xs mt-2 font-medium">Belum ada transaksi customer di periode ini.</p>
                </div>
                @endif
            </div>
            <div class="pt-4 mt-4 border-t border-gray-100 text-right">
                <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">Kelola Customer &rarr;</a>
            </div>
        </div>

        {{-- Recent Shipments --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-base font-black text-gray-800 tracking-tight flex items-center gap-2">
                        <span>🚚</span> Shipment Terbaru
                    </h3>
                    <a href="{{ route('admin.shipments.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Semua &rarr;</a>
                </div>
                <p class="text-xs text-gray-400 mb-4">5 pengiriman kargo terbaru di sistem</p>

                <div class="space-y-3">
                    @forelse($recentShipments as $shipment)
                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-gray-50 hover:border-gray-200 hover:bg-gray-50/60 transition">
                        <div class="min-w-0 pr-2">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs">
                                    @if(($shipment->shipment_type ?? '') === 'air') ✈️
                                    @elseif(($shipment->shipment_type ?? '') === 'sea') 🚢
                                    @else 🚛 @endif
                                </span>
                                <p class="font-mono font-bold text-xs text-blue-600 truncate">{{ $shipment->awb_number }}</p>
                            </div>
                            <p class="text-[10px] text-gray-400 truncate mt-0.5">{{ $shipment->customer->company_name ?? '-' }}</p>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-black rounded-full shrink-0
                            {{ $shipment->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                            {{ $shipment->status === 'pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                            {{ $shipment->status === 'in_progress' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                            {{ $shipment->status === 'in_transit' ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' }}
                            {{ in_array($shipment->status, ['cancel', 'cancelled']) ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                            {{ in_array($shipment->status, ['cancel', 'cancelled']) ? 'BATAL' : strtoupper(str_replace('_', ' ', $shipment->status)) }}
                        </span>
                    </div>
                    @empty
                    <div class="text-center py-10">
                        <span class="text-3xl">📦</span>
                        <p class="text-gray-400 text-xs mt-2 font-medium">Belum ada data shipment.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="pt-4 mt-4 border-t border-gray-100 text-right">
                <a href="{{ route('admin.shipments.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition">Manage Shipments &rarr;</a>
            </div>
        </div>
    </div>

    {{-- ===== SECTION: PERTUMBUHAN BISNIS MULTI-TAHUN (ACCURATE + PORTAL) ===== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-6">
            <div>
                <h3 class="text-base font-black text-gray-800 tracking-tight flex items-center gap-2">
                    <span>📈</span> Pertumbuhan Revenue M2B Multi-Tahun
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Data historis 2024&ndash;2025 dari Accurate Accounting &bull;
                    <span class="text-blue-600 font-bold">* data live portal M2B</span>
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold text-gray-600">
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-blue-600"></span> Revenue</span>
                <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded-full bg-emerald-500"></span> Laba Kotor</span>
            </div>
        </div>

        {{-- Laba Bersih Highlight --}}
        @if($netProfit)
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8 mb-6 p-5 rounded-2xl border {{ $netProfit['value'] >= 0 ? 'bg-emerald-50/70 border-emerald-200' : 'bg-amber-50/70 border-amber-200' }}">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider {{ $netProfit['value'] >= 0 ? 'text-emerald-800' : 'text-amber-800' }}">
                    Laba Bersih Tahun {{ $netProfit['year'] }}
                </p>
                <p class="text-3xl font-black tracking-tight tabular-nums {{ $netProfit['value'] >= 0 ? 'text-emerald-700' : 'text-amber-900' }}">
                    {{ $netProfit['value'] >= 0 ? '+' : '−' }}Rp {{ number_format(abs($netProfit['value'])/1000000, 0, ',', '.') }}jt
                </p>
                @if($netProfit['previous'] !== null)
                <p class="text-xs mt-1 font-bold {{ $netProfit['improving'] ? 'text-emerald-700' : 'text-rose-600' }}">
                    {{ $netProfit['improving'] ? '↑ Membaik' : '↓ Memburuk' }}
                    <span class="font-normal text-gray-600">
                        dari {{ $netProfit['previous'] >= 0 ? '+' : '−' }}Rp {{ number_format(abs($netProfit['previous'])/1000000, 0, ',', '.') }}jt tahun sebelumnya
                    </span>
                </p>
                @endif
            </div>

            {{-- Sparkline 5 Tahun --}}
            <div class="flex items-stretch gap-3 sm:ml-auto">
                @php $skala = max(array_map(fn ($d) => abs($d['value']), $netProfit['series'])) ?: 1; @endphp
                @foreach($netProfit['series'] as $titik)
                @php $tinggi = max(round(abs($titik['value']) / $skala * 100), 6); @endphp
                <div class="flex flex-col items-center w-12" title="{{ $titik['year'] }}: {{ $titik['value'] >= 0 ? '+' : '−' }}Rp {{ number_format(abs($titik['value'])/1000000, 0, ',', '.') }}jt">
                    {{-- Laba (Atas) --}}
                    <div class="w-full h-8 flex flex-col justify-end items-center">
                        @if($titik['value'] >= 0)
                        <span class="text-[10px] font-black tabular-nums text-emerald-700 leading-none mb-0.5">+{{ number_format($titik['value']/1000000, 0) }}</span>
                        <div class="w-full rounded-t bg-emerald-500" style="height: {{ $tinggi }}%"></div>
                        @endif
                    </div>
                    <div class="w-full border-t border-gray-300"></div>
                    {{-- Rugi (Bawah) --}}
                    <div class="w-full h-8 flex flex-col items-center">
                        @if($titik['value'] < 0)
                        <div class="w-full rounded-b bg-rose-400" style="height: {{ $tinggi }}%"></div>
                        <span class="text-[10px] font-black tabular-nums text-rose-600 leading-none mt-0.5">−{{ number_format(abs($titik['value'])/1000000, 0) }}</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold text-gray-500 tabular-nums mt-1">{{ $titik['year'] }}</span>
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
            <div class="rounded-xl p-3.5 text-center {{ $isPortal ? 'bg-blue-50/70 border border-blue-200' : 'bg-gray-50/80 border border-gray-100' }}">
                <div class="text-xs font-black {{ $isPortal ? 'text-blue-600' : 'text-gray-500' }} mb-1">
                    {{ $year }}{{ $isPortal ? ' *' : '' }}
                </div>
                <div class="text-sm font-black text-gray-800">
                    {{ $rev >= 1000000000 ? number_format($rev/1000000000, 1) . 'M' : number_format($rev/1000000, 0) . 'jt' }}
                </div>
                <div class="text-xs font-semibold {{ $gp >= 0 ? 'text-emerald-600' : 'text-rose-500' }} mt-0.5">
                    @if($gp != 0)
                        GP: {{ $gp >= 0 ? '+' : '' }}{{ number_format($gp/1000000, 0) }}jt
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </div>
                @if($np != 0)
                <div class="text-xs font-semibold {{ $np >= 0 ? 'text-emerald-500' : 'text-rose-400' }} mt-0.5">
                    NP: {{ $np >= 0 ? '+' : '' }}{{ number_format($np/1000000, 0) }}jt
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Multi-year Revenue Chart --}}
        <div class="relative h-64 w-full">
            <canvas id="growthChart"></canvas>
        </div>
    </div>

    {{-- ===== SCRIPT CHART.JS DENGAN STYLING MODERN ===== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', initCharts);
        document.addEventListener('livewire:navigated', initCharts);
        
        function initCharts() {
            const months = @json($chartData['labels']);
            const shipmentData = @json($chartData['shipments']);
            const revenueData = @json($chartData['revenue']);
            const revenueMedian = @json($chartData['revenue_median']);

            Chart.getChart('shipmentChart')?.destroy();
            Chart.getChart('revenueChart')?.destroy();
            Chart.getChart('growthChart')?.destroy();

            // ── 1. Growth Chart (Multi-year Accurate + Portal) ──
            const growthCanvas = document.getElementById('growthChart');
            if (growthCanvas) {
                const growthLabels  = @json($growthData['labels']);
                const growthRevenue = @json($growthData['revenue']);
                const growthGross   = @json($growthData['gross']);

                const barColors = growthLabels.map(l =>
                    l.includes('*') ? 'rgba(37, 99, 235, 0.85)' : 'rgba(156, 163, 175, 0.55)'
                );

                new Chart(growthCanvas, {
                    type: 'bar',
                    data: {
                        labels: growthLabels,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: growthRevenue,
                                backgroundColor: barColors,
                                borderRadius: 6,
                                order: 2,
                            },
                            {
                                label: 'Laba Kotor',
                                data: growthGross,
                                type: 'line',
                                borderColor: '#059669',
                                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                                pointBackgroundColor: '#059669',
                                pointRadius: 3,
                                tension: 0.3,
                                fill: false,
                                order: 1,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: ctx => {
                                        const v = ctx.raw;
                                        const sign = v < 0 ? '-' : '';
                                        return ` ${ctx.dataset.label}: ${sign}Rp ${Math.abs(v/1000000).toFixed(1)} Juta`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { 
                                grid: { display: false },
                                ticks: { font: { size: 10, weight: 'bold' }, maxRotation: 45 } 
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(243, 244, 246, 1)' },
                                ticks: { 
                                    font: { size: 11 },
                                    callback: v => 'Rp ' + (v/1000000).toFixed(0) + 'jt' 
                                }
                            }
                        }
                    }
                });
            }

            // ── 2. Shipment Chart (Modern Royal Blue Rounded Bar) ──
            const shipmentCanvas = document.getElementById('shipmentChart');
            if (shipmentCanvas) {
                const ctxS = shipmentCanvas.getContext('2d');
                const gradientS = ctxS.createLinearGradient(0, 0, 0, 250);
                gradientS.addColorStop(0, 'rgba(37, 99, 235, 0.9)');
                gradientS.addColorStop(1, 'rgba(96, 165, 250, 0.4)');

                new Chart(shipmentCanvas, {
                    type: 'bar',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Shipment Valid',
                            data: shipmentData,
                            backgroundColor: gradientS,
                            hoverBackgroundColor: 'rgba(29, 78, 216, 1)',
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: { label: ctx => ` 📦 ${ctx.raw} shipment aktif` }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true, 
                                grid: { color: 'rgba(243, 244, 246, 1)' },
                                ticks: { precision: 0, font: { size: 11, weight: 'bold' } } 
                            },
                            x: { 
                                grid: { display: false },
                                ticks: { font: { size: 11, weight: 'bold' } }
                            }
                        }
                    }
                });
            }

            // ── 3. Revenue Chart (Emerald Spline with Smooth Gradient Area) ──
            const revenueCanvas = document.getElementById('revenueChart');
            if (revenueCanvas) {
                const ctxR = revenueCanvas.getContext('2d');
                const gradientR = ctxR.createLinearGradient(0, 0, 0, 250);
                gradientR.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
                gradientR.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

                new Chart(revenueCanvas, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [
                            {
                                label: 'Revenue Invoice',
                                data: revenueData,
                                borderColor: '#059669',
                                backgroundColor: gradientR,
                                borderWidth: 3,
                                pointBackgroundColor: '#059669',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 7,
                                fill: true,
                                tension: 0.35
                            },
                            {
                                label: 'Median Bulanan',
                                data: months.map(() => revenueMedian),
                                borderColor: 'rgba(156, 163, 175, 0.8)',
                                borderWidth: 2,
                                borderDash: [6, 4],
                                pointRadius: 0,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { 
                                display: true, 
                                position: 'bottom', 
                                labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } } 
                            },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: ctx => ` ${ctx.dataset.label}: Rp ${(ctx.raw/1000000).toFixed(1)} Juta`
                                }
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true, 
                                grid: { color: 'rgba(243, 244, 246, 1)' },
                                ticks: { 
                                    font: { size: 11 },
                                    callback: v => 'Rp ' + (v/1000000).toFixed(0) + 'jt' 
                                } 
                            },
                            x: { 
                                grid: { display: false },
                                ticks: { font: { size: 11, weight: 'bold' } }
                            }
                        }
                    }
                });
            }
        }
    </script>
</div>
