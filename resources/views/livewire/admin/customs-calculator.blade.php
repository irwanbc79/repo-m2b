<div class="max-w-7xl mx-auto space-y-6 pb-12">
    @section('header', 'Kalkulator Pabean')

    {{-- Toast Notification --}}
    <div x-data="{ show: false, message: '', type: 'success' }" 
         x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 3000)"
         x-show="show" x-transition
         x-bind:class="type === 'success' ? 'bg-green-500' : 'bg-red-500'"
         class="fixed top-4 right-4 text-white px-6 py-3 rounded-xl shadow-lg z-50 flex items-center gap-2"
         style="display: none;">
        <span x-text="message"></span>
    </div>

    {{-- Top Info Bar --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-blue-600 p-2.5 rounded-xl text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800 tracking-tight">Kalkulator Simulasi Impor</h2>
                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Sinkronisasi Kurs Pajak Mingguan</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            {{-- History Button --}}
            <button wire:click="toggleHistory" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl transition text-sm font-semibold text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                History ({{ count($calculationHistory) }})
            </button>
            <div class="flex items-center gap-6 md:border-l md:pl-6 border-gray-100">
                <div class="text-right">
                    <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Kurs {{ $mata_uang }} Saat Ini</span>
                    <span class="text-xl font-mono font-black text-blue-900 tracking-tighter">Rp {{ number_format($kurs, 2, ',', '.') }}</span>
                </div>
                <div class="bg-green-50 px-3 py-1.5 rounded-lg border border-green-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-[9px] font-black text-green-700 uppercase tracking-widest">Live Sync</span>
                </div>
            </div>
        </div>
    </div>

    {{-- History Modal --}}
    @if($showHistory)
    <div class="fixed inset-0 bg-black/50 z-40 flex items-center justify-center p-4" wire:click.self="toggleHistory">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">📜 Riwayat Kalkulasi</h3>
                <div class="flex items-center gap-2">
                    @if(count($calculationHistory) > 0)
                    <button wire:click="clearHistory" class="text-xs text-red-500 hover:text-red-700 font-semibold">Hapus Semua</button>
                    @endif
                    <button wire:click="toggleHistory" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                @forelse($calculationHistory as $item)
                <div class="bg-gray-50 rounded-xl p-4 mb-3 hover:bg-gray-100 transition group">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($item['timestamp'])->format('d M Y, H:i') }}</span>
                            <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded">{{ $item['preset'] }}</span>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                            <button wire:click="loadFromHistory('{{ $item['id'] }}')" class="p-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600" title="Load">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </button>
                            <button wire:click="deleteFromHistory('{{ $item['id'] }}')" class="p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600" title="Hapus">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-800">{{ $item['mata_uang'] }} {{ number_format($item['nilai_barang'], 2) }}</span>
                            <span class="text-gray-400 text-sm ml-2">@ Rp {{ number_format($item['kurs'], 0) }}</span>
                        </div>
                        <span class="font-black text-green-600 text-lg">Rp {{ number_format($item['hasil']['total'], 0, ',', '.') }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p>Belum ada riwayat kalkulasi</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- KOLOM KIRI: INPUT (COL-7) --}}
        <div class="lg:col-span-7 space-y-6">
            
            {{-- PRESET KATEGORI BARANG --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-3xl shadow-sm border border-blue-100 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="bg-indigo-600 text-white w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black">⚡</span>
                    <h4 class="font-bold text-gray-700 uppercase text-xs tracking-widest">Preset Kategori Barang</h4>
                    <span class="ml-auto text-[10px] text-gray-400 italic">Klik untuk auto-fill tarif</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                    @foreach($presets as $key => $preset)
                    <button wire:click="applyPreset('{{ $key }}')" 
                            class="px-3 py-2 rounded-xl text-xs font-semibold transition-all {{ $selectedPreset === $key ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-white text-gray-600 hover:bg-indigo-100 border border-gray-200' }}">
                        {{ $preset['name'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- DATA NILAI BARANG --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 md:p-8 space-y-6">
                <div class="flex items-center gap-2 border-b border-gray-50 pb-4">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black shadow-sm">01</span>
                    <h4 class="font-bold text-gray-700 uppercase text-xs tracking-widest">Nilai Pabean (CIF)</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Mata Uang</label>
                        <select wire:model.live="mata_uang" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-blue-500 focus:border-blue-500 py-3 shadow-sm transition-all">
                            @foreach($currencies as $code => $currency)
                            <option value="{{ $code }}">
                                {{ $currency['flag'] ?? '🏳️' }} {{ $code }} - {{ $currency['name'] ?? $code }}
                            </option>   
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Kurs (IDR)</label>
                        <div class="relative">
                            <input type="number" wire:model.live="kurs" class="w-full border-gray-200 rounded-xl text-sm font-mono font-bold bg-gray-50 py-3 pl-4 pr-16" {{ $is_auto_kurs ? 'readonly' : '' }}>
                            @if($is_auto_kurs)
                            <div class="absolute right-3 top-2.5 flex items-center gap-1 bg-blue-100 text-blue-700 px-2 py-1 rounded-lg text-[8px] font-black border border-blue-200">AUTO</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider ml-1">Input Nilai Transaksi (CIF)</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-gray-300 font-black text-xl group-focus-within:text-blue-600 transition-colors">{{ $mata_uang }}</div>
                        <input type="text" 
                               wire:model.live="nilai_barang"
                               x-data
                               x-on:input="$el.value = $el.value.replace(/[^0-9.]/g, '')"
                               class="w-full pl-24 pr-6 py-6 border-2 border-gray-100 rounded-2xl text-4xl font-black text-gray-800 focus:ring-0 focus:border-blue-600 transition-all placeholder:text-gray-200 shadow-inner bg-gray-50/30" 
                               placeholder="0">
                    </div>
                </div>

                <div class="bg-blue-600 rounded-2xl p-6 flex justify-between items-center shadow-lg shadow-blue-100">
                    <div>
                        <p class="text-[9px] font-black text-blue-200 uppercase tracking-widest">Rupiah Equivalent</p>
                        <p class="text-sm text-white font-bold mt-0.5">Nilai Pabean (IDR)</p>
                    </div>
                    <span class="text-2xl font-black text-white font-mono tracking-tighter">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- PARAMETER TARIF --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-6 md:p-8">
                <div class="flex items-center gap-2 border-b border-gray-50 pb-4 mb-6">
                    <span class="bg-gray-800 text-white w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black">02</span>
                    <h4 class="font-bold text-gray-700 uppercase text-xs tracking-widest">Parameter Tarif (%)</h4>
                    <button wire:click="toggleBreakdown" class="ml-auto text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Info Tarif
                    </button>
                </div>
                
                {{-- Tooltip Info --}}
                @if($showBreakdown)
                <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100 text-sm text-gray-600 space-y-2">
                    <p><strong>BM (Bea Masuk):</strong> Tarif berdasarkan jenis barang, umumnya 0-30%</p>
                    <p><strong>PPN:</strong> Pajak Pertambahan Nilai, standar 11%</p>
                    <p><strong>PPnBM:</strong> Pajak Penjualan Barang Mewah, untuk barang tertentu 10-200%</p>
                    <p><strong>PPh:</strong> Pajak Penghasilan Impor, 2.5-10% tergantung NPWP/API</p>
                </div>
                @endif
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @php
                    $tarifInfo = [
                        'tarif_bm' => ['label' => 'BM', 'hint' => 'Bea Masuk'],
                        'tarif_ppn' => ['label' => 'PPN', 'hint' => 'Pajak Pertambahan Nilai'],
                        'tarif_ppnbm' => ['label' => 'PPnBM', 'hint' => 'Pajak Barang Mewah'],
                        'tarif_pph' => ['label' => 'PPh', 'hint' => 'Pajak Penghasilan'],
                    ];
                    @endphp
                    @foreach($tarifInfo as $key => $info)
                    <div class="space-y-2 text-center group">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest" title="{{ $info['hint'] }}">{{ $info['label'] }}</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:model.live="{{ $key }}" class="w-full border border-gray-200 bg-gray-50 rounded-xl text-lg font-black text-center py-4 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            <span class="absolute right-3 top-4 text-[10px] font-bold text-gray-300">%</span>
                        </div>
                        <span class="text-[9px] text-gray-400 hidden group-hover:block">{{ $info['hint'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RECEIPT (COL-5) --}}
        <div class="lg:col-span-5">
            <div class="bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-blue-900/30 border border-white/5 overflow-hidden sticky top-6">
                <div class="p-10 text-center relative overflow-hidden">
                    <div class="absolute -top-32 -right-32 w-80 h-80 bg-blue-600 rounded-full opacity-10 blur-[80px]"></div>
                    
                    <h4 class="text-blue-400 font-black tracking-[0.4em] uppercase text-[9px] mb-6 relative z-10 opacity-80">Total Estimasi Pungutan</h4>
                    <div class="flex flex-col items-center justify-center gap-1 relative z-10">
                        <span class="text-white/10 text-2xl font-black italic tracking-widest">IDR</span>
                        <span class="text-5xl md:text-6xl font-black text-white font-mono tracking-tighter leading-none">{{ number_format($total_pungutan, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="mt-10 pt-10 border-t border-white/5 relative z-10 space-y-5">
                        @php
                        $rows = [
                            ['L' => 'Bea Masuk (BM)', 'V' => $bayar_bm, 'P' => $tarif_bm, 'C' => 'text-blue-400'],
                            ['L' => 'PPN Impor', 'V' => $bayar_ppn, 'P' => $tarif_ppn, 'C' => 'text-slate-300'],
                            ['L' => 'PPnBM', 'V' => $bayar_ppnbm, 'P' => $tarif_ppnbm, 'C' => 'text-slate-500'],
                            ['L' => 'PPh Impor', 'V' => $bayar_pph, 'P' => $tarif_pph, 'C' => 'text-slate-300'],
                        ];
                        @endphp

                        @foreach($rows as $item)
                        <div class="flex justify-between items-center group">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-widest {{ $item['C'] }} opacity-70 group-hover:opacity-100 transition-opacity">{{ $item['L'] }}</span>
                                <span class="text-[9px] text-white/30">({{ $item['P'] }}%)</span>
                            </div>
                            <span class="font-mono font-black text-white text-lg">Rp {{ number_format($item['V'], 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="p-8 bg-white/5 backdrop-blur-2xl border-t border-white/5 space-y-4">
                    <div class="flex justify-between items-center bg-white/5 p-5 rounded-2xl border border-white/5">
                        <span class="text-[10px] font-black text-blue-300 uppercase tracking-widest">Sub-Total (PDRI)</span>
                        <span class="font-mono font-black text-white text-xl">Rp {{ number_format($bayar_ppn + $bayar_ppnbm + $bayar_pph, 0, ',', '.') }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button wire:click="saveToHistory" class="py-4 bg-green-500 text-white font-black rounded-xl hover:bg-green-600 transition-all text-[10px] uppercase tracking-widest flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Simpan
                        </button>
                        <button wire:click="copyToClipboard" 
                                x-data
                                x-on:copy-text.window="navigator.clipboard.writeText($event.detail.text); $dispatch('notify', {type: 'success', message: 'Berhasil disalin ke clipboard!'})"
                                class="py-4 bg-blue-500 text-white font-black rounded-xl hover:bg-blue-600 transition-all text-[10px] uppercase tracking-widest flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            Copy
                        </button>
                    </div>

                    <button wire:click="resetCalculator" class="w-full py-4 bg-white text-slate-900 font-black rounded-xl hover:bg-red-500 hover:text-white transition-all text-[10px] uppercase tracking-[0.2em] flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset Simulasi
                    </button>
                    
                    <p class="text-[9px] text-center text-slate-500 uppercase font-bold tracking-tighter leading-relaxed opacity-50">
                        *Hasil simulasi ini adalah estimasi. Nilai akhir ditentukan oleh kurs resmi Bea Cukai pada dokumen PIB.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
