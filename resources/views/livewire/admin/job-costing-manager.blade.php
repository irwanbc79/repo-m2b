<div class="space-y-6">
    @section('header', 'Job Costing Analysis')

    {{-- ALERT --}}
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-black text-gray-800">{{ $stats["total_shipments"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Total Shipments</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 shadow-sm border border-blue-100 text-center">
            <p class="text-lg font-black text-blue-600">{{ number_format($stats["total_revenue"] ?? 0, 0, ",", ".") }}</p>
            <p class="text-xs text-gray-500">Total Revenue</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4 shadow-sm border border-red-100 text-center">
            <p class="text-lg font-black text-red-600">{{ number_format($stats["total_cost"] ?? 0, 0, ",", ".") }}</p>
            <p class="text-xs text-gray-500">Total Cost</p>
        </div>
        <div class="bg-green-50 rounded-xl p-4 shadow-sm border border-green-100 text-center">
            <p class="text-lg font-black {{ ($stats["total_profit"] ?? 0) >= 0 ? "text-green-600" : "text-red-600" }}">{{ number_format($stats["total_profit"] ?? 0, 0, ",", ".") }}</p>
            <p class="text-xs text-gray-500">Total Profit</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-4 shadow-sm border border-indigo-100 text-center">
            <p class="text-2xl font-black text-indigo-600">{{ number_format($stats["avg_margin"] ?? 0, 1) }}%</p>
            <p class="text-xs text-gray-500">Avg Margin</p>
        </div>
        <div class="bg-orange-50 rounded-xl p-4 shadow-sm border border-orange-100 text-center">
            <p class="text-2xl font-black text-orange-600">{{ $stats["low_margin"] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Low Margin (&lt;10%)</p>
        </div>
    </div>

    {{-- DASHBOARD TABLE --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-gray-50">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Search --}}
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari No Shipment / Customer..." class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-900 focus:ring-1 focus:ring-blue-900 transition">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                
                {{-- Filter Lane --}}
                <select wire:model.live="filterLane" class="border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Jalur</option>
                    <option value="green">🟩 Jalur Hijau</option>
                    <option value="red">🟥 Jalur Merah</option>
                </select>
            </div>
            
            <div class="flex gap-2">
                {{-- ✅ FIX LINE 65: Safe check untuk total() --}}
                <span class="text-xs font-bold text-gray-500 bg-white border border-gray-200 px-3 py-1.5 rounded-lg shadow-sm">
                    Total: {{ $shipments instanceof \Illuminate\Pagination\LengthAwarePaginator ? $shipments->total() : $shipments->count() }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 font-bold text-gray-600 uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-4 w-1/5">Ref / Jalur</th>
                        <th class="px-6 py-4 w-1/4">Customer / Cargo Info</th>
                        <th class="px-6 py-4 text-right text-blue-800">Total Invoice (Omset)</th>
                        <th class="px-6 py-4 text-right text-red-800">Total Cost (Biaya)</th>
                        <th class="px-6 py-4 text-right">Est. Profit (Margin)</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shipments as $shipment)
                        @php
                            // 1. FINANCIAL LOGIC - dengan safe check
                            $revenue = $shipment->invoices ? $shipment->invoices->sum('grand_total') : 0;
                            $cost = $shipment->jobCosts ? $shipment->jobCosts->sum('amount') : 0;
                            $profit = $revenue - $cost;
                            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
                            
                            // 2. JALUR LOGIC
                            $jalurCode = $shipment->lane_status;
                            
                            if ($jalurCode == 'green') {
                                $jalurText = 'JALUR HIJAU';
                                $jalurBadge = 'bg-green-100 text-green-700 border-green-200';
                            } elseif ($jalurCode == 'red') {
                                $jalurText = 'JALUR MERAH';
                                $jalurBadge = 'bg-red-100 text-red-700 border-red-200';
                            } elseif ($jalurCode == 'yellow') {
                                $jalurText = 'JALUR KUNING';
                                $jalurBadge = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                            } else {
                                $manualJalur = $shipment->customs_lane ?? $shipment->jalur ?? '';
                                if ($manualJalur) {
                                    $jalurText = strtoupper($manualJalur);
                                    if (str_contains(strtolower($manualJalur), 'merah') || str_contains(strtolower($manualJalur), 'red')) {
                                        $jalurBadge = 'bg-red-100 text-red-700 border-red-200';
                                    } elseif (str_contains(strtolower($manualJalur), 'hijau') || str_contains(strtolower($manualJalur), 'green')) {
                                        $jalurBadge = 'bg-green-100 text-green-700 border-green-200';
                                    } else {
                                        $jalurBadge = 'bg-gray-100 text-gray-600 border-gray-200';
                                    }
                                } else {
                                    $jalurText = 'BELUM SET';
                                    $jalurBadge = 'bg-gray-100 text-gray-400 border-gray-200';
                                }
                            }

                            // 3. CARGO INFO LOGIC - dengan safe check
                            $parts = [];
                            if ($shipment->container_mode) $parts[] = $shipment->container_mode;
                            if ($shipment->container_info) $parts[] = "({$shipment->container_info})";
                            
                            $detailParts = [];
                            if (isset($shipment->qty) && $shipment->qty > 0) {
                                $detailParts[] = number_format($shipment->qty) . ' ' . ($shipment->unit ?? 'Pcs');
                            }
                            if (isset($shipment->weight) && $shipment->weight > 0) {
                                $detailParts[] = number_format($shipment->weight) . ' Kg';
                            }
                            
                            if (!empty($detailParts)) {
                                $parts[] = implode(' / ', $detailParts);
                            }
                            
                            if (empty($parts) && ($shipment->description ?? $shipment->goods_name ?? null)) {
                                $parts[] = $shipment->description ?? $shipment->goods_name;
                            }
                            
                            $cargoInfo = !empty($parts) ? implode(' ', $parts) : '-';
                        @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 align-top">
                            <span class="block font-black text-blue-900 text-base mb-1 hover:underline cursor-pointer">{{ $shipment->awb_number }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold uppercase border {{ $jalurBadge }}">
                                {{ $jalurText }}
                            </span>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <div class="font-bold text-gray-800 text-sm mb-1">
                                {{ $shipment->customer->company_name ?? ($shipment->customer->name ?? '-') }}
                            </div>
                            <div class="text-xs text-gray-500 font-medium flex items-start gap-1.5 bg-gray-50 p-1.5 rounded border border-gray-100 w-fit max-w-[250px]">
                                <svg class="w-3.5 h-3.5 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <span class="leading-tight line-clamp-2" title="{{ $cargoInfo }}">
                                    {{ $cargoInfo }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right align-top">
                            <span class="font-mono text-blue-600 font-black text-sm block tracking-tight">{{ number_format($revenue) }}</span>
                            @if($revenue == 0)
                                <span class="text-[10px] text-red-400 italic">Belum Invoice</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right align-top">
                            <span class="font-mono text-red-500 font-black text-sm block tracking-tight">{{ number_format($cost) }}</span>
                            @if($cost == 0)
                                <span class="text-[10px] text-orange-400 italic flex justify-end gap-1 items-center font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Data Kosong
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right align-top">
                            <div class="flex flex-col items-end">
                                <span class="font-black font-mono text-sm tracking-tight {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($profit) }}
                                </span>
                                @php
                                    $marginColor = 'bg-gray-100 text-gray-600';
                                    if ($margin > 20) $marginColor = 'bg-green-100 text-green-800 border-green-200';
                                    elseif ($margin > 0) $marginColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                    elseif ($margin < 0) $marginColor = 'bg-red-100 text-red-800 border-red-200';
                                @endphp
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold mt-1 border {{ $marginColor }}">
                                    {{ number_format($margin, 1) }}%
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center align-top">
                            <button wire:click="manageCosts({{ $shipment->id }})" class="bg-slate-800 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg font-bold shadow-sm transition text-xs flex items-center justify-center mx-auto gap-2 group hover:shadow-md transform hover:-translate-y-0.5">
                                <svg class="w-3.5 h-3.5 text-slate-300 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                <span>Manage Cost</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400 italic bg-gray-50 flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        Belum ada data shipment untuk dianalisa.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t bg-gray-50">
            @if($shipments instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $shipments->links() }}
            @else
                <p class="text-sm text-gray-500 text-center">Showing {{ $shipments->count() }} items</p>
            @endif
        </div>
    </div>
    
    {{-- ============================================= --}}
    {{-- MODAL MANAGE COSTING (UPDATED dengan fitur baru) --}}
    {{-- ============================================= --}}
    @if($isModalOpen && $selectedShipment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm overflow-y-auto" wire:ignore.self>
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl animate-fade-in-up my-8">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">Manage Costing</h3>
                        <p class="text-xs text-gray-500">Shipment: <span class="font-bold text-blue-600">{{ $selectedShipment->awb_number }}</span></p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                
                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6 max-h-[80vh] overflow-y-auto">
                    
                    {{-- KOLOM KIRI: FORM INPUT --}}
                    <div class="lg:col-span-1 bg-slate-50 p-4 rounded-lg border border-slate-200 h-fit">
                        <h4 class="font-bold text-slate-700 mb-3 text-sm uppercase border-b border-slate-200 pb-2">Tambah Biaya Baru</h4>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi Biaya</label>
                                <input type="text" wire:model="description" class="w-full border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="Contoh: Trucking Vendor">
                                @error('description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Vendor (Penerima)</label>
                                <select wire:model="vendor_id" class="w-full border-gray-300 rounded-lg text-sm focus:border-blue-500">
                                    <option value="">-- Pilih Vendor (Opsional) --</option>
                                    @foreach($vendors as $ven)
                                        <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nominal (Rp)</label>
                                <input type="number" wire:model="amount" class="w-full border-gray-300 rounded-lg text-sm font-mono font-bold text-red-600 text-right focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="0">
                                @error('amount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            {{-- HIDDEN: Auto-select Akun (Staf tidak perlu pilih) --}}
                            <input type="hidden" wire:model="coa_id">
                            <input type="hidden" wire:model="credit_account_id">
                            
                            {{-- Info untuk staf --}}
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                <p class="text-[10px] text-gray-500 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Akun biaya akan otomatis dipilih oleh sistem
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Status Pembayaran</label>
                                <input type="hidden" wire:model="status" value="unpaid">
                                <div class="w-full px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg text-sm">
                                    <span class="font-semibold text-yellow-700">📋 Unpaid (Hutang)</span>
                                </div>
                                <p class="text-[10px] text-blue-600 mt-1 flex items-center gap-1 bg-blue-50 p-2 rounded">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    💡 Untuk bayar vendor, gunakan menu <strong>Kasir (Simple)</strong>. Biaya ini akan otomatis lunas setelah dicatat di kasir.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Bukti Bayar / Nota</label>
                                <input type="file" wire:model="payment_proof" class="w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-white file:text-blue-700 hover:file:bg-blue-50 border border-gray-300 rounded-lg">
                                <div wire:loading wire:target="payment_proof" class="text-xs text-blue-500 mt-1 italic">Mengupload...</div>
                                @error('payment_proof') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <button wire:click="saveCost" wire:loading.attr="disabled" class="w-full bg-blue-900 text-white py-2.5 rounded-lg font-bold text-sm hover:bg-blue-800 transition mt-2 shadow-md hover:shadow-lg flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="saveCost">Simpan Biaya</span>
                                <span wire:loading wire:target="saveCost" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- KOLOM KANAN: LIST BIAYA --}}
                    <div class="lg:col-span-2">
                        <div class="flex justify-between items-center mb-3 border-b pb-2">
                            <h4 class="font-bold text-gray-700 text-sm uppercase">Rincian Pengeluaran</h4>
                            <div class="text-red-600 font-black text-lg font-mono tracking-tight bg-red-50 px-3 py-1 rounded">
                                Total: Rp {{ number_format($selectedShipment->jobCosts ? $selectedShipment->jobCosts->sum('amount') : 0) }}
                            </div>
                        </div>

                        <div class="overflow-y-auto max-h-[450px] border rounded-lg shadow-inner bg-slate-50">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-200 text-xs text-gray-600 uppercase sticky top-0 font-bold z-10">
                                    <tr>
                                        <th class="px-4 py-3 border-b border-gray-300">Keterangan / Vendor</th>
                                        <th class="px-4 py-3 border-b border-gray-300 text-right">Jumlah</th>
                                        <th class="px-4 py-3 border-b border-gray-300 text-center">Status</th>
                                        <th class="px-4 py-3 border-b border-gray-300 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($selectedShipment->jobCosts as $cost)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-bold text-gray-800 text-sm">{{ $cost->description }}</div>
                                            @if($cost->vendor)
                                                <div class="text-[11px] text-blue-600 flex items-center gap-1 mt-0.5 bg-blue-50 w-fit px-1.5 rounded">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    {{ $cost->vendor->name }}
                                                </div>
                                            @endif
                                            {{-- NEW: Tampilkan info akun COA --}}
                                            @if($cost->account)
                                                <div class="text-[10px] text-gray-500 mt-0.5">
                                                    Akun: {{ $cost->account->code }} - {{ $cost->account->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-gray-700 text-base align-top">
                                            {{ number_format($cost->amount) }}
                                        </td>
                                        <td class="px-4 py-3 text-center align-top">
                                            <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold {{ $cost->status == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $cost->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center align-top">
                                            <div class="flex items-center justify-center gap-1">
                                                {{-- Preview Button --}}
                                                @if($cost->proof_file)
                                                    <button wire:click="previewProof({{ $cost->id }})" 
                                                        class="text-blue-500 hover:text-blue-700 transition p-1 rounded hover:bg-blue-50" 
                                                        title="Preview Bukti">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    </button>
                                                    {{-- Download Button --}}
                                                    <a href="{{ asset('storage/' . $cost->proof_file) }}" download 
                                                        class="text-teal-500 hover:text-teal-700 transition p-1 rounded hover:bg-teal-50" 
                                                        title="Download Bukti">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </a>
                                                @endif
                                                
                                                {{-- Status Info (Read Only) --}}
                                                @if($cost->status === 'unpaid')
                                                    <a href="{{ route('simple-cashier') }}" 
                                                        class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition p-1 rounded flex items-center gap-1" 
                                                        title="Bayar via Kasir">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                    </a>
                                                @else
                                                    <span class="text-green-500 p-1" title="Sudah Lunas">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </span>
                                                @endif
                                                {{-- Delete Button --}}
                                                <button wire:click="deleteCost({{ $cost->id }})" 
                                                    wire:confirm="Hapus biaya ini? Tindakan tidak dapat dibatalkan!" 
                                                    class="text-gray-400 hover:text-red-600 transition p-1 rounded hover:bg-red-50" 
                                                    title="Hapus Biaya">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="p-8 text-center text-gray-400 italic">
                                        <svg class="w-10 h-10 text-gray-200 mb-2 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                        Belum ada biaya tercatat untuk shipment ini.
                                    </td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================= --}}
    {{-- NEW: MODAL PREVIEW BUKTI BAYAR --}}
    {{-- ============================================= --}}
    @if($showPreviewModal && $previewUrl)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4" wire:ignore.self>
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                {{-- Header --}}
                <div class="px-4 py-3 border-b bg-slate-700 flex justify-between items-center">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Preview Bukti Bayar
                    </h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ $previewUrl }}" download class="px-3 py-1.5 bg-teal-600 text-white text-xs rounded-lg hover:bg-teal-700 transition font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download
                        </a>
                        <a href="{{ $previewUrl }}" target="_blank" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            Tab Baru
                        </a>
                        <button wire:click="closePreview" class="text-white hover:text-gray-200 text-2xl ml-2">&times;</button>
                    </div>
                </div>
                
                {{-- Content --}}
                <div class="p-4 bg-gray-100 overflow-auto" style="max-height: calc(90vh - 60px);">
                    @if($previewType === 'image')
                        <div class="flex justify-center">
                            <img src="{{ $previewUrl }}" alt="Bukti Bayar" class="max-w-full h-auto rounded-lg shadow-lg">
                        </div>
                    @elseif($previewType === 'pdf')
                        <div class="w-full" style="height: 70vh;">
                            <iframe src="{{ $previewUrl }}" class="w-full h-full rounded-lg shadow-lg border-0"></iframe>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p>Format file tidak dapat di-preview</p>
                            <a href="{{ $previewUrl }}" download class="mt-4 inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-bold text-sm">
                                Download File
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>