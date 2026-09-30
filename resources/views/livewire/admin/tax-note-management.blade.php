<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm font-semibold flex items-center gap-2.5 shadow-sm animate-fade-in">
            <span class="text-xl">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Vibrant Hero Header & Year Filter --}}
    <div class="relative bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-800 text-white rounded-3xl p-6 sm:p-7 shadow-xl shadow-indigo-950/20 mb-6 overflow-hidden">
        {{-- Decorative glowing blur background elements --}}
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -bottom-16 w-48 h-48 bg-amber-400/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-3xl shadow-inner shrink-0">
                    ⚖️
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white drop-shadow-sm">
                            Pusat Perpajakan &amp; Catatan Pajak
                        </h1>
                        <span class="px-3 py-1 rounded-full bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider shadow-sm">
                            Tahun {{ $selectedYear }}
                        </span>
                    </div>
                    <p class="text-sm text-blue-100 font-medium mt-1 leading-relaxed max-w-2xl">
                        Monitoring PPN Keluaran, Pemotongan PPh 23, Ekualisasi Omzet Jasa vs Talangan (PMK 71/2022), Kurs KMK, dan Catatan Konsultan Pajak.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0 flex-wrap">
                <div class="flex items-center gap-2 bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl px-3.5 py-2 text-xs shadow-inner">
                    <span class="text-blue-100 font-bold uppercase tracking-wider text-[11px]">Tahun Fiskal:</span>
                    <select wire:model.live="selectedYear" class="bg-transparent text-white font-black text-sm focus:outline-none cursor-pointer">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" class="bg-slate-900 text-white">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                @if($this->canCreate())
                <button wire:click="openCreate"
                    class="px-5 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 text-sm font-extrabold rounded-2xl shadow-lg shadow-amber-500/30 transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-2 cursor-pointer">
                    <span class="text-base">➕</span>
                    <span>Tambah Catatan Pajak</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Top 4 KPI Metric Cards (Vibrant, Rich Gradient & High Contrast) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: PPN Keluaran --}}
        <div class="bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 text-white rounded-3xl p-5 shadow-lg shadow-emerald-900/20 border border-emerald-400/30 relative overflow-hidden group hover:scale-[1.02] transition-all duration-200">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs text-emerald-100 mb-2 font-bold uppercase tracking-wider">
                <span class="text-[11px]">PPN Keluaran (11%)</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-sm shadow-xs">🧾</span>
            </div>
            <div class="text-2xl lg:text-3xl font-black text-white font-mono tracking-tight drop-shadow-sm">
                Rp {{ number_format($metrics['total_ppn'], 0, ',', '.') }}
            </div>
            <div class="mt-3 flex items-center gap-2 text-xs flex-wrap">
                <span class="px-2.5 py-1 rounded-lg bg-emerald-950/40 text-emerald-200 font-bold border border-emerald-400/30 backdrop-blur-xs">
                    ✓ {{ $metrics['fp_issued'] }} Faktur Terbit
                </span>
                @if($metrics['fp_pending'] > 0)
                <span class="px-2.5 py-1 rounded-lg bg-amber-400 text-slate-950 font-black shadow-sm animate-pulse">
                    ⚠️ {{ $metrics['fp_pending'] }} Pending
                </span>
                @else
                <span class="px-2 py-0.5 rounded-lg bg-white/20 text-white font-semibold text-[11px]">
                    Semua Lengkap
                </span>
                @endif
            </div>
        </div>

        {{-- Card 2: PPh 23 --}}
        <div class="bg-gradient-to-br from-blue-600 via-indigo-700 to-blue-800 text-white rounded-3xl p-5 shadow-lg shadow-blue-900/20 border border-blue-400/30 relative overflow-hidden group hover:scale-[1.02] transition-all duration-200">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs text-blue-100 mb-2 font-bold uppercase tracking-wider">
                <span class="text-[11px]">PPh 23 Dipotong Customer (2%)</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-sm shadow-xs">💼</span>
            </div>
            <div class="text-2xl lg:text-3xl font-black text-white font-mono tracking-tight drop-shadow-sm">
                Rp {{ number_format($metrics['total_pph'], 0, ',', '.') }}
            </div>
            <div class="mt-3 flex items-center gap-2 text-xs flex-wrap">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-950/40 text-blue-200 font-bold border border-blue-400/30 backdrop-blur-xs">
                    ✓ {{ $metrics['bupot_received'] }} Bupot Ada
                </span>
                @if($metrics['bupot_pending'] > 0)
                <span class="px-2.5 py-1 rounded-lg bg-rose-400 text-slate-950 font-black shadow-sm">
                    ⏳ {{ $metrics['bupot_pending'] }} Belum Ada
                </span>
                @else
                <span class="px-2 py-0.5 rounded-lg bg-white/20 text-white font-semibold text-[11px]">
                    Semua Lengkap
                </span>
                @endif
            </div>
        </div>

        {{-- Card 3: Ekualisasi Omzet --}}
        <div class="bg-gradient-to-br from-purple-600 via-purple-700 to-fuchsia-800 text-white rounded-3xl p-5 shadow-lg shadow-purple-900/20 border border-purple-400/30 relative overflow-hidden group hover:scale-[1.02] transition-all duration-200">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs text-purple-100 mb-2 font-bold uppercase tracking-wider">
                <span class="text-[11px]">Ekualisasi Omzet (DPP vs Talangan)</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-sm shadow-xs">📊</span>
            </div>
            <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-purple-200 font-semibold">DPP Jasa (Objek PPh 23):</span>
                    <span class="font-mono font-black text-amber-200 text-sm">Rp {{ number_format($metrics['total_service'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-purple-200 font-semibold">Talangan/Reimb:</span>
                    <span class="font-mono font-bold text-white text-sm">Rp {{ number_format($metrics['total_reimbursement'], 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="mt-2.5">
                <span class="inline-block px-2 py-0.5 rounded-md bg-purple-950/50 border border-purple-400/30 text-[10px] text-purple-200 font-medium">
                    *PMK 71/2022: Talangan murni non-DPP
                </span>
            </div>
        </div>

        {{-- Card 4: Kurs KMK & Status Audit --}}
        <div class="bg-gradient-to-br from-amber-500 via-orange-600 to-amber-700 text-white rounded-3xl p-5 shadow-lg shadow-orange-900/20 border border-amber-300/40 relative overflow-hidden group hover:scale-[1.02] transition-all duration-200">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs text-amber-100 mb-2 font-bold uppercase tracking-wider">
                <span class="text-[11px]">Kurs KMK &amp; Status Catatan</span>
                <span class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-sm shadow-xs">🌐</span>
            </div>
            <div class="flex items-center justify-between text-xs bg-black/15 rounded-xl px-2.5 py-1 font-mono">
                <span class="text-amber-100 font-bold">USD:</span>
                <span class="font-black text-white text-sm">
                    {{ $metrics['usd_rate'] ? 'Rp ' . number_format($metrics['usd_rate'], 0, ',', '.') : '—' }}
                </span>
                <span class="text-amber-200 mx-1">|</span>
                <span class="text-amber-100 font-bold">SGD:</span>
                <span class="font-black text-white text-sm">
                    {{ $metrics['sgd_rate'] ? 'Rp ' . number_format($metrics['sgd_rate'], 0, ',', '.') : '—' }}
                </span>
            </div>
            <div class="mt-3">
                @if($metrics['unresolved_notes'] > 0)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white text-orange-950 font-black text-xs shadow-md">
                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                    {{ $metrics['unresolved_notes'] }} Catatan Butuh Tindakan
                </span>
                @else
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-950/40 border border-emerald-400/40 text-white font-bold text-xs">
                    ✅ Semua Catatan Selesai
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs Navigation Bar (Modern Segmented Floating Control) --}}
    <div class="bg-white p-2 rounded-3xl shadow-md border border-slate-200/80 flex flex-wrap items-center gap-2 mb-6">
        <button wire:click="setTab('notes')"
            class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $activeTab === 'notes' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-400/30' : 'bg-slate-50 text-slate-700 hover:text-blue-700 hover:bg-blue-50/60 border border-slate-200/80' }}">
            <span>🗒️ Catatan &amp; Audit Pajak</span>
            @if($metrics['unresolved_notes'] > 0)
            <span class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-950 text-[10px] font-black shadow-xs">
                {{ $metrics['unresolved_notes'] }}
            </span>
            @endif
        </button>

        <button wire:click="setTab('compliance')"
            class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $activeTab === 'compliance' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-400/30' : 'bg-slate-50 text-slate-700 hover:text-blue-700 hover:bg-blue-50/60 border border-slate-200/80' }}">
            <span>📋 Monitoring Faktur Pajak &amp; Bukti Potong</span>
            @if($metrics['fp_pending'] + $metrics['bupot_pending'] > 0)
            <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black shadow-xs">
                {{ $metrics['fp_pending'] + $metrics['bupot_pending'] }}
            </span>
            @endif
        </button>

        <button wire:click="setTab('equalization')"
            class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $activeTab === 'equalization' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-400/30' : 'bg-slate-50 text-slate-700 hover:text-blue-700 hover:bg-blue-50/60 border border-slate-200/80' }}">
            <span>📊 Rekap Ekualisasi Omzet (DPP)</span>
        </button>

        <button wire:click="setTab('rates')"
            class="px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 {{ $activeTab === 'rates' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-400/30' : 'bg-slate-50 text-slate-700 hover:text-blue-700 hover:bg-blue-50/60 border border-slate-200/80' }}">
            <span>🌐 Kurs Pajak KMK Mingguan</span>
        </button>
    </div>

    {{-- TAB 1: CATATAN PAJAK --}}
    @if($activeTab === 'notes')
    <div class="bg-white rounded-3xl border border-slate-200 shadow-md overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gradient-to-r from-slate-50 to-blue-50/50">
            <div>
                <h3 class="text-base font-extrabold text-slate-800 flex items-center gap-2">
                    <span>📝 Catatan &amp; Rekomendasi Audit Konsultan Pajak</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Media koordinasi dan konsultasi pajak resmi antara Admin/Finance M2B dengan Konsultan Pajak.</p>
            </div>
            <span class="px-3 py-1 rounded-xl bg-blue-100 text-blue-800 font-extrabold text-xs shadow-xs">
                Total: {{ $notes->total() }} Catatan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-slate-700">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-xs font-black tracking-wider border-b border-slate-200">
                        <th class="px-4 py-3.5 text-center w-14">Status</th>
                        <th class="px-4 py-3.5 text-left">Periode</th>
                        <th class="px-4 py-3.5 text-left">Jenis Pajak</th>
                        <th class="px-4 py-3.5 text-left">Ref Invoice</th>
                        <th class="px-4 py-3.5 text-right">Nominal</th>
                        <th class="px-5 py-3.5 text-left">Catatan &amp; Pembahasan</th>
                        <th class="px-4 py-3.5 text-left">Dibuat Oleh</th>
                        <th class="px-4 py-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($notes as $note)
                    @php
                        $attachCount = count($note->attachments ?? []);
                    @endphp
                    <tr class="hover:bg-blue-50/40 transition-colors {{ $note->is_resolved ? 'bg-slate-50/60' : 'bg-white' }}">
                        {{-- Status toggle --}}
                        <td class="px-4 py-3.5 text-center">
                            @if($this->canEdit($note))
                            <button wire:click="toggleResolved({{ $note->id }})"
                                title="{{ $note->is_resolved ? 'Klik untuk tandai Belum Selesai' : 'Klik untuk tandai Selesai' }}"
                                class="text-xl leading-none transition-transform hover:scale-125 cursor-pointer">
                                {{ $note->is_resolved ? '✅' : '🔴' }}
                            </button>
                            @else
                                <span class="text-xl">{{ $note->is_resolved ? '✅' : '🔴' }}</span>
                            @endif
                        </td>

                        {{-- Periode --}}
                        <td class="px-4 py-3.5">
                            <span class="font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2.5 py-1 rounded-lg text-xs">
                                {{ $note->periode }}
                            </span>
                        </td>

                        {{-- Jenis Pajak --}}
                        <td class="px-4 py-3.5">
                            @if($note->jenis_pajak)
                                <span class="px-2.5 py-1 bg-amber-50 border border-amber-300 rounded-lg text-xs text-amber-900 font-black">
                                    {{ $note->jenis_pajak }}
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Invoice --}}
                        <td class="px-4 py-3.5">
                            @if($note->invoice)
                                <a href="{{ route('admin.invoices.index', ['search' => $note->invoice->invoice_number]) }}" target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700 font-mono font-bold hover:bg-blue-100 transition shadow-xs">
                                    🧾 {{ $note->invoice->invoice_number }} ↗
                                </a>
                                @if($note->invoice->customer)
                                    <div class="text-xs text-slate-500 font-bold mt-1">{{ $note->invoice->customer->company_name }}</div>
                                @endif
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Nominal --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs">
                            @if($note->nominal !== null)
                                <span class="text-slate-900 font-black text-sm">Rp {{ number_format($note->nominal, 0, ',', '.') }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        {{-- Catatan + lampiran --}}
                        <td class="px-5 py-3.5 max-w-sm">
                            <div class="text-slate-800 leading-relaxed font-semibold">{{ $note->catatan }}</div>
                            @if($note->is_resolved && $note->resolved_at)
                                <div class="text-[11px] text-emerald-700 mt-1 font-bold flex items-center gap-1">
                                    <span>✓ Selesai pada:</span> {{ $note->resolved_at->format('d M Y H:i') }}
                                </div>
                            @endif
                            @if($attachCount > 0)
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($note->attachments as $path)
                                    <a href="{{ Storage::disk('public')->url($path) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-200 transition shadow-xs">
                                        📎 {{ basename($path) }}
                                    </a>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        {{-- Dibuat Oleh --}}
                        <td class="px-4 py-3.5 text-xs text-slate-600">
                            <div class="font-bold text-slate-800">{{ $note->user->name ?? '—' }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">{{ $note->created_at->format('d M Y H:i') }}</div>
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3.5 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                @if($this->canEdit($note))
                                <button wire:click="openEdit({{ $note->id }})"
                                    class="px-2.5 py-1.5 text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg transition font-bold cursor-pointer"
                                    title="Edit Catatan">
                                    ✏️ Edit
                                </button>
                                @endif
                                @if($this->canDelete())
                                <button wire:click="confirmDelete({{ $note->id }})"
                                    class="px-2.5 py-1.5 text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg transition font-bold cursor-pointer"
                                    title="Hapus Catatan">
                                    🗑️
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center text-slate-500 bg-white">
                            <div class="text-4xl mb-3">📋</div>
                            <p class="font-bold text-slate-700 text-base">Belum ada catatan pajak untuk periode ini.</p>
                            <p class="text-xs text-slate-500 mt-1">Konsultan pajak atau finance dapat menambahkan catatan untuk koordinasi pelaporan SPT.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notes->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $notes->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- TAB 2: MONITORING FAKTUR PAJAK & BUKTI POTONG --}}
    @if($activeTab === 'compliance' && $complianceInvoices)
    <div class="bg-white rounded-3xl border border-slate-200 shadow-md overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50/50 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-black text-slate-500 uppercase tracking-wider mr-1">Filter Dokumen:</span>
                <button wire:click="$set('complianceFilter', 'all')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer {{ $complianceFilter === 'all' ? 'bg-blue-600 text-white ring-2 ring-blue-400/30' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200' }}">
                    Semua Berpajak
                </button>
                <button wire:click="$set('complianceFilter', 'missing_fp')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer {{ $complianceFilter === 'missing_fp' ? 'bg-amber-500 text-slate-950 ring-2 ring-amber-400/30' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200' }}">
                    ⚠️ Faktur Pajak Belum Terbit ({{ $metrics['fp_pending'] }})
                </button>
                <button wire:click="$set('complianceFilter', 'missing_bupot')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer {{ $complianceFilter === 'missing_bupot' ? 'bg-rose-600 text-white ring-2 ring-rose-400/30' : 'bg-rose-50 text-rose-900 hover:bg-rose-100 border border-rose-200' }}">
                    ⏳ Bukti Potong PPh 23 Belum Ada ({{ $metrics['bupot_pending'] }})
                </button>
                <button wire:click="$set('complianceFilter', 'complete')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-xs cursor-pointer {{ $complianceFilter === 'complete' ? 'bg-emerald-600 text-white ring-2 ring-emerald-400/30' : 'bg-emerald-50 text-emerald-900 hover:bg-emerald-100 border border-emerald-200' }}">
                    ✓ Dokumen Lengkap
                </button>
            </div>

            <div class="relative w-full md:w-72">
                <input type="text" wire:model.live.debounce.300ms="complianceSearch" placeholder="Cari No Invoice / FP / Bupot..."
                    class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-slate-700">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-xs font-black tracking-wider border-b border-slate-200">
                        <th class="px-4 py-3.5 text-left">No. Invoice &amp; Tgl</th>
                        <th class="px-4 py-3.5 text-left">Customer</th>
                        <th class="px-4 py-3.5 text-right">DPP Jasa</th>
                        <th class="px-4 py-3.5 text-right">Reimbursement</th>
                        <th class="px-4 py-3.5 text-left">PPN (11%) &amp; Faktur Pajak</th>
                        <th class="px-4 py-3.5 text-left">PPh 23 (2%) &amp; Bukti Potong</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($complianceInvoices as $inv)
                    <tr class="hover:bg-blue-50/40 transition-colors bg-white">
                        {{-- No Invoice & Tgl --}}
                        <td class="px-4 py-3.5">
                            <span class="font-mono font-black text-blue-700 text-sm">{{ $inv->invoice_number }}</span>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '—' }}</div>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black uppercase mt-1 {{ $inv->status === 'paid' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                {{ $inv->status }}
                            </span>
                        </td>

                        {{-- Customer --}}
                        <td class="px-4 py-3.5">
                            <div class="font-extrabold text-slate-800">{{ $inv->customer->company_name ?? '—' }}</div>
                        </td>

                        {{-- DPP Jasa --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs font-black text-purple-700 bg-purple-50/50">
                            Rp {{ number_format($inv->service_total, 0, ',', '.') }}
                        </td>

                        {{-- Reimbursement --}}
                        <td class="px-4 py-3.5 text-right font-mono text-xs font-bold text-amber-800 bg-amber-50/50">
                            Rp {{ number_format($inv->reimbursement_total, 0, ',', '.') }}
                        </td>

                        {{-- PPN & Faktur Pajak --}}
                        <td class="px-4 py-3.5">
                            @if($inv->tax_amount > 0)
                                <div class="text-xs font-mono font-black text-emerald-700">
                                    Rp {{ number_format($inv->tax_amount, 0, ',', '.') }}
                                </div>
                                @if($inv->faktur_pajak_path)
                                    <div class="mt-1">
                                        <a href="{{ Storage::disk('public')->url($inv->faktur_pajak_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-lg text-xs hover:bg-emerald-100 font-mono font-bold transition shadow-xs">
                                            ✓ FP: {{ $inv->faktur_pajak_number ?: 'Lihat PDF' }} ↗
                                        </a>
                                    </div>
                                @else
                                    <span class="inline-block mt-1 px-2.5 py-1 bg-amber-100 border border-amber-300 text-amber-900 rounded-lg text-[11px] font-black animate-pulse shadow-xs">
                                        ⚠️ Faktur Pajak Belum Ada
                                    </span>
                                @endif
                            @else
                                <span class="text-slate-400 text-xs">— (Non PPN)</span>
                            @endif
                        </td>

                        {{-- PPh 23 & Bukti Potong --}}
                        <td class="px-4 py-3.5">
                            @if($inv->pph_amount > 0)
                                <div class="text-xs font-mono font-black text-blue-700">
                                    Rp {{ number_format($inv->pph_amount, 0, ',', '.') }}
                                </div>
                                @if($inv->bukti_potong_path)
                                    <div class="mt-1">
                                        <a href="{{ Storage::disk('public')->url($inv->bukti_potong_path) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 border border-blue-300 text-blue-800 rounded-lg text-xs hover:bg-blue-100 font-mono font-bold transition shadow-xs">
                                            ✓ Bupot: {{ $inv->bukti_potong_number ?: 'Lihat PDF' }} ↗
                                        </a>
                                    </div>
                                @else
                                    <span class="inline-block mt-1 px-2.5 py-1 bg-rose-100 border border-rose-300 text-rose-900 rounded-lg text-[11px] font-black shadow-xs">
                                        ⏳ Bupot Belum Diterima
                                    </span>
                                @endif
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3.5 text-center">
                            <a href="{{ route('admin.invoices.index', ['search' => $inv->invoice_number]) }}" target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                                Lihat Invoice ↗
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 bg-white">
                            Tidak ditemukan invoice yang sesuai dengan filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complianceInvoices->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $complianceInvoices->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- TAB 3: REKAP EKUALISASI OMZET (DPP JASA VS TALANGAN) --}}
    @if($activeTab === 'equalization' && $equalizationData)
    <div class="space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-md overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-purple-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-slate-800">
                        📊 Ekualisasi Omzet Freight Forwarding &amp; Pajak (Tahun {{ $selectedYear }})
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Memisahkan DPP Imbalan Jasa yang terutang PPh 23 / PPN dengan Talangan Reimbursement Murni
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-slate-700">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 uppercase text-xs font-black tracking-wider border-b border-slate-200">
                            <th class="px-4 py-3.5 text-left">Masa Pajak</th>
                            <th class="px-4 py-3.5 text-center">Jml Inv</th>
                            <th class="px-4 py-3.5 text-right bg-purple-50 text-purple-900 border-x border-purple-100">DPP Jasa (Objek PPh 23)</th>
                            <th class="px-4 py-3.5 text-right bg-amber-50 text-amber-900 border-r border-amber-100">Reimbursement (Non-DPP)</th>
                            <th class="px-4 py-3.5 text-right">Subtotal</th>
                            <th class="px-4 py-3.5 text-right bg-emerald-50 text-emerald-900 border-x border-emerald-100">PPN (11%)</th>
                            <th class="px-4 py-3.5 text-right bg-blue-50 text-blue-900 border-r border-blue-100">PPh 23 (2%)</th>
                            <th class="px-4 py-3.5 text-right bg-indigo-50 text-indigo-950 font-black">Grand Total Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono text-xs">
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
                        <tr class="hover:bg-blue-50/40 transition-colors {{ $row['count'] > 0 ? 'bg-white' : 'bg-slate-50/50 text-slate-400' }}">
                            <td class="px-4 py-3 font-sans font-bold text-slate-800">
                                {{ $row['month_name'] }}
                            </td>
                            <td class="px-4 py-3 text-center font-sans font-semibold">
                                {{ $row['count'] ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-purple-700 font-black bg-purple-50/40 border-x border-purple-100">
                                {{ $row['dpp_jasa'] ? number_format($row['dpp_jasa'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-amber-800 font-bold bg-amber-50/40 border-r border-amber-100">
                                {{ $row['reimbursement'] ? number_format($row['reimbursement'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700 font-semibold">
                                {{ $row['subtotal'] ? number_format($row['subtotal'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-emerald-700 font-black bg-emerald-50/40 border-x border-emerald-100">
                                {{ $row['ppn'] ? number_format($row['ppn'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-blue-700 font-black bg-blue-50/40 border-r border-blue-100">
                                {{ $row['pph23'] ? number_format($row['pph23'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-indigo-900 font-black bg-indigo-50/50">
                                {{ $row['grand_total'] ? number_format($row['grand_total'], 0, ',', '.') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 text-white font-mono text-xs font-black">
                        <tr>
                            <td class="px-4 py-3.5 font-sans">TOTAL TAHUN {{ $selectedYear }}</td>
                            <td class="px-4 py-3.5 text-center font-sans">{{ $sumCount }}</td>
                            <td class="px-4 py-3.5 text-right text-amber-300">Rp {{ number_format($sumDpp, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-amber-200">Rp {{ number_format($sumReimb, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-slate-200">Rp {{ number_format($sumSub, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-emerald-300">Rp {{ number_format($sumPpn, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-blue-300">Rp {{ number_format($sumPph, 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-white text-sm">Rp {{ number_format($sumGrand, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Legal Reference Card --}}
        <div class="p-5 bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white rounded-3xl text-xs leading-relaxed flex items-start gap-4 shadow-lg border border-blue-700/40">
            <span class="text-3xl mt-0.5">ℹ️</span>
            <div>
                <h4 class="font-black text-amber-300 text-sm mb-1">Pedoman Fiskal Freight Forwarding &amp; Ekualisasi Pajak M2B</h4>
                <p class="text-blue-100 font-medium">
                    Berdasarkan <strong>PMK 71/PMK.03/2022</strong> dan regulasi PPh Pasal 23 DJP, dalam kegiatan freight forwarding terdapat pemisahan tegas antara <strong>Imbalan Jasa Manajemen/Handling (DPP)</strong> dengan <strong>Biaya Talangan (Reimbursement/At Cost)</strong> seperti ongkos angkut pelayaran, demurrage, dan sewa penumpukan TPS. Tabel di atas mempermudah Konsultan Pajak dalam mencocokkan SPT Masa PPN 1111, SPT Masa PPh 23, serta SPT Tahunan Badan 1771 tanpa adanya selisih ekualisasi omzet.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- TAB 4: KURS PAJAK KEMENKEU (KMK) --}}
    @if($activeTab === 'rates' && $exchangeRates)
    <div class="bg-white rounded-3xl border border-slate-200 shadow-md overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-800">
                    🌐 Kurs Keputusan Menteri Keuangan (KMK) Mingguan
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Dasar konversi nilai pabean, perhitungan Bea Masuk, PPN Impor &amp; PPh 22 Impor resmi Kemenkeu
                </p>
            </div>

            <div class="w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="rateSearch" placeholder="Cari mata uang (USD, SGD, EUR)..."
                    class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-slate-700">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-xs font-black tracking-wider border-b border-slate-200">
                        <th class="px-4 py-3.5 text-left">Kode</th>
                        <th class="px-4 py-3.5 text-left">Mata Uang</th>
                        <th class="px-4 py-3.5 text-right">Nilai Kurs Pajak</th>
                        <th class="px-4 py-3.5 text-left">Mulai Berlaku</th>
                        <th class="px-4 py-3.5 text-left">Berlaku Sampai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-xs font-medium">
                    @forelse($exchangeRates as $rate)
                    <tr class="hover:bg-blue-50/40 transition-colors bg-white">
                        <td class="px-4 py-3.5 font-bold text-blue-700 font-sans">
                            <span class="px-2.5 py-1 bg-blue-100 border border-blue-200 text-blue-800 font-black rounded-lg">
                                {{ $rate->currency_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 font-sans font-bold text-slate-800">
                            {{ trim($rate->currency_name) }}
                        </td>
                        <td class="px-4 py-3.5 text-right font-black text-emerald-700 text-sm">
                            Rp {{ number_format($rate->rate, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3.5 text-slate-600 font-sans font-semibold">
                            {{ $rate->valid_from ? $rate->valid_from->format('d M Y') : '—' }}
                        </td>
                        <td class="px-4 py-3.5 text-slate-600 font-sans font-semibold">
                            {{ $rate->valid_until ? $rate->valid_until->format('d M Y') : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-slate-500 font-sans bg-white">
                            Tidak ditemukan data kurs KMK.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($exchangeRates->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50">
            {{ $exchangeRates->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- Create / Edit Modal (Preserved & Beautifully Styled) --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden animate-fade-in">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-blue-700 to-indigo-700 text-white flex-shrink-0">
                <h2 class="text-base font-extrabold flex items-center gap-2">
                    <span>{{ $isEditing ? '✏️ Edit Catatan Pajak' : '➕ Tambah Catatan Pajak' }}</span>
                </h2>
                <button wire:click="closeModal" class="text-white/80 hover:text-white transition-colors text-2xl leading-none">&times;</button>
            </div>

            {{-- Scrollable body --}}
            <form wire:submit.prevent="save" class="px-6 py-5 space-y-4 overflow-y-auto flex-1">

                {{-- Periode --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wide">Masa Pajak / Periode (YYYY-MM)</label>
                    <select wire:model="periode"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        @foreach($periodeOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('periode') <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Jenis Pajak + Nominal --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wide">
                            Jenis Pajak <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <select wire:model="jenis_pajak"
                            class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                            <option value="">— Pilih jenis —</option>
                            @foreach($jenisPajakList as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis_pajak') <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wide">
                            Nominal (Rp) <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <input wire:model="nominal" type="number" min="0" step="1" placeholder="0"
                            class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition font-mono">
                        @error('nominal') <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Referensi Invoice --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wide">
                        Referensi Invoice <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input wire:model.live.debounce.300ms="invoiceSearch"
                        type="text"
                        placeholder="Ketik nomor invoice atau nama customer..."
                        class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition mb-2">
                    <select wire:model="invoice_id"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-xl px-3.5 py-2.5 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition">
                        <option value="">— Tidak terkait invoice spesifik —</option>
                        @foreach($invoiceOptions as $inv)
                            <option value="{{ $inv['id'] }}">{{ $inv['label'] }}</option>
                        @endforeach
                    </select>
                    @error('invoice_id') <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wide">
                        Catatan, Rekomendasi, atau Temuan Fiskal
                    </label>
                    <textarea wire:model="catatan" rows="4"
                        placeholder="Tuliskan catatan kepatuhan pajak, koreksi fiskal, atau instruksi tindak lanjut..."
                        class="w-full bg-slate-50 border border-slate-300 text-slate-800 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition leading-relaxed"></textarea>
                    @error('catatan') <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Attachments --}}
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wide">
                        Lampiran Dokumen <span class="text-slate-400 font-normal">(PDF, XLS, Gambar · maks 10MB)</span>
                    </label>
                    <input type="file" wire:model="newAttachments" multiple
                        class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                    @error('newAttachments.*') <p class="text-rose-600 text-xs font-bold mt-1">{{ $message }}</p> @enderror

                    {{-- Existing attachments list --}}
                    @if(!empty($existingAttachments))
                    <div class="mt-2.5 space-y-1.5">
                        <p class="text-xs text-slate-500 font-bold mb-1">Lampiran tersimpan:</p>
                        @foreach($existingAttachments as $i => $path)
                        <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100 rounded-xl text-xs text-slate-700 border border-slate-200">
                            <span class="truncate font-semibold">📎 {{ basename($path) }}</span>
                            <button type="button" wire:click="removeExistingAttachment({{ $i }})"
                                class="text-rose-600 hover:text-rose-800 ml-2 font-black text-sm">&times;</button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                    <button type="button" wire:click="closeModal"
                        class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:text-slate-800 border border-slate-300 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        class="px-6 py-2.5 text-sm font-black bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg shadow-blue-500/25 transition cursor-pointer">
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 text-center border border-slate-200 animate-fade-in">
            <div class="text-5xl mb-3">🗑️</div>
            <h3 class="text-lg font-black text-slate-900 mb-1">Hapus Catatan Pajak?</h3>
            <p class="text-xs text-slate-500 mb-6 font-medium leading-relaxed">Semua lampiran terkait akan ikut terhapus permanen dari sistem.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete"
                    class="px-5 py-2.5 text-sm font-bold border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button wire:click="deleteNote"
                    class="px-5 py-2.5 text-sm bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl shadow-lg shadow-rose-600/30 transition cursor-pointer">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
