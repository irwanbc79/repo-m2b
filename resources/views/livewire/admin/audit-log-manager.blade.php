<div class="space-y-6 max-w-[1600px] mx-auto pb-12">
    @section('header', 'Audit Logs / Rekam Jejak Aktivitas')

    {{-- HERO METRICS / 4 STATS CARDS PROPORSI TINGGI & HARMONIS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- Card 1: Total Volume Log (Deep Navy & Indigo Gradient) --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-[#0F2C59] to-indigo-950 rounded-2xl p-5 text-white shadow-md border border-slate-700/60 hover:shadow-lg transition">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-200/90 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span> Total Rekam Jejak
                    </p>
                    <h3 class="text-3xl font-black mt-1 text-white tracking-tight font-mono">{{ number_format($stats['total'] ?? 0) }}</h3>
                    <div class="mt-2.5 flex items-center gap-1.5 text-xs text-indigo-200">
                        <span class="inline-flex items-center gap-1 font-bold text-white bg-white/15 px-2 py-0.5 rounded-full border border-white/20 text-[11px] backdrop-blur-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            +{{ number_format($stats['this_week'] ?? 0) }}
                        </span>
                        <span class="text-slate-300 font-medium">minggu ini</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-cyan-300 border border-white/20 shadow-inner shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        {{-- Card 2: Aktivitas Hari Ini (Royal Indigo & Cyan Gradient) --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-800 to-indigo-900 rounded-2xl p-5 text-white shadow-md border border-blue-600/40 hover:shadow-lg transition">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-200/90 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span> Aktivitas Hari Ini
                    </p>
                    <h3 class="text-3xl font-black mt-1 text-white tracking-tight font-mono">{{ number_format($stats['today'] ?? 0) }}</h3>
                    <div class="mt-2.5">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-black/30 border border-white/20 font-bold text-[11px] text-cyan-200 backdrop-blur-xs">
                            👥 {{ $stats['users_active'] ?? 0 }} Staf Aktif
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white border border-white/20 shadow-inner shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-cyan-400/10 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        {{-- Card 3: Operasi CRUD Data (Clean Elevated White Card) --}}
        <div class="relative overflow-hidden bg-white rounded-2xl p-5 text-slate-800 shadow-sm border border-slate-200 hover:border-slate-300 hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Mutasi &amp; Modifikasi Data</p>
                    <h3 class="text-3xl font-black mt-1 text-slate-900 tracking-tight font-mono">{{ number_format(($stats['creates'] ?? 0) + ($stats['updates'] ?? 0)) }}</h3>
                    <div class="flex items-center gap-2 mt-2.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200 text-[11px]">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> +{{ $stats['creates'] ?? 0 }} Create
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-amber-700 font-bold bg-amber-50 px-2.5 py-0.5 rounded-lg border border-amber-200 text-[11px]">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> ✏️ {{ $stats['updates'] ?? 0 }} Update
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700 border border-slate-200 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>
        </div>

        {{-- Card 4: Forensic Risk Warning (Dynamic State: Safe Green / Danger Red) --}}
        @php
            $highRiskCount = $stats['today_high_risk'] ?? 0;
        @endphp
        <div wire:click="setRiskFilter('high')" class="cursor-pointer relative overflow-hidden rounded-2xl p-5 text-white transition-all duration-300 hover:shadow-lg {{ $highRiskCount > 0 ? 'bg-gradient-to-br from-rose-600 via-red-600 to-rose-800 shadow-lg shadow-rose-500/20 ring-2 ring-rose-400 animate-pulse border border-rose-400' : 'bg-gradient-to-br from-slate-900 via-slate-850 to-emerald-950 shadow-sm border border-slate-800 hover:border-emerald-700/50' }}">
            <div class="flex items-start justify-between relative z-10">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider {{ $highRiskCount > 0 ? 'text-rose-100' : 'text-slate-400' }}">Forensic Risk Status</p>
                    <h3 class="text-3xl font-black mt-1 tracking-tight font-mono {{ $highRiskCount > 0 ? 'text-white' : 'text-emerald-400' }}">{{ $highRiskCount }}</h3>
                    <div class="mt-2.5">
                        @if($highRiskCount > 0)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-white/20 text-white px-2.5 py-0.5 rounded-lg border border-white/30">
                                ⚠️ High Risk Events (Klik Cek)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-400 bg-emerald-950/80 px-2.5 py-0.5 rounded-lg border border-emerald-800/80">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Sistem Aman (0 Insiden)
                            </span>
                        @endif
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl {{ $highRiskCount > 0 ? 'bg-white/20 text-white border-white/30' : 'bg-emerald-950/60 text-emerald-400 border-emerald-700/50' }} flex items-center justify-center border shadow-inner shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <div class="absolute -right-6 -bottom-6 w-28 h-28 {{ $highRiskCount > 0 ? 'bg-red-400/20' : 'bg-emerald-500/10' }} rounded-full blur-2xl pointer-events-none"></div>
        </div>

    </div>

    {{-- CONTROL TOOLBAR / FILTER PANEL (FULL WIDTH) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
        
        {{-- Row 1: Search & Risk Pills & Excel Export --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            {{-- Smart Search Bar --}}
            <div class="relative flex-1 max-w-lg">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="Cari nama staf, no. referensi (INV/JOB/JR), modul, atau aksi..." 
                    class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition placeholder:text-slate-400 text-slate-800 font-medium"
                >
                @if($search)
                <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @endif
            </div>

            {{-- Risk Filter Quick Tabs & Export --}}
            <div class="flex flex-wrap items-center gap-2">
                
                {{-- Risk Pills --}}
                <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">
                    <button 
                        wire:click="setRiskFilter('')" 
                        class="px-3 py-1.5 rounded-lg transition {{ empty($filterRisk) ? 'bg-slate-900 text-white shadow-xs font-bold' : 'hover:text-slate-900' }}"
                    >
                        Semua
                    </button>
                    <button 
                        wire:click="setRiskFilter('high')" 
                        class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $filterRisk === 'high' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'text-rose-700 hover:bg-rose-50 font-bold' }}"
                    >
                        <span class="w-1.5 h-1.5 rounded-full {{ $filterRisk === 'high' ? 'bg-white' : 'bg-rose-600' }}"></span>
                        High Risk
                    </button>
                    <button 
                        wire:click="setRiskFilter('medium')" 
                        class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $filterRisk === 'medium' ? 'bg-amber-500 text-white shadow-xs font-bold' : 'text-amber-800 hover:bg-amber-50 font-bold' }}"
                    >
                        <span class="w-1.5 h-1.5 rounded-full {{ $filterRisk === 'medium' ? 'bg-white' : 'bg-amber-500' }}"></span>
                        Medium
                    </button>
                    <button 
                        wire:click="setRiskFilter('low')" 
                        class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ $filterRisk === 'low' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-blue-700 hover:bg-blue-50 font-bold' }}"
                    >
                        <span class="w-1.5 h-1.5 rounded-full {{ $filterRisk === 'low' ? 'bg-white' : 'bg-blue-600' }}"></span>
                        Info / Low
                    </button>
                </div>

                {{-- Export Excel Button --}}
                <button 
                    wire:click="exportExcel" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl font-bold text-xs tracking-wide shadow-sm shadow-emerald-600/20 hover:shadow transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span wire:loading.remove wire:target="exportExcel">Export CSV</span>
                    <span wire:loading wire:target="exportExcel">Menyiapkan...</span>
                </button>
            </div>

        </div>

        {{-- Row 2: Secondary Dropdown Filters & Date Presets --}}
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Filter User --}}
                <select wire:model.live="filterUser" class="bg-slate-50 border border-slate-300 rounded-xl text-xs py-2 px-3 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800 shadow-2xs">
                    <option value="">👤 Semua Staf</option>
                    @foreach($filterOptions['users'] ?? [] as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>

                {{-- Filter Modul --}}
                <select wire:model.live="filterModule" class="bg-slate-50 border border-slate-300 rounded-xl text-xs py-2 px-3 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800 shadow-2xs">
                    <option value="">📂 Semua Modul</option>
                    @foreach($filterOptions['modules'] ?? [] as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>

                {{-- Filter Aksi --}}
                <select wire:model.live="filterAction" class="bg-slate-50 border border-slate-300 rounded-xl text-xs py-2 px-3 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800 shadow-2xs">
                    <option value="">⚡ Semua Aksi</option>
                    @foreach($filterOptions['actions'] ?? [] as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>

                {{-- Filter Role --}}
                <select wire:model.live="filterRole" class="bg-slate-50 border border-slate-300 rounded-xl text-xs py-2 px-3 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-800 shadow-2xs">
                    <option value="">🏷️ Semua Role</option>
                    @foreach($filterOptions['roles'] ?? [] as $r)
                        <option value="{{ $r }}">{{ strtoupper(str_replace('_', ' ', $r)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Filter Presets --}}
            <div class="flex items-center gap-2">
                <div class="inline-flex p-0.5 bg-slate-100 rounded-lg text-[11px] font-semibold text-slate-700 border border-slate-200">
                    <button wire:click="setDatePreset('all')" class="px-2.5 py-1 rounded-md transition {{ $datePreset === 'all' && empty($filterDateFrom) ? 'bg-slate-900 text-white shadow-xs font-bold' : 'hover:text-slate-900' }}">Semua</button>
                    <button wire:click="setDatePreset('today')" class="px-2.5 py-1 rounded-md transition {{ $datePreset === 'today' ? 'bg-slate-900 text-white shadow-xs font-bold' : 'hover:text-slate-900' }}">Hari Ini</button>
                    <button wire:click="setDatePreset('this_week')" class="px-2.5 py-1 rounded-md transition {{ $datePreset === 'this_week' ? 'bg-slate-900 text-white shadow-xs font-bold' : 'hover:text-slate-900' }}">Minggu Ini</button>
                    <button wire:click="setDatePreset('this_month')" class="px-2.5 py-1 rounded-md transition {{ $datePreset === 'this_month' ? 'bg-slate-900 text-white shadow-xs font-bold' : 'hover:text-slate-900' }}">Bulan Ini</button>
                </div>

                {{-- Date Inputs --}}
                <div class="flex items-center gap-1">
                    <input wire:model.live="filterDateFrom" type="date" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1.5 px-2 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 text-slate-800 font-medium" title="Dari Tanggal">
                    <span class="text-slate-400 text-xs">-</span>
                    <input wire:model.live="filterDateTo" type="date" class="bg-slate-50 border border-slate-300 rounded-lg text-xs py-1.5 px-2 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 text-slate-800 font-medium" title="Sampai Tanggal">
                </div>

                @if($filterUser || $filterRole || $filterModule || $filterAction || $filterRisk || $filterDateFrom || $filterDateTo || $search)
                <button wire:click="clearFilters" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reset
                </button>
                @endif
            </div>

        </div>

    </div>

    {{-- FORENSIC AUDIT TABLE CONTAINER DENGAN DARK EXECUTIVE HEADER (FULL WIDTH) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900 border-b border-slate-800 text-[11px] font-extrabold uppercase tracking-wider text-slate-200">
                        <th class="py-3.5 px-4 pl-6">Waktu &amp; Sesi</th>
                        <th class="py-3.5 px-4">Pelaku (User)</th>
                        <th class="py-3.5 px-4">Modul</th>
                        <th class="py-3.5 px-4">No. Referensi</th>
                        <th class="py-3.5 px-4">Aktivitas &amp; Uraian</th>
                        <th class="py-3.5 px-3 text-center">Risiko</th>
                        <th class="py-3.5 px-4">IP &amp; Device</th>
                        <th class="py-3.5 px-4 pr-6 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($logs as $log)
                    @php
                        $highRiskActions = [
                            'DELETE', 'DELETE_JOURNAL', 'DELETE_USER', 'DELETE_COA', 'DELETE_COST', 
                            'VOID', 'CANCEL', 'CANCEL_INVOICE', 'UPDATE_ROLE', 'UPDATE_BANK_DETAILS', 
                            'UPDATE_BALANCE', 'LOGIN_BLOCKED', 'LOGIN_FAILED', 'VERIFY_PAYMENT'
                        ];
                        $highRiskModules = ['Cashier', 'JobCost', 'VendorBill', 'Payroll'];

                        $isHighRisk = in_array($log->action, $highRiskActions) || in_array($log->module, $highRiskModules);
                                      
                        $isMediumRisk = !$isHighRisk && (
                            str_contains($log->action, 'CREATE') || 
                            str_contains($log->action, 'UPDATE') || 
                            str_contains($log->action, 'EDIT') ||
                            $log->action === 'STATUS_CHANGE' ||
                            $log->action === 'CONVERT_TO_SHIPMENT'
                        );

                        // Module Styling & Icons
                        $moduleStyles = [
                            'Accounting' => ['bg' => 'bg-emerald-50 text-emerald-800 border-emerald-300', 'icon' => '💼'],
                            'Invoice'    => ['bg' => 'bg-violet-50 text-violet-800 border-violet-300', 'icon' => '🧾'],
                            'Shipment'   => ['bg' => 'bg-blue-50 text-blue-800 border-blue-300', 'icon' => '🚢'],
                            'Quotation'  => ['bg' => 'bg-amber-50 text-amber-900 border-amber-300', 'icon' => '📋'],
                            'Auth'       => ['bg' => 'bg-purple-50 text-purple-800 border-purple-300', 'icon' => '🔐'],
                            'UserManagement' => ['bg' => 'bg-indigo-50 text-indigo-800 border-indigo-300', 'icon' => '👥'],
                            'Customer'   => ['bg' => 'bg-teal-50 text-teal-800 border-teal-300', 'icon' => '🏢'],
                            'Vendor'     => ['bg' => 'bg-cyan-50 text-cyan-800 border-cyan-300', 'icon' => '🚚'],
                            'Cashier'    => ['bg' => 'bg-emerald-50 text-emerald-800 border-emerald-300', 'icon' => '💵'],
                            'JobCost'    => ['bg' => 'bg-orange-50 text-orange-800 border-orange-300', 'icon' => '📊'],
                        ];
                        $modStyle = $moduleStyles[$log->module] ?? ['bg' => 'bg-slate-100 text-slate-800 border-slate-300', 'icon' => '📁'];

                        // Action Badge Styling
                        $actionColors = [
                            'CREATE' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                            'CREATE_JOURNAL' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                            'CREATE_COA' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                            'CREATE_USER' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                            'UPDATE' => 'bg-blue-50 text-blue-800 border-blue-300',
                            'UPDATE_JOURNAL' => 'bg-blue-50 text-blue-800 border-blue-300',
                            'UPDATE_COA' => 'bg-blue-50 text-blue-800 border-blue-300',
                            'UPDATE_ROLE' => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
                            'UPDATE_BANK_DETAILS' => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
                            'VERIFY_PAYMENT' => 'bg-emerald-50 text-emerald-800 border-emerald-300 font-bold',
                            'DELETE' => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
                            'DELETE_JOURNAL' => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
                            'DELETE_USER' => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
                            'DELETE_COA' => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
                            'LOGIN' => 'bg-purple-50 text-purple-800 border-purple-300',
                            'LOGIN_GOOGLE' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                            'LOGIN_FAILED' => 'bg-rose-100 text-rose-900 border-rose-400 font-bold',
                            'LOGIN_BLOCKED' => 'bg-rose-100 text-rose-900 border-rose-400 font-bold',
                            'LOGOUT' => 'bg-slate-100 text-slate-800 border-slate-300',
                            'SEND_EMAIL' => 'bg-sky-50 text-sky-800 border-sky-300',
                        ];
                        $actColor = $actionColors[$log->action] ?? (str_contains($log->action, 'DELETE') ? 'bg-rose-50 text-rose-800 border-rose-300' : 'bg-slate-100 text-slate-800 border-slate-300');

                        // Border indicator styling for rows
                        $rowBorder = $isHighRisk ? 'border-l-4 border-l-rose-500 bg-rose-50/20 hover:bg-rose-50/40' : ($isMediumRisk ? 'border-l-4 border-l-amber-400 hover:bg-amber-50/25' : 'border-l-4 border-l-transparent hover:bg-indigo-50/30');
                    @endphp

                    <tr class="transition-colors {{ $rowBorder }}">
                        
                        {{-- Waktu --}}
                        <td class="py-3.5 px-4 pl-6 whitespace-nowrap">
                            <p class="font-bold text-slate-900 text-xs font-mono">{{ $log->created_at->format('H:i:s') }}</p>
                            <p class="text-[11px] text-slate-600 font-medium">{{ $log->created_at->format('d M Y') }}</p>
                            <span class="inline-block text-[10px] text-slate-400 font-medium mt-0.5">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </td>

                        {{-- User / Pelaku --}}
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                @php
                                    $userInitial = strtoupper(substr($log->user_name ?? 'U', 0, 1));
                                    $avatarGradients = [
                                        'A' => 'from-rose-500 to-red-600',
                                        'B' => 'from-blue-600 to-indigo-700',
                                        'C' => 'from-emerald-600 to-teal-700',
                                        'D' => 'from-amber-500 to-orange-600',
                                        'E' => 'from-purple-600 to-pink-600',
                                        'K' => 'from-indigo-600 to-blue-700',
                                        'N' => 'from-teal-600 to-emerald-700',
                                        'S' => 'from-violet-600 to-purple-700',
                                    ];
                                    $avatarGrad = $avatarGradients[$userInitial] ?? 'from-slate-700 to-slate-800';
                                @endphp
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr {{ $avatarGrad }} flex items-center justify-center text-white font-bold text-xs shadow-xs shrink-0">
                                    {{ $userInitial }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 text-xs truncate">{{ $log->user_name }}</p>
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wider mt-0.5">
                                        {{ str_replace('_', ' ', $log->role) }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Modul --}}
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $modStyle['bg'] }}">
                                <span>{{ $modStyle['icon'] }}</span>
                                <span>{{ $log->module }}</span>
                            </span>
                        </td>

                        {{-- No Referensi --}}
                        <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs font-bold text-indigo-800">
                            @if($log->target_ref)
                                <span class="bg-indigo-50/90 text-indigo-900 px-2.5 py-1 rounded-md border border-indigo-200 inline-block">{{ $log->target_ref }}</span>
                            @else
                                <span class="text-slate-400 font-normal">-</span>
                            @endif
                        </td>

                        {{-- Aktivitas & Uraian --}}
                        <td class="py-3.5 px-4 max-w-sm">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $actColor }}">
                                    @if(str_contains($log->action, 'CREATE'))
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    @elseif(str_contains($log->action, 'DELETE'))
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    @elseif(str_contains($log->action, 'UPDATE'))
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    @endif
                                    {{ $log->action }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-700 font-normal leading-snug line-clamp-2" title="{{ $log->description }}">
                                {{ $log->description ?: '-' }}
                            </p>
                        </td>

                        {{-- Tingkat Risiko --}}
                        <td class="py-3.5 px-3 text-center whitespace-nowrap">
                            @if($isHighRisk)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs">
                                    🔴 High
                                </span>
                            @elseif($isMediumRisk)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                                    🟡 Med
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-800 border border-blue-200">
                                    🔵 Info
                                </span>
                            @endif
                        </td>

                        {{-- IP & Device --}}
                        <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                            <p class="font-mono text-slate-800 font-bold">{{ $log->ip_address }}</p>
                            @if($log->user_agent)
                                <p class="text-[10px] text-slate-500 font-medium truncate max-w-[120px] mt-0.5" title="{{ $log->user_agent }}">
                                    {{ str_contains($log->user_agent, 'Macintosh') ? '💻 Mac' : (str_contains($log->user_agent, 'Windows') ? '💻 Windows' : (str_contains($log->user_agent, 'Android') || str_contains($log->user_agent, 'iPhone') ? '📱 Mobile' : '🖥️ Browser')) }}
                                </p>
                            @endif
                        </td>

                        {{-- Action Button --}}
                        <td class="py-3.5 px-4 pr-6 text-center whitespace-nowrap">
                            <button 
                                wire:click="viewDetail({{ $log->id }})" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-600 hover:text-white border border-indigo-200 transition shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </button>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-16 px-6 text-center">
                            <div class="max-w-sm mx-auto">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <h4 class="font-bold text-slate-900 text-sm">Tidak ada log aktivitas</h4>
                                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau filter tanggal yang dipilih.</p>
                                @if($search || $filterUser || $filterModule || $filterAction || $filterRisk || $filterDateFrom)
                                    <button wire:click="clearFilters" class="mt-3 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition">
                                        Reset Semua Filter
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <p class="text-xs font-medium text-slate-600">
                Menampilkan <span class="font-bold text-slate-900">{{ $logs->firstItem() ?? 0 }}</span> - <span class="font-bold text-slate-900">{{ $logs->lastItem() ?? 0 }}</span> dari total <span class="font-bold text-slate-900">{{ $logs->total() }}</span> log aktivitas
            </p>
            <div>
                {{ $logs->links() }}
            </div>
        </div>

    </div>

    {{-- BOTTOM ANALYTICS & INSIGHTS GRID: 3 Kolom di Bawah Tabel --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
        
        {{-- Widget 1: Leaderboard Staf 7 Hari --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🏆</span> Leaderboard Staf (7 Hari)
                    </h4>
                    <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full">Real-time</span>
                </div>

                <div class="space-y-3 mt-3.5">
                    @php
                        $maxUserCount = collect($topUsers)->max('count') ?: 1;
                        $rankMedals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                    @endphp
                    @forelse($topUsers as $index => $user)
                    <div class="space-y-1.5 p-2 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-200">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-base shrink-0">{{ $rankMedals[$index] ?? '#' . ($index + 1) }}</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900 truncate leading-tight">{{ $user->user_name }}</p>
                                    <p class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider leading-none mt-0.5">{{ str_replace('_', ' ', $user->role) }}</p>
                                </div>
                            </div>
                            <span class="font-black text-indigo-800 bg-indigo-50 px-2 py-0.5 rounded-md text-xs shrink-0 font-mono border border-indigo-100">
                                {{ $user->count }} logs
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/60">
                            <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 h-2 rounded-full transition-all duration-500" style="width: {{ ($user->count / $maxUserCount) * 100 }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada data aktivitas</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Widget 2: Modul Teraktif --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <span>📊</span> Modul Teraktif (7 Hari)
                    </h4>
                </div>

                <div class="space-y-3.5 mt-3.5">
                    @php
                        $maxModuleCount = collect($topModules)->max('count') ?: 1;
                        $moduleGradients = [
                            'Shipment' => 'from-blue-600 to-cyan-500',
                            'Accounting' => 'from-emerald-600 to-teal-500',
                            'Auth' => 'from-purple-600 to-indigo-600',
                            'Invoice' => 'from-violet-600 to-purple-500',
                            'Quotation' => 'from-amber-500 to-orange-500',
                            'Customer' => 'from-teal-600 to-emerald-500',
                            'Vendor' => 'from-cyan-600 to-blue-600',
                        ];
                    @endphp
                    @forelse($topModules as $mod)
                    @php
                        $barGrad = $moduleGradients[$mod->module] ?? 'from-slate-700 to-slate-800';
                    @endphp
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs font-bold text-slate-800">
                            <span>{{ $mod->module }}</span>
                            <span class="font-black text-slate-900 font-mono">{{ $mod->count }} logs</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/60">
                            <div class="bg-gradient-to-r {{ $barGrad }} h-2 rounded-full transition-all duration-500" style="width: {{ ($mod->count / $maxModuleCount) * 100 }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada data modul</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Widget 3: Security & Compliance Shield (Premium Dark Card) --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-[#0F2C59] to-slate-900 rounded-2xl p-5 text-white shadow-md border border-slate-700 space-y-3.5 flex flex-col justify-between">
            <div class="space-y-3.5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-400/20 text-amber-400 flex items-center justify-center border border-amber-400/40">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-amber-400">Forensic Integrity</h4>
                        <p class="text-[10px] text-slate-300">COSO &amp; ISO 27001 Standard</p>
                    </div>
                </div>
                
                <p class="text-xs text-slate-200 leading-relaxed">
                    Setiap mutasi finansial, eskalasi hak akses, dan perubahan data sensitif dicatat secara permanen bersama identitas IP, browser, serta parameter <span class="text-emerald-300 font-mono bg-emerald-950/60 px-1 py-0.5 rounded border border-emerald-700/50">before vs after</span>.
                </p>
            </div>

            <div class="pt-3 border-t border-slate-800 space-y-2 text-[11px]">
                <div class="flex justify-between items-center text-slate-200">
                    <span>Immutable Audit Trail</span>
                    <span class="text-emerald-400 font-bold flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Active
                    </span>
                </div>
                <div class="flex justify-between items-center text-slate-200">
                    <span>Anti-Tamper Signature</span>
                    <span class="text-cyan-300 font-bold">Enabled</span>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL: FORENSIC ACTIVITY DETAIL (BEFORE / AFTER COMPARISON) --}}
    @if($showDetailModal && $selectedLog)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4 overflow-y-auto" wire:click="closeDetailModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden border border-slate-200 my-8 animate-in fade-in zoom-in duration-200" wire:click.stop>
            
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-slate-900 via-[#0F2C59] to-slate-900 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/10 text-cyan-300 flex items-center justify-center border border-white/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Forensic Log Inspector</h3>
                        <p class="text-xs text-slate-300">ID Log #{{ $selectedLog->id }} &bull; {{ $selectedLog->created_at->format('d M Y, H:i:s') }}</p>
                    </div>
                </div>
                <button wire:click="closeDetailModal" class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                
                {{-- Meta Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Pelaku (User)</p>
                        <p class="font-bold text-slate-900 text-sm mt-0.5">{{ $selectedLog->user_name }}</p>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 text-slate-700 uppercase mt-1">
                            {{ str_replace('_', ' ', $selectedLog->role) }}
                        </span>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Modul &amp; Aksi</p>
                        <p class="font-bold text-indigo-700 text-sm mt-0.5">{{ $selectedLog->module }}</p>
                        <p class="text-xs font-bold text-slate-800 mt-1 font-mono">{{ $selectedLog->action }}</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">No. Referensi</p>
                        <p class="font-mono font-bold text-blue-700 text-sm mt-0.5">{{ $selectedLog->target_ref ?: '-' }}</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">IP Address</p>
                        <p class="font-mono text-xs font-bold text-slate-900 mt-0.5">{{ $selectedLog->ip_address }}</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 sm:col-span-2">
                        <p class="text-[11px] font-bold text-slate-500 uppercase">Perangkat (User Agent)</p>
                        <p class="font-mono text-[11px] text-slate-700 break-all leading-tight mt-0.5">{{ $selectedLog->user_agent ?: '-' }}</p>
                    </div>
                </div>

                {{-- Deskripsi Aktivitas --}}
                <div class="bg-indigo-50/80 border border-indigo-200 rounded-xl p-4">
                    <p class="text-[11px] font-bold text-indigo-900 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <span>💬</span> Uraian Aktivitas
                    </p>
                    <p class="text-sm text-slate-900 leading-relaxed font-medium">
                        {{ $selectedLog->description ?: 'Tidak ada keterangan tambahan.' }}
                    </p>
                </div>

                {{-- Before vs After Comparison --}}
                @if($selectedLog->hasComparison())
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <span>🔄</span> Perbandingan Perubahan (Before vs After)
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        {{-- Before --}}
                        <div class="bg-rose-50/80 border border-rose-200 rounded-xl p-4 space-y-2.5">
                            <p class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> NILAI SEBELUMNYA (BEFORE)
                            </p>
                            <div class="space-y-2 text-xs">
                                @foreach($selectedLog->changes as $field => $change)
                                <div class="bg-white rounded-lg p-2.5 border border-rose-200 shadow-2xs">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase">{{ ucfirst(str_replace('_', ' ', $field)) }}</p>
                                    <p class="font-mono text-rose-900 break-all mt-0.5">{{ is_array($change['old']) ? json_encode($change['old']) : ($change['old'] ?? 'null') }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- After --}}
                        <div class="bg-emerald-50/80 border border-emerald-200 rounded-xl p-4 space-y-2.5">
                            <p class="text-xs font-bold text-emerald-800 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> NILAI SETELAHNYA (AFTER)
                            </p>
                            <div class="space-y-2 text-xs">
                                @foreach($selectedLog->changes as $field => $change)
                                <div class="bg-white rounded-lg p-2.5 border border-emerald-200 shadow-2xs">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase">{{ ucfirst(str_replace('_', ' ', $field)) }}</p>
                                    <p class="font-mono font-bold text-emerald-950 break-all mt-0.5">{{ is_array($change['new']) ? json_encode($change['new']) : ($change['new'] ?? 'null') }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @else
                    @php
                        $valuesToShow = $selectedLog->new_values ?: $selectedLog->old_values;
                        $isCreated = !empty($selectedLog->new_values);
                    @endphp
                    @if($valuesToShow)
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                            <span>📋</span> {{ $isCreated ? 'Data Yang Dibuat (Created Payload)' : 'Data Yang Dihapus (Deleted Payload)' }}
                        </h4>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 max-h-60 overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                                @foreach($valuesToShow as $key => $val)
                                <div class="bg-white rounded-lg p-2.5 border border-slate-200 shadow-2xs">
                                    <p class="text-[10px] font-bold text-slate-500 uppercase">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                                    <p class="font-mono font-bold text-slate-900 break-all mt-0.5">{{ is_array($val) ? json_encode($val) : ($val ?? 'null') }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                @endif

            </div>

            {{-- Modal Footer --}}
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end">
                <button wire:click="closeDetailModal" class="px-5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition shadow-sm">
                    Tutup
                </button>
            </div>

        </div>
    </div>
    @endif

</div>

