<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-500/20 border border-green-500/40 text-green-200 rounded-xl text-sm flex items-center gap-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Header & Year Filter --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <span class="text-3xl">⚖️</span>
                <div>
                    <h1 class="text-2xl font-bold text-gray-100 flex items-center gap-2">
                        Pusat Perpajakan &amp; Catatan Pajak
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 font-mono">
                            Tahun {{ $selectedYear }}
                        </span>
                    </h1>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Monitoring PPN, PPh 23, Ekualisasi Omzet Jasa vs Talangan, Kurs KMK &amp; Catatan Audit Kepatuhan
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-xl px-3 py-1.5 text-xs text-gray-300">
                <span class="text-gray-400 font-semibold uppercase text-[10px]">Tahun Pajak:</span>
                <select wire:model.live="selectedYear" class="bg-transparent text-white font-bold text-sm focus:outline-none cursor-pointer">
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" class="bg-gray-800 text-white">{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            @if($this->canCreate())
            <button wire:click="openCreate"
                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-blue-500/20 transition flex items-center gap-2">
                <span>➕</span>
                <span>Tambah Catatan Pajak</span>
            </button>
            @endif
        </div>
    </div>

    {{-- Top KPI Metrics Cards (Synced for Admin & Konsultan Pajak) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: PPN Keluaran --}}
        <div class="bg-gray-800/90 border border-gray-700/80 rounded-2xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                <span class="font-semibold uppercase tracking-wider text-[10px]">PPN Keluaran (11%)</span>
                <span class="text-base">🧾</span>
            </div>
            <div class="text-xl font-black text-emerald-400 font-mono">
                Rp {{ number_format($metrics['total_ppn'], 0, ',', '.') }}
            </div>
            <div class="mt-2.5 flex items-center gap-2 text-[11px]">
                <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 font-medium">
                    ✓ {{ $metrics['fp_issued'] }} Faktur Terbit
                </span>
                @if($metrics['fp_pending'] > 0)
                <span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/20 font-medium animate-pulse">
                    ⚠️ {{ $metrics['fp_pending'] }} Pending
                </span>
                @else
                <span class="text-gray-500">Semua lengkap</span>
                @endif
            </div>
        </div>

        {{-- Card 2: PPh 23 --}}
        <div class="bg-gray-800/90 border border-gray-700/80 rounded-2xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                <span class="font-semibold uppercase tracking-wider text-[10px]">PPh 23 Dipotong Customer (2%)</span>
                <span class="text-base">💼</span>
            </div>
            <div class="text-xl font-black text-blue-400 font-mono">
                Rp {{ number_format($metrics['total_pph'], 0, ',', '.') }}
            </div>
            <div class="mt-2.5 flex items-center gap-2 text-[11px]">
                <span class="px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-300 border border-blue-500/20 font-medium">
                    ✓ {{ $metrics['bupot_received'] }} Bupot Diterima
                </span>
                @if($metrics['bupot_pending'] > 0)
                <span class="px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-300 border border-rose-500/20 font-medium">
                    ⏳ {{ $metrics['bupot_pending'] }} Belum Ada
                </span>
                @else
                <span class="text-gray-500">Semua lengkap</span>
                @endif
            </div>
        </div>

        {{-- Card 3: Ekualisasi Omzet --}}
        <div class="bg-gray-800/90 border border-gray-700/80 rounded-2xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                <span class="font-semibold uppercase tracking-wider text-[10px]">Ekualisasi Omzet (DPP vs Talangan)</span>
                <span class="text-base">📊</span>
            </div>
            <div class="text-sm font-bold text-gray-200">
                <span class="text-gray-400 text-xs font-normal">DPP Jasa:</span>
                <span class="font-mono text-purple-300">Rp {{ number_format($metrics['total_service'], 0, ',', '.') }}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1">
                <span>Talangan/Reimb:</span>
                <span class="font-mono text-amber-300 font-semibold">Rp {{ number_format($metrics['total_reimbursement'], 0, ',', '.') }}</span>
            </div>
            <div class="text-[10px] text-gray-500 mt-1 italic">
                *Talangan murni non-objek PPh 23
            </div>
        </div>

        {{-- Card 4: Kurs KMK & Status Audit --}}
        <div class="bg-gray-800/90 border border-gray-700/80 rounded-2xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                <span class="font-semibold uppercase tracking-wider text-[10px]">Kurs Pajak KMK &amp; Catatan</span>
                <span class="text-base">🌐</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-300">USD KMK:</span>
                <span class="font-mono font-bold text-amber-300">
                    {{ $metrics['usd_rate'] ? 'Rp ' . number_format($metrics['usd_rate'], 0, ',', '.') : '—' }}
                </span>
            </div>
            <div class="flex items-center justify-between text-xs mt-1">
                <span class="text-gray-300">SGD KMK:</span>
                <span class="font-mono font-bold text-blue-300">
                    {{ $metrics['sgd_rate'] ? 'Rp ' . number_format($metrics['sgd_rate'], 0, ',', '.') : '—' }}
                </span>
            </div>
            <div class="mt-2 text-[11px]">
                @if($metrics['unresolved_notes'] > 0)
                <span class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold">
                    🔴 {{ $metrics['unresolved_notes'] }} Catatan Butuh Tindakan
                </span>
                @else
                <span class="px-2 py-0.5 rounded-md bg-green-500/10 text-green-300 border border-green-500/20">
                    ✅ Semua Catatan Selesai
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-700/80 pb-3 mb-6">
        <button wire:click="setTab('notes')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'notes' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20' : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-750' }}">
            <span>🗒️ Catatan &amp; Audit Pajak</span>
            @if($metrics['unresolved_notes'] > 0)
            <span class="px-1.5 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-black">
                {{ $metrics['unresolved_notes'] }}
            </span>
            @endif
        </button>

        <button wire:click="setTab('compliance')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'compliance' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20' : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-750' }}">
            <span>📋 Monitoring Faktur &amp; Bukti Potong</span>
            @if($metrics['fp_pending'] + $metrics['bupot_pending'] > 0)
            <span class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black">
                {{ $metrics['fp_pending'] + $metrics['bupot_pending'] }}
            </span>
            @endif
        </button>

        <button wire:click="setTab('equalization')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'equalization' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20' : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-750' }}">
            <span>📊 Rekap Ekualisasi Omzet (DPP)</span>
        </button>

        <button wire:click="setTab('rates')"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'rates' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 ring-1 ring-white/20' : 'bg-gray-800/80 text-gray-400 hover:text-white hover:bg-gray-750' }}">
            <span>🌐 Kurs Pajak KMK</span>
        </button>
    </div>

    {{-- TAB 1: CATATAN PAJAK --}}
    @if($activeTab === 'notes')
    <div class="bg-gray-800 rounded-2xl border border-gray-700/80 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-gray-700/80 flex items-center justify-between bg-gray-750/50">
            <h3 class="text-sm font-bold text-gray-200 flex items-center gap-2">
                <span>📝 Daftar Catatan &amp; Rekomendasi Konsultan Pajak</span>
            </h3>
            <span class="text-xs text-gray-400">Total: {{ $notes->total() }} catatan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="bg-gray-700/80 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-600/50">
                        <th class="px-3 py-3 text-center w-12">Status</th>
                        <th class="px-4 py-3 text-left">Periode</th>
                        <th class="px-4 py-3 text-left">Jenis Pajak</th>
                        <th class="px-4 py-3 text-left">Referensi Invoice</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-left">Catatan &amp; Pembahasan</th>
                        <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                        <th class="px-4 py-3 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/60">
                    @forelse($notes as $note)
                    @php
                        $attachCount = count($note->attachments ?? []);
                    @endphp
                    <tr class="hover:bg-gray-750/60 transition-colors {{ $note->is_resolved ? 'opacity-65' : '' }}">
                        {{-- Status toggle --}}
                        <td class="px-3 py-3 text-center">
                            @if($this->canEdit($note))
                            <button wire:click="toggleResolved({{ $note->id }})"
                                title="{{ $note->is_resolved ? 'Tandai Belum Selesai' : 'Tandai Selesai' }}"
                                class="text-lg leading-none transition-transform hover:scale-125">
                                {{ $note->is_resolved ? '✅' : '🔴' }}
                            </button>
                            @else
                                <span class="text-lg">{{ $note->is_resolved ? '✅' : '🔴' }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 font-mono font-medium text-blue-300">{{ $note->periode }}</td>

                        {{-- Jenis Pajak --}}
                        <td class="px-4 py-3">
                            @if($note->jenis_pajak)
                                <span class="px-2 py-1 bg-yellow-900/50 border border-yellow-700 rounded-lg text-xs text-yellow-300 font-semibold">
                                    {{ $note->jenis_pajak }}
                                </span>
                            @else
                                <span class="text-gray-500 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Invoice --}}
                        <td class="px-4 py-3">
                            @if($note->invoice)
                                <a href="{{ route('admin.invoices.index', ['search' => $note->invoice->invoice_number]) }}" target="_blank"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-900/40 border border-emerald-700/60 rounded-md text-xs text-emerald-300 font-mono hover:underline">
                                    🧾 {{ $note->invoice->invoice_number }} ↗
                                </a>
                                @if($note->invoice->customer)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $note->invoice->customer->company_name }}</div>
                                @endif
                            @else
                                <span class="text-gray-500 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Nominal --}}
                        <td class="px-4 py-3 text-right font-mono text-xs">
                            @if($note->nominal !== null)
                                <span class="text-gray-200 font-semibold">Rp {{ number_format($note->nominal, 0, ',', '.') }}</span>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </td>

                        {{-- Catatan + lampiran --}}
                        <td class="px-4 py-3 max-w-sm">
                            <div class="text-gray-200 leading-relaxed">{{ $note->catatan }}</div>
                            @if($note->is_resolved && $note->resolved_at)
                                <div class="text-[11px] text-green-400 mt-1 font-medium">✓ Selesai pada {{ $note->resolved_at->format('d M Y H:i') }}</div>
                            @endif
                            @if($attachCount > 0)
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($note->attachments as $path)
                                    <a href="{{ Storage::disk('public')->url($path) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-900/40 border border-blue-700/60 text-blue-300 rounded text-xs hover:bg-blue-800/50 transition">
                                        📎 {{ basename($path) }}
                                    </a>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        {{-- Dibuat Oleh --}}
                        <td class="px-4 py-3 text-xs text-gray-400">
                            <div class="font-medium text-gray-300">{{ $note->user->name ?? '—' }}</div>
                            <div class="text-[10px] text-gray-500">{{ $note->created_at->format('d/m/Y H:i') }}</div>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex items-center gap-1">
                                @if($this->canEdit($note))
                                <button wire:click="openEdit({{ $note->id }})"
                                    class="px-2 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-blue-300 rounded transition"
                                    title="Edit Catatan">
                                    ✏️
                                </button>
                                @endif
                                @if($this->canDelete())
                                <button wire:click="confirmDelete({{ $note->id }})"
                                    class="px-2 py-1 text-xs bg-red-900/40 hover:bg-red-800 text-red-300 rounded transition"
                                    title="Hapus Catatan">
                                    🗑️
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <div class="text-3xl mb-2">📋</div>
                            <p class="font-medium">Belum ada catatan pajak untuk periode ini.</p>
                            <p class="text-xs text-gray-500 mt-1">Konsultan pajak atau finance dapat menambahkan catatan untuk koordinasi pelaporan SPT.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notes->hasPages())
        <div class="p-4 border-t border-gray-700/80 bg-gray-750/30">
            {{ $notes->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- TAB 2: MONITORING FAKTUR PAJAK & BUKTI POTONG --}}
    @if($activeTab === 'compliance' && $complianceInvoices)
    <div class="bg-gray-800 rounded-2xl border border-gray-700/80 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-gray-700/80 bg-gray-750/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase">Filter Status:</span>
                <button wire:click="$set('complianceFilter', 'all')"
                    class="px-3 py-1 rounded-lg text-xs font-medium transition {{ $complianceFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    Semua Berpajak
                </button>
                <button wire:click="$set('complianceFilter', 'missing_fp')"
                    class="px-3 py-1 rounded-lg text-xs font-medium transition {{ $complianceFilter === 'missing_fp' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    ⚠️ Faktur Pajak Belum Terbit ({{ $metrics['fp_pending'] }})
                </button>
                <button wire:click="$set('complianceFilter', 'missing_bupot')"
                    class="px-3 py-1 rounded-lg text-xs font-medium transition {{ $complianceFilter === 'missing_bupot' ? 'bg-rose-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    ⏳ Bukti Potong PPh 23 Belum Ada ({{ $metrics['bupot_pending'] }})
                </button>
                <button wire:click="$set('complianceFilter', 'complete')"
                    class="px-3 py-1 rounded-lg text-xs font-medium transition {{ $complianceFilter === 'complete' ? 'bg-emerald-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    ✓ Lengkap
                </button>
            </div>

            <div class="relative w-full md:w-64">
                <input type="text" wire:model.live.debounce.300ms="complianceSearch" placeholder="Cari Inv / No FP / Bupot..."
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-1.5 text-xs text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="bg-gray-700/80 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-600/50">
                        <th class="px-4 py-3 text-left">No. Invoice &amp; Tgl</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-right">DPP Jasa</th>
                        <th class="px-4 py-3 text-right">Reimbursement</th>
                        <th class="px-4 py-3 text-left">PPN (11%) &amp; Faktur Pajak</th>
                        <th class="px-4 py-3 text-left">PPh 23 (2%) &amp; Bukti Potong</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/60">
                    @forelse($complianceInvoices as $inv)
                    <tr class="hover:bg-gray-750/60 transition-colors">
                        {{-- No Invoice & Tgl --}}
                        <td class="px-4 py-3">
                            <span class="font-mono font-bold text-blue-300">{{ $inv->invoice_number }}</span>
                            <div class="text-xs text-gray-500">{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '—' }}</div>
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase mt-0.5 {{ $inv->status === 'paid' ? 'bg-green-900/50 text-green-300' : 'bg-gray-700 text-gray-400' }}">
                                {{ $inv->status }}
                            </span>
                        </td>

                        {{-- Customer --}}
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-200">{{ $inv->customer->company_name ?? '—' }}</div>
                        </td>

                        {{-- DPP Jasa --}}
                        <td class="px-4 py-3 text-right font-mono text-xs text-purple-300">
                            Rp {{ number_format($inv->service_total, 0, ',', '.') }}
                        </td>

                        {{-- Reimbursement --}}
                        <td class="px-4 py-3 text-right font-mono text-xs text-amber-300">
                            Rp {{ number_format($inv->reimbursement_total, 0, ',', '.') }}
                        </td>

                        {{-- PPN & Faktur Pajak --}}
                        <td class="px-4 py-3">
                            @if($inv->tax_amount > 0)
                                <div class="text-xs font-mono font-bold text-emerald-300">
                                    Rp {{ number_format($inv->tax_amount, 0, ',', '.') }}
                                </div>
                                @if($inv->faktur_pajak_path)
                                    <div class="mt-1 flex items-center gap-1">
                                        <a href="{{ Storage::disk('public')->url($inv->faktur_pajak_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-900/40 border border-emerald-600/50 text-emerald-300 rounded text-xs hover:bg-emerald-800/40 font-mono">
                                            ✓ FP: {{ $inv->faktur_pajak_number ?: 'Lihat PDF' }} ↗
                                        </a>
                                    </div>
                                @else
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-amber-500/10 border border-amber-500/30 text-amber-300 rounded text-[11px] font-medium">
                                        ⚠️ Faktur Pajak Belum Ada
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-500 text-xs">— (Non PPN)</span>
                            @endif
                        </td>

                        {{-- PPh 23 & Bukti Potong --}}
                        <td class="px-4 py-3">
                            @if($inv->pph_amount > 0)
                                <div class="text-xs font-mono font-bold text-blue-300">
                                    Rp {{ number_format($inv->pph_amount, 0, ',', '.') }}
                                </div>
                                @if($inv->bukti_potong_path)
                                    <div class="mt-1 flex items-center gap-1">
                                        <a href="{{ Storage::disk('public')->url($inv->bukti_potong_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-900/40 border border-blue-600/50 text-blue-300 rounded text-xs hover:bg-blue-800/40 font-mono">
                                            ✓ Bupot: {{ $inv->bukti_potong_number ?: 'Lihat PDF' }} ↗
                                        </a>
                                    </div>
                                @else
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-rose-500/10 border border-rose-500/30 text-rose-300 rounded text-[11px] font-medium">
                                        ⏳ Bupot Belum Diterima
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-500 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.invoices.index', ['search' => $inv->invoice_number]) }}" target="_blank"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-700 hover:bg-gray-600 text-blue-300 rounded-lg text-xs font-semibold transition">
                                Kelola ↗
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                            Tidak ditemukan invoice yang sesuai dengan filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complianceInvoices->hasPages())
        <div class="p-4 border-t border-gray-700/80 bg-gray-750/30">
            {{ $complianceInvoices->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- TAB 3: REKAP EKUALISASI OMZET (DPP JASA VS TALANGAN) --}}
    @if($activeTab === 'equalization' && $equalizationData)
    <div class="space-y-6">
        <div class="bg-gray-800 rounded-2xl border border-gray-700/80 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-gray-700/80 bg-gray-750/50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-200">
                        📊 Ekualisasi Omzet Freight Forwarding &amp; Pajak (Tahun {{ $selectedYear }})
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Memisahkan DPP Imbalan Jasa yang terutang PPh 23 / PPN dengan Talangan Reimbursement Murni
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-gray-300">
                    <thead>
                        <tr class="bg-gray-700/80 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-600/50">
                            <th class="px-4 py-3 text-left">Masa Pajak</th>
                            <th class="px-4 py-3 text-center">Jml Inv</th>
                            <th class="px-4 py-3 text-right">DPP Jasa (Objek PPh 23)</th>
                            <th class="px-4 py-3 text-right">Reimbursement (Non-DPP)</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                            <th class="px-4 py-3 text-right">PPN (11%)</th>
                            <th class="px-4 py-3 text-right">PPh 23 (2%)</th>
                            <th class="px-4 py-3 text-right">Grand Total Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/60 font-mono text-xs">
                        @php
                            $sumCount = 0;
                            $sumDpp = 0;
                            $sumReimb = 0;
                            $sumSub = 0;
                            $sumPpn = 0;
                            $sumPph = 0;
                            $sumGrand = 0;
                        @endphp
                        @foreach($equalizationData as $row)
                        @php
                            $sumCount += $row['count'];
                            $sumDpp += $row['dpp_jasa'];
                            $sumReimb += $row['reimbursement'];
                            $sumSub += $row['subtotal'];
                            $sumPpn += $row['ppn'];
                            $sumPph += $row['pph23'];
                            $sumGrand += $row['grand_total'];
                        @endphp
                        <tr class="hover:bg-gray-750/50 {{ $row['count'] > 0 ? '' : 'text-gray-600' }}">
                            <td class="px-4 py-2.5 font-sans font-medium text-gray-200">
                                {{ $row['month_name'] }}
                            </td>
                            <td class="px-4 py-2.5 text-center font-sans">
                                {{ $row['count'] ?: '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-purple-300">
                                {{ $row['dpp_jasa'] ? number_format($row['dpp_jasa'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-amber-300">
                                {{ $row['reimbursement'] ? number_format($row['reimbursement'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-300">
                                {{ $row['subtotal'] ? number_format($row['subtotal'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-emerald-300 font-bold">
                                {{ $row['ppn'] ? number_format($row['ppn'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-blue-300 font-bold">
                                {{ $row['pph23'] ? number_format($row['pph23'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-right text-white font-bold">
                                {{ $row['grand_total'] ? number_format($row['grand_total'], 0, ',', '.') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-750/80 font-mono text-xs font-bold border-t-2 border-gray-600">
                        <tr>
                            <td class="px-4 py-3 font-sans text-gray-100">TOTAL TAHUN {{ $selectedYear }}</td>
                            <td class="px-4 py-3 text-center text-gray-100 font-sans">{{ $sumCount }}</td>
                            <td class="px-4 py-3 text-right text-purple-300">Rp {{ number_format($sumDpp, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-amber-300">Rp {{ number_format($sumReimb, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-gray-200">Rp {{ number_format($sumSub, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-emerald-300">Rp {{ number_format($sumPpn, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-blue-300">Rp {{ number_format($sumPph, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-white">Rp {{ number_format($sumGrand, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Legal Reference Card --}}
        <div class="p-4 bg-blue-950/40 border border-blue-800/50 rounded-2xl text-xs text-blue-200/90 leading-relaxed flex items-start gap-3">
            <span class="text-2xl mt-0.5">ℹ️</span>
            <div>
                <h4 class="font-bold text-blue-100 mb-1">Pedoman Fiskal Freight Forwarding &amp; Ekualisasi Pajak M2B</h4>
                <p>
                    Berdasarkan <strong>PMK 71/PMK.03/2022</strong> dan regulasi PPh Pasal 23 DJP, dalam kegiatan freight forwarding terdapat pemisahan tegas antara <strong>Imbalan Jasa Manajemen/Handling (DPP)</strong> dengan <strong>Biaya Talangan (Reimbursement/At Cost)</strong> seperti ongkos angkut pelayaran, demurrage, dan sewa penumpukan TPS. Tabel di atas mempermudah Konsultan Pajak dalam mencocokkan SPT Masa PPN 1111, SPT Masa PPh 23, serta SPT Tahunan Badan 1771 tanpa adanya selisih ekualisasi omzet.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 4: KURS PAJAK KEMENKEU (KMK) --}}
    @if($activeTab === 'rates' && $exchangeRates)
    <div class="bg-gray-800 rounded-2xl border border-gray-700/80 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-gray-700/80 bg-gray-750/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-gray-200">
                    🌐 Kurs Keputusan Menteri Keuangan (KMK) Mingguan
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Dasar konversi nilai pabean, perhitungan Bea Masuk, PPN Impor &amp; PPh 22 Impor
                </p>
            </div>

            <div class="w-full sm:w-64">
                <input type="text" wire:model.live.debounce.300ms="rateSearch" placeholder="Cari mata uang (USD, SGD, EUR)..."
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-1.5 text-xs text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="bg-gray-700/80 text-gray-400 uppercase text-xs tracking-wider border-b border-gray-600/50">
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Mata Uang</th>
                        <th class="px-4 py-3 text-right">Nilai Kurs Pajak</th>
                        <th class="px-4 py-3 text-left">Mulai Berlaku</th>
                        <th class="px-4 py-3 text-left">Berlaku Sampai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/60 font-mono text-xs">
                    @forelse($exchangeRates as $rate)
                    <tr class="hover:bg-gray-750/50 transition-colors">
                        <td class="px-4 py-3 font-bold text-blue-300 font-sans">
                            <span class="px-2 py-0.5 bg-blue-900/40 border border-blue-700/50 rounded">
                                {{ $rate->currency_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-sans text-gray-200">
                            {{ trim($rate->currency_name) }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-300 text-sm">
                            Rp {{ number_format($rate->rate, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-gray-400 font-sans">
                            {{ $rate->valid_from ? $rate->valid_from->format('d M Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-400 font-sans">
                            {{ $rate->valid_until ? $rate->valid_until->format('d M Y') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-sans">
                            Tidak ditemukan data kurs KMK.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($exchangeRates->hasPages())
        <div class="p-4 border-t border-gray-700/80 bg-gray-750/30">
            {{ $exchangeRates->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- Create / Edit Modal (Preserved & Enhanced) --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs">
        <div class="bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] flex flex-col border border-gray-700">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 flex-shrink-0">
                <h2 class="text-lg font-semibold text-gray-100 flex items-center gap-2">
                    <span>{{ $isEditing ? '✏️ Edit Catatan Pajak' : '➕ Tambah Catatan Pajak' }}</span>
                </h2>
                <button wire:click="closeModal" class="text-gray-400 hover:text-white transition-colors text-xl">&times;</button>
            </div>

            {{-- Scrollable body --}}
            <form wire:submit.prevent="save" class="px-6 py-5 space-y-4 overflow-y-auto flex-1">

                {{-- Periode --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">Masa Pajak / Periode (YYYY-MM)</label>
                    <select wire:model="periode"
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($periodeOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('periode') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Jenis Pajak + Nominal --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                            Jenis Pajak <span class="text-gray-500 font-normal">(opsional)</span>
                        </label>
                        <select wire:model="jenis_pajak"
                            class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Pilih jenis —</option>
                            @foreach($jenisPajakList as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis_pajak') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                            Nominal (Rp) <span class="text-gray-500 font-normal">(opsional)</span>
                        </label>
                        <input wire:model="nominal" type="number" min="0" step="1" placeholder="0"
                            class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('nominal') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Referensi Invoice --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                        Referensi Invoice <span class="text-gray-500 font-normal">(opsional)</span>
                    </label>
                    <input wire:model.live.debounce.300ms="invoiceSearch"
                        type="text"
                        placeholder="Cari nomor invoice / nama customer..."
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                    <select wire:model="invoice_id"
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tidak terkait invoice spesifik —</option>
                        @foreach($invoiceOptions as $inv)
                            <option value="{{ $inv['id'] }}">{{ $inv['label'] }}</option>
                        @endforeach
                    </select>
                    @error('invoice_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                        Catatan, Rekomendasi, atau Temuan Fiskal
                    </label>
                    <textarea wire:model="catatan" rows="4"
                        placeholder="Tuliskan catatan kepatuhan pajak, koreksi fiskal, atau instruksi tindak lanjut..."
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('catatan') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Attachments --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                        Lampiran Dokumen <span class="text-gray-500 font-normal">(PDF, XLS, Gambar · maks 10MB)</span>
                    </label>
                    <input type="file" wire:model="newAttachments" multiple
                        class="w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                    @error('newAttachments.*') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

                    {{-- Existing attachments list --}}
                    @if(!empty($existingAttachments))
                    <div class="mt-2 space-y-1">
                        <p class="text-xs text-gray-500 mb-1">Lampiran tersimpan:</p>
                        @foreach($existingAttachments as $i => $path)
                        <div class="flex items-center justify-between px-2 py-1 bg-gray-700 rounded text-xs text-gray-300">
                            <span class="truncate">📎 {{ basename($path) }}</span>
                            <button type="button" wire:click="removeExistingAttachment({{ $i }})"
                                class="text-red-400 hover:text-red-300 ml-2 font-bold">&times;</button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-gray-700">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-gray-600 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        class="px-5 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-500 text-white rounded-lg shadow-md transition-colors">
                        <span wire:loading.remove>{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Catatan' }}</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs">
        <div class="bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center border border-gray-700">
            <div class="text-4xl mb-3">🗑️</div>
            <h3 class="text-lg font-semibold text-gray-100 mb-2">Hapus Catatan Pajak?</h3>
            <p class="text-sm text-gray-400 mb-6">Semua lampiran terkait akan ikut terhapus permanen dari sistem.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete"
                    class="px-4 py-2 text-sm border border-gray-600 text-gray-300 hover:text-white rounded-lg transition-colors">
                    Batal
                </button>
                <button wire:click="deleteNote"
                    class="px-4 py-2 text-sm bg-red-600 hover:bg-red-500 text-white font-semibold rounded-lg transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
