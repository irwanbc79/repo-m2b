<div class="max-w-7xl mx-auto space-y-6 pb-16">
    @section('header', 'Kalkulator Pabean & Simulasi Pajak')

    {{-- Toast Notification --}}
    <div x-data="{ show: false, message: '', type: 'success' }" 
         x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type || 'success'; setTimeout(() => show = false, 3500)"
         x-show="show" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         x-bind:class="type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
         class="fixed top-5 right-5 px-5 py-3 rounded-2xl shadow-2xl z-50 flex items-center gap-3 border border-white/20 text-sm font-semibold"
         style="display: none;">
        <span x-text="message"></span>
    </div>

    {{-- Top Header & Kurs Sync Bar --}}
    <div class="bg-white rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200 flex flex-col lg:flex-row justify-between items-center gap-5">
        <div class="flex items-center gap-4 w-full lg:w-auto">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white text-2xl shadow-lg shadow-blue-500/25 shrink-0">
                🧮
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-black text-slate-800 tracking-tight">Kalkulator Pabean &amp; Simulasi Pajak Impor</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-700">Enterprise</span>
                </div>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Sinkronisasi Kurs Pajak Kemenkeu & Analisis Kredit Pajak PPJK M2B</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-end">
            {{-- History Button --}}
            <button wire:click="toggleHistory" class="flex items-center gap-2 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl transition text-xs font-bold text-slate-700">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Riwayat</span>
                @if(count($calculationHistory) > 0)
                <span class="bg-blue-600 text-white text-[10px] px-1.5 py-0.2 rounded-full font-black">{{ count($calculationHistory) }}</span>
                @endif
            </button>

            {{-- Live Kurs Badge --}}
            <div class="flex items-center gap-3 bg-slate-50 border border-slate-200/80 px-4 py-2 rounded-2xl">
                <div class="text-right">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Kurs Pajak {{ $mata_uang }}</span>
                    <span class="text-lg font-mono font-black text-blue-900 tracking-tight leading-none">Rp {{ number_format($kurs, 2, ',', '.') }}</span>
                </div>
                <div class="bg-emerald-100/80 px-2.5 py-1 rounded-xl border border-emerald-200 flex items-center gap-1.5 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] font-black text-emerald-800 uppercase tracking-wider">{{ $is_auto_kurs ? 'Live Sync' : 'Manual' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Segmented Mode Switcher (Operasional vs Perpajakan) --}}
    <div class="bg-slate-100 p-1.5 rounded-2xl flex max-w-xl mx-auto shadow-inner border border-slate-200">
        <button wire:click="$set('activeTab', 'operation')" 
                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-black transition-all duration-200 flex items-center justify-center gap-2 {{ $activeTab === 'operation' ? 'bg-white text-blue-600 shadow-md shadow-slate-200' : 'text-slate-500 hover:text-slate-800' }}">
            <span>📦</span>
            <span>Mode Operasional & PPJK</span>
        </button>
        <button wire:click="$set('activeTab', 'tax')" 
                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-black transition-all duration-200 flex items-center justify-center gap-2 {{ $activeTab === 'tax' ? 'bg-white text-indigo-600 shadow-md shadow-slate-200' : 'text-slate-500 hover:text-slate-800' }}">
            <span>⚖️</span>
            <span>Mode Perpajakan & Kredit Pajak</span>
        </button>
    </div>

    {{-- History Modal --}}
    @if($showHistory)
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4" wire:click.self="toggleHistory">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden border border-slate-100 animate-in fade-in zoom-in duration-150">
            <div class="p-5 md:p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-base font-black text-slate-800">📜 Riwayat Simulasi Terakhir</h3>
                    <p class="text-xs text-slate-400">Tersimpan otomatis per sesi kerja Anda</p>
                </div>
                <div class="flex items-center gap-2">
                    @if(count($calculationHistory) > 0)
                    <button wire:click="clearHistory" wire:confirm="Hapus semua riwayat kalkulasi?" class="text-xs text-rose-500 hover:text-rose-700 font-bold px-2 py-1 hover:bg-rose-50 rounded-lg transition">Hapus Semua</button>
                    @endif
                    <button wire:click="toggleHistory" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-5 md:p-6 overflow-y-auto max-h-[60vh] space-y-3">
                @forelse($calculationHistory as $item)
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/60 hover:border-blue-300 hover:bg-blue-50/30 transition group flex justify-between items-center gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-slate-400">{{ \Carbon\Carbon::parse($item['timestamp'])->format('d M Y, H:i') }}</span>
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-black rounded-md">{{ $item['preset'] ?? 'Manual' }}</span>
                            @if(($item['cif_method'] ?? 'direct') === 'breakdown')
                            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-[9px] font-bold rounded-md">Breakdown FOB</span>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="font-bold text-slate-800 text-sm">{{ $item['mata_uang'] }} {{ number_format($item['nilai_barang'], 2) }}</span>
                            <span class="text-xs text-slate-400 font-mono">@ Rp {{ number_format($item['kurs'], 0) }}</span>
                        </div>
                        <div class="text-xs text-slate-500">
                            BM: {{ $item['tarif']['bm'] }}% &bull; PPN: {{ $item['tarif']['ppn'] }}% &bull; PPh: {{ $item['tarif']['pph'] }}%
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="block text-xs font-black text-slate-400 uppercase tracking-widest">Total Billing</span>
                        <span class="font-mono font-black text-emerald-600 text-base">Rp {{ number_format($item['hasil']['total'], 0, ',', '.') }}</span>
                        <div class="flex gap-1.5 justify-end mt-2 opacity-90 group-hover:opacity-100 transition">
                            <button wire:click="loadFromHistory('{{ $item['id'] }}')" class="px-2.5 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-bold transition">
                                Muat
                            </button>
                            <button wire:click="deleteFromHistory('{{ $item['id'] }}')" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12 text-slate-400">
                    <p class="font-medium text-sm">Belum ada riwayat kalkulasi tersimpan.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- =========================================
             KOLOM KIRI: INPUT & PARAMETER (COL-7)
             ========================================= --}}
        <div class="lg:col-span-7 space-y-6">

            {{-- 1. PRESET KATEGORI KOMODITAS --}}
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200 p-5 md:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-black">⚡</span>
                        <h4 class="font-bold text-slate-700 uppercase text-xs tracking-wider">Preset Cepat Komoditas Umum</h4>
                    </div>
                    <span class="text-[11px] text-slate-400">Klik untuk auto-fill tarif standar</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    @foreach($presets as $key => $preset)
                    <button wire:click="applyPreset('{{ $key }}')" 
                            class="p-2.5 rounded-2xl text-left border transition-all duration-150 relative group {{ $selectedPreset === $key ? 'bg-indigo-50 border-indigo-400 ring-2 ring-indigo-500/20 shadow-xs' : 'bg-slate-50/70 border-slate-200 hover:bg-slate-100 hover:border-slate-300' }}">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="text-base">{{ $preset['icon'] }}</span>
                            <span class="text-xs font-bold text-slate-800 truncate">{{ $preset['name'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono">
                            <span>BM: {{ $preset['bm'] }}%</span> &bull; 
                            <span>PPN: {{ $preset['ppn'] }}%</span>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- 2. NILAI TRANSAKSI PABEAN (CIF) --}}
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200 p-6 md:p-7 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-[10px] font-black">01</span>
                        <h4 class="font-bold text-slate-800 uppercase text-xs tracking-wider">Nilai Transaksi Pabean (CIF)</h4>
                    </div>

                    {{-- Switcher CIF: Langsung vs Breakdown --}}
                    <div class="inline-flex bg-slate-100 p-1 rounded-xl text-xs font-bold self-start sm:self-auto">
                        <button wire:click="$set('cifMethod', 'direct')" 
                                class="px-3 py-1 rounded-lg transition {{ $cifMethod === 'direct' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                            Langsung CIF
                        </button>
                        <button wire:click="$set('cifMethod', 'breakdown')" 
                                class="px-3 py-1 rounded-lg transition {{ $cifMethod === 'breakdown' ? 'bg-white text-purple-600 shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                            FOB + Freight + Asuransi
                        </button>
                    </div>
                </div>

                {{-- Baris Mata Uang & Kurs --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Mata Uang Faktur</label>
                        <select wire:model.live="mata_uang" class="w-full border-slate-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-2.5 bg-slate-50/50 shadow-xs">
                            @foreach($currencies as $code => $currency)
                            <option value="{{ $code }}">
                                {{ $currency['flag'] }} {{ $code }} &mdash; {{ $currency['name'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Kurs Pajak (IDR)</label>
                            <span class="text-[10px] text-slate-400">Kemenkeu</span>
                        </div>
                        <div class="relative">
                            <input type="number" step="0.01" wire:model.live="kurs" class="w-full border-slate-200 rounded-xl text-sm font-mono font-bold bg-slate-50 py-2.5 pl-3.5 pr-14" {{ $is_auto_kurs ? 'readonly' : '' }}>
                            <div class="absolute right-2.5 top-2 flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-0.5 rounded-lg text-[9px] font-black border border-blue-200">
                                {{ $is_auto_kurs ? 'AUTO' : 'EDIT' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Input Nilai --}}
                @if($cifMethod === 'breakdown')
                {{-- Breakdown Form --}}
                <div class="bg-purple-50/40 rounded-2xl p-4 md:p-5 border border-purple-100 space-y-4">
                    <div class="flex items-center justify-between text-xs font-black text-purple-900 uppercase tracking-wider">
                        <span>Rincian Pembentuk CIF (Incoterms)</span>
                        <span class="text-[10px] text-purple-600 font-medium">Sesuai invoice supplier & B/L</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase">Nilai FOB (Barang)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">{{ $mata_uang }}</span>
                                <input type="number" step="0.01" wire:model.live.debounce.300ms="nilai_fob" 
                                       class="w-full pl-12 pr-3 py-2 border-slate-200 rounded-xl text-sm font-mono font-bold focus:ring-purple-500 focus:border-purple-500 bg-white" placeholder="0">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-500 uppercase">Ongkos Angkut (Freight)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">{{ $mata_uang }}</span>
                                <input type="number" step="0.01" wire:model.live.debounce.300ms="nilai_freight" 
                                       class="w-full pl-12 pr-3 py-2 border-slate-200 rounded-xl text-sm font-mono font-bold focus:ring-purple-500 focus:border-purple-500 bg-white" placeholder="0">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex justify-between items-center">
                                <label class="text-[10px] font-bold text-slate-500 uppercase">Asuransi</label>
                                <select wire:model.live="insurance_type" class="text-[9px] border-none bg-transparent p-0 text-purple-700 font-bold focus:ring-0">
                                    <option value="manual">Manual Polis</option>
                                    <option value="pmk_dn">Rumus DN (0.5%)</option>
                                </select>
                            </div>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">{{ $mata_uang }}</span>
                                <input type="number" step="0.01" wire:model.live.debounce.300ms="nilai_insurance" 
                                       class="w-full pl-12 pr-3 py-2 border-slate-200 rounded-xl text-sm font-mono font-bold focus:ring-purple-500 focus:border-purple-500 bg-white" placeholder="0"
                                       {{ $insurance_type === 'pmk_dn' ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Total Input Nilai Barang CIF --}}
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                            {{ $cifMethod === 'breakdown' ? 'Total Nilai CIF Terhitung' : 'Input Nilai CIF (Cost, Insurance & Freight)' }}
                        </label>
                        <span class="text-xs font-bold text-blue-600 font-mono">{{ $mata_uang }}</span>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 font-black text-2xl">
                            {{ $mata_uang }}
                        </div>
                        <input type="number" step="0.01" wire:model.live.debounce.300ms="nilai_barang" 
                               {{ $cifMethod === 'breakdown' ? 'readonly' : '' }}
                               class="w-full pl-24 pr-6 py-4.5 border-2 border-slate-200 rounded-2xl text-3xl font-black text-slate-800 focus:ring-0 focus:border-blue-600 transition-all placeholder:text-slate-200 bg-slate-50/50 font-mono"
                               placeholder="0">
                    </div>
                </div>

                {{-- Equivalent Rupiah Card --}}
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-5 flex justify-between items-center shadow-lg shadow-blue-500/15 text-white">
                    <div>
                        <p class="text-[10px] font-black text-blue-200 uppercase tracking-widest">Dasar Pungutan (CIF × Kurs)</p>
                        <p class="text-sm font-bold mt-0.5">Nilai Pabean (IDR)</p>
                    </div>
                    <div class="text-right">
                        <span class="text-2xl md:text-3xl font-black font-mono tracking-tight">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- 3. PARAMETER TARIF, STATUS IMPORTIR & FASILITAS --}}
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200 p-6 md:p-7 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-slate-800 text-white flex items-center justify-center text-[10px] font-black">02</span>
                        <h4 class="font-bold text-slate-800 uppercase text-xs tracking-wider">Regulasi Tarif & Status Importir</h4>
                    </div>
                    <button wire:click="toggleBreakdown" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Aturan Tarif</span>
                    </button>
                </div>

                @if($showBreakdown)
                <div class="p-4 bg-blue-50/60 rounded-2xl border border-blue-100 text-xs text-slate-700 space-y-1.5 leading-relaxed">
                    <p><strong>Bea Masuk (BM):</strong> Tarif BTKI (0% s/d 30%+). Dapat 0% jika menggunakan Form E / D.</p>
                    <p><strong>PPN Impor:</strong> Standar 11% (UU HPP). Dikenakan atas Nilai Impor (CIF + BM).</p>
                    <p><strong>PPh Pasal 22:</strong> 2.5% jika memiliki API (Angka Pengenal Importir), 7.5% untuk Non-API, dan 15% jika tanpa NPWP.</p>
                    <p><strong>PPnBM:</strong> Pajak Barang Mewah khusus kategori tertentu (10% - 200%).</p>
                </div>
                @endif

                {{-- Status Importir & Skema FTA --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Status Izin Importir (PPh 22)</label>
                        <select wire:model.live="status_importir" class="w-full border-slate-200 rounded-xl text-xs font-bold focus:ring-blue-500 focus:border-blue-500 py-2.5 bg-slate-50/50 shadow-xs">
                            <option value="api">Memiliki API (Angka Pengenal Importir) &bull; PPh 2.5%</option>
                            <option value="non_api">Non-API (Tanpa API) &bull; PPh 7.5%</option>
                            <option value="no_npwp">Tanpa NPWP &bull; Kenaikan 100% (PPh 15.0%)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Skema Fasilitas / FTA</label>
                        <select wire:model.live="skema_fasilitas" class="w-full border-slate-200 rounded-xl text-xs font-bold focus:ring-blue-500 focus:border-blue-500 py-2.5 bg-slate-50/50 shadow-xs">
                            <option value="mfn">Normal / Standar (MFN)</option>
                            <option value="form_e">Form E (ACFTA China &bull; BM 0%)</option>
                            <option value="form_d">Form D (ATIGA ASEAN &bull; BM 0%)</option>
                            <option value="form_ak">Form AK (AKFTA Korea &bull; BM 0%)</option>
                            <option value="kite">KITE / Kawasan Berikat (Pembebasan)</option>
                            <option value="custom">Kustom / Lainnya</option>
                        </select>
                    </div>
                </div>

                {{-- Input Persentase Tarif --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                    <div class="space-y-1 text-center group">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">Bea Masuk (BM)</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:model.live.debounce.300ms="tarif_bm" 
                                   class="w-full border border-slate-200 bg-slate-50 rounded-xl text-base font-black text-center py-2.5 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                            <span class="absolute right-2.5 top-2.5 text-xs font-bold text-slate-400">%</span>
                        </div>
                    </div>

                    <div class="space-y-1 text-center group">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">PPN Impor</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:model.live.debounce.300ms="tarif_ppn" 
                                   class="w-full border border-slate-200 bg-slate-50 rounded-xl text-base font-black text-center py-2.5 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                            <span class="absolute right-2.5 top-2.5 text-xs font-bold text-slate-400">%</span>
                        </div>
                    </div>

                    <div class="space-y-1 text-center group">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">PPnBM</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:model.live.debounce.300ms="tarif_ppnbm" 
                                   class="w-full border border-slate-200 bg-slate-50 rounded-xl text-base font-black text-center py-2.5 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                            <span class="absolute right-2.5 top-2.5 text-xs font-bold text-slate-400">%</span>
                        </div>
                    </div>

                    <div class="space-y-1 text-center group">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wider">PPh Pasal 22</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:model.live.debounce.300ms="tarif_pph" 
                                   class="w-full border border-slate-200 bg-slate-50 rounded-xl text-base font-black text-center py-2.5 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                            <span class="absolute right-2.5 top-2.5 text-xs font-bold text-slate-400">%</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- =========================================
             KOLOM KANAN: RECEIPT & ANALISIS (COL-5)
             ========================================= --}}
        <div class="lg:col-span-5 space-y-6">
            
            <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-900/40 border border-slate-800 overflow-hidden sticky top-6">
                
                {{-- Panel Atas Resi --}}
                <div class="p-8 md:p-9 text-center relative overflow-hidden">
                    <div class="absolute -top-32 -right-32 w-80 h-80 bg-blue-600 rounded-full opacity-15 blur-[90px] pointer-events-none"></div>
                    <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-indigo-600 rounded-full opacity-15 blur-[90px] pointer-events-none"></div>

                    <div class="relative z-10">
                        <span class="inline-block px-3 py-1 rounded-full bg-blue-500/10 border border-blue-400/20 text-blue-400 text-[10px] font-black tracking-widest uppercase mb-3">
                            {{ $activeTab === 'operation' ? 'Billing SIMPONI / SSPCP' : 'Dampak Perpajakan & Kas' }}
                        </span>

                        <h4 class="text-slate-400 font-black tracking-[0.25em] uppercase text-[10px] mb-2">Total Estimasi Setoran Pabean</h4>
                        
                        <div class="flex items-baseline justify-center gap-2">
                            <span class="text-white/30 text-xl font-bold font-mono">IDR</span>
                            <span class="text-4xl md:text-5xl font-black text-white font-mono tracking-tight">{{ number_format($total_pungutan, 0, ',', '.') }}</span>
                        </div>

                        {{-- Stacked Visual Bar Proporsi Pungutan --}}
                        @if($total_pungutan > 0)
                        <div class="mt-6 space-y-2">
                            <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden flex shadow-inner">
                                @if($proporsi_bm > 0)
                                <div style="width: {{ $proporsi_bm }}%" class="bg-blue-500 h-full" title="BM: {{ $proporsi_bm }}%"></div>
                                @endif
                                @if($proporsi_ppn > 0)
                                <div style="width: {{ $proporsi_ppn }}%" class="bg-emerald-500 h-full" title="PPN: {{ $proporsi_ppn }}%"></div>
                                @endif
                                @if($proporsi_ppnbm > 0)
                                <div style="width: {{ $proporsi_ppnbm }}%" class="bg-rose-500 h-full" title="PPnBM: {{ $proporsi_ppnbm }}%"></div>
                                @endif
                                @if($proporsi_pph > 0)
                                <div style="width: {{ $proporsi_pph }}%" class="bg-amber-500 h-full" title="PPh: {{ $proporsi_pph }}%"></div>
                                @endif
                            </div>

                            <div class="flex justify-between items-center text-[10px] font-mono text-slate-400 px-1">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span> BM {{ $proporsi_bm }}%</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> PPN {{ $proporsi_ppn }}%</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> PPh {{ $proporsi_pph }}%</span>
                            </div>
                        </div>
                        @endif

                        {{-- Breakdown Baris Pungutan --}}
                        <div class="mt-8 pt-6 border-t border-slate-800/80 space-y-3.5 text-left">
                            <div class="flex justify-between items-center group">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                    <span class="text-xs font-bold text-slate-300">Bea Masuk (BM)</span>
                                    <span class="text-[10px] text-slate-500 font-mono">({{ $tarif_bm }}%)</span>
                                </div>
                                <span class="font-mono font-bold text-white text-sm">Rp {{ number_format($bayar_bm, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between items-center group">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <span class="text-xs font-bold text-slate-300">PPN Impor</span>
                                    <span class="text-[10px] text-slate-500 font-mono">({{ $tarif_ppn }}%)</span>
                                </div>
                                <span class="font-mono font-bold text-emerald-400 text-sm">Rp {{ number_format($bayar_ppn, 0, ',', '.') }}</span>
                            </div>

                            @if($tarif_ppnbm > 0)
                            <div class="flex justify-between items-center group">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                    <span class="text-xs font-bold text-slate-300">PPnBM</span>
                                    <span class="text-[10px] text-slate-500 font-mono">({{ $tarif_ppnbm }}%)</span>
                                </div>
                                <span class="font-mono font-bold text-rose-400 text-sm">Rp {{ number_format($bayar_ppnbm, 0, ',', '.') }}</span>
                            </div>
                            @endif

                            <div class="flex justify-between items-center group">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    <span class="text-xs font-bold text-slate-300">PPh Pasal 22</span>
                                    <span class="text-[10px] text-slate-500 font-mono">({{ $tarif_pph }}%)</span>
                                </div>
                                <span class="font-mono font-bold text-amber-400 text-sm">Rp {{ number_format($bayar_pph, 0, ',', '.') }}</span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Panel Bawah Resi: Tax Insight & Action Buttons --}}
                <div class="p-6 md:p-8 bg-slate-950/80 border-t border-slate-800 space-y-5">
                    
                    {{-- Card Analisis Perpajakan (Tax Impact Card) --}}
                    <div class="bg-slate-900/90 rounded-2xl p-4.5 border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">💡 Dampak Pajak & Akuntansi</span>
                            <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-bold border border-emerald-500/20">Tax Deductible</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Kredit Pajak (Dapat Diklaim)</span>
                                <span class="font-mono font-black text-emerald-400 text-sm">Rp {{ number_format($total_kredit_pajak, 0, ',', '.') }}</span>
                                <span class="block text-[8px] text-slate-500 mt-0.5">PPN Masukan + PPh 22</span>
                            </div>

                            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
                                <span class="block text-[9px] font-bold text-slate-500 uppercase">Landed Cost Barang (HPP)</span>
                                <span class="font-mono font-black text-blue-400 text-sm">Rp {{ number_format($beban_pokok, 0, ',', '.') }}</span>
                                <span class="block text-[8px] text-slate-500 mt-0.5">CIF + BM + PPnBM</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Tindakan Utama --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Tombol Download PDF --}}
                        <button wire:click="downloadPdf" 
                                class="py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl transition text-xs flex items-center justify-center gap-2 shadow-lg shadow-blue-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Download PDF</span>
                        </button>

                        {{-- Tombol Copy Format WhatsApp --}}
                        <button wire:click="copyToClipboard"
                                x-data
                                x-on:copy-text.window="navigator.clipboard.writeText($event.detail.text); $dispatch('notify', {type: 'success', message: 'Teks format WhatsApp berhasil disalin!'})"
                                class="py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span>Salin WA</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Simpan ke Riwayat --}}
                        <button wire:click="saveToHistory" 
                                class="py-2.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold rounded-xl transition text-xs flex items-center justify-center gap-1.5 border border-slate-700/80">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            <span>Simpan</span>
                        </button>

                        {{-- Reset --}}
                        <button wire:click="resetCalculator" 
                                class="py-2.5 px-3 bg-slate-800 hover:bg-rose-950/60 hover:text-rose-400 text-slate-400 font-bold rounded-xl transition text-xs flex items-center justify-center gap-1.5 border border-slate-700/80">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset</span>
                        </button>
                    </div>

                    <p class="text-[9px] text-center text-slate-500 font-medium leading-relaxed">
                        *Estimasi mengacu pada kurs pajak Kemenkeu minggu berjalan. Nilai pungutan resmi yang mengikat ditetapkan oleh Bea Cukai pada dokumen PIB/SPPB.
                    </p>
                </div>
            </div>

        </div>

    </div>
</div>
