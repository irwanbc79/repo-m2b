<div class="max-w-7xl mx-auto space-y-6 pb-12">
    @section('header', 'Kalkulator Pabean')

    {{-- Toast Notification --}}
    <div x-data="{ show: false, message: '', type: 'success' }" 
         x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 3000)"
         x-show="show" x-transition
         x-bind:class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         class="fixed top-4 right-4 text-white px-6 py-4 rounded-2xl shadow-2xl z-[9999] flex items-center gap-3 border border-white/20 backdrop-blur-md"
         style="display: none;">
        <span x-text="message" class="font-bold text-sm"></span>
    </div>

    {{-- Top Info Bar --}}
    <div class="bg-white rounded-[2rem] p-5 shadow-xl shadow-blue-900/5 border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="bg-blue-600 p-3 rounded-2xl text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <h2 class="text-xl font-black text-slate-800 tracking-tight leading-none">Simulasi Pabean M2B</h2>
                <p class="text-gray-400 text-[10px] uppercase font-bold tracking-[0.2em] mt-1">Live Tax Exchange Synchronization</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button wire:click="toggleHistory" class="flex items-center gap-2 px-5 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl transition text-xs font-black text-slate-600 uppercase tracking-widest">
                History ({{ count($calculationHistory) }})
            </button>
            <div class="flex items-center gap-6 md:border-l md:pl-6 border-gray-100">
                <div class="text-right">
                    <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Kurs {{ $mata_uang }}</span>
                    <span class="text-2xl font-mono font-black text-blue-900 tracking-tighter leading-none">Rp {{ number_format($kurs, 2, ',', '.') }}</span>
                </div>
                <div class="bg-green-100 px-4 py-2 rounded-xl border border-green-200 flex items-center gap-2 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-[9px] font-black text-green-700 uppercase tracking-widest uppercase">Connected</span>
                </div>
            </div>
        </div>
    </div>

    {{-- History Modal --}}
    @if($showHistory)
    <div class="fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center p-4 backdrop-blur-sm" wire:click.self="toggleHistory">
        <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-2xl w-full max-h-[70vh] overflow-hidden flex flex-col border border-white/20">
            <div class="p-8 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">📜 Riwayat Kalkulasi</h3>
                <button wire:click="toggleHistory" class="text-gray-400 hover:text-red-500 transition-colors text-2xl leading-none">&times;</button>
            </div>
            <div class="p-8 overflow-y-auto flex-1 space-y-4">
                @forelse($calculationHistory as $item)
                <div class="bg-white rounded-3xl p-6 border border-gray-100 hover:border-blue-500 hover:shadow-2xl transition-all flex justify-between items-center group">
                    <div>
                        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest">{{ \Carbon\Carbon::parse($item['timestamp'])->format('d M, H:i') }}</span>
                        <p class="font-black text-slate-800 text-lg font-mono">{{ $item['mata_uang'] }} {{ number_format($item['nilai_barang'], 2) }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-black text-green-600 text-xl font-mono">Rp {{ number_format($item['hasil']['total'], 0, ',', '.') }}</span>
                        <div class="flex gap-2 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button wire:click="loadFromHistory('{{ $item['id'] }}')" class="text-[10px] font-bold text-blue-600 hover:underline">Load</button>
                            <button wire:click="deleteFromHistory('{{ $item['id'] }}')" class="text-[10px] font-bold text-red-500 hover:underline">Hapus</button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-10 text-gray-300 font-bold uppercase tracking-widest text-xs italic">Belum ada riwayat</div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- KOLOM KIRI --}}
        <div class="lg:col-span-7 space-y-6">
            
            {{-- PRESET KATEGORI --}}
            <div class="bg-gradient-to-br from-slate-50 to-blue-50 rounded-[2.5rem] shadow-sm border border-blue-100 p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-sm shadow-lg">⚡</div>
                    <div>
                        <h4 class="font-black text-slate-800 uppercase text-xs tracking-widest leading-none">Preset Kategori Barang</h4>
                        <p class="text-[10px] text-blue-400 font-bold mt-1 uppercase tracking-tighter">Auto-fill tarif standar pabean</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    @foreach($presets as $key => $preset)
                    <button wire:click="applyPreset('{{ $key }}')" 
                            class="px-3 py-3 rounded-2xl text-[10px] font-black uppercase tracking-tight transition-all duration-300 border {{ $selectedPreset === $key ? 'bg-blue-600 text-white border-blue-600 shadow-xl shadow-blue-200 scale-105' : 'bg-white text-slate-600 hover:bg-blue-100 hover:border-blue-300 border-gray-100' }}">
                        {{ $preset['name'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- FORM INPUT --}}
            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-blue-900/5 border border-gray-100 p-8 md:p-10 space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Pilih Mata Uang</label>
                        <select wire:model.live="mata_uang" class="w-full border-gray-100 bg-gray-50/50 rounded-2xl text-sm font-black text-slate-700 focus:ring-blue-500 py-4 shadow-inner">
                            @foreach($currencies as $code => $currency)
                            <option value="{{ $code }}">{{ $currency['flag'] }} {{ $code }} - {{ $currency['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nilai Kurs Berjalan</label>
                        <div class="relative">
                            <input type="number" wire:model.live="kurs" class="w-full border-gray-100 bg-gray-50/50 rounded-2xl text-lg font-mono font-black text-blue-900 py-4 pl-6 shadow-inner transition-all" {{ $is_auto_kurs ? 'readonly' : '' }}>
                            @if($is_auto_kurs)
                            <div class="absolute right-4 top-4 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-[8px] font-black border border-blue-500 shadow-md">AUTO</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-sm font-black text-slate-800 tracking-tight ml-1 uppercase text-[10px]">Nilai Barang (CIF)</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-8 flex items-center pointer-events-none text-slate-300 font-black text-3xl group-focus-within:text-blue-600 transition-colors">{{ $mata_uang }}</div>
                        <input type="number" step="0.01" wire:model.live.debounce.300ms="nilai_barang" 
                               class="w-full pl-32 pr-8 py-8 border-4 border-gray-50 rounded-[2rem] text-5xl font-black text-slate-900 focus:ring-0 focus:border-blue-600 transition-all placeholder:text-gray-100 shadow-inner bg-gray-50/50" 
                               placeholder="0.00">
                    </div>
                </div>

                <div class="bg-blue-900 rounded-3xl p-8 flex justify-between items-center shadow-2xl shadow-blue-900/20">
                    <div class="relative z-10">
                        <p class="text-[9px] font-black text-blue-400 uppercase tracking-[0.4em]">Equivalent IDR</p>
                        <p class="text-lg text-white font-black mt-1 uppercase tracking-tight">Nilai Pabean</p>
                    </div>
                    <div class="text-right relative z-10">
                        <span class="text-3xl md:text-4xl font-black text-white font-mono tracking-tighter">Rp {{ number_format($nilai_pabean, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- PARAMETER TARIF --}}
            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-blue-900/5 border border-gray-100 p-8 md:p-10">
                <div class="flex items-center gap-3 border-b border-gray-50 pb-6 mb-10">
                    <span class="bg-slate-900 text-white w-7 h-7 rounded-xl flex items-center justify-center text-xs font-black">02</span>
                    <h4 class="font-black text-slate-800 uppercase text-xs tracking-widest">Detail Tarif Pajak (%)</h4>
                    <button wire:click="toggleBreakdown" class="ml-auto text-[10px] font-black text-blue-600 hover:underline uppercase tracking-widest">Info Tarif</button>
                </div>

                @if($showBreakdown)
                <div class="mb-10 p-5 bg-blue-50 rounded-2xl border border-blue-100 text-xs text-slate-600 leading-relaxed italic animate-fade-in">
                    Parameter tarif dihitung dari Nilai Pabean (BM) dan Nilai Impor (PPN, PPnBM, PPh) sesuai UU Harmonisasi Peraturan Perpajakan.
                </div>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    @foreach(['tarif_bm' => 'BM', 'tarif_ppn' => 'PPN', 'tarif_ppnbm' => 'PPnBM', 'tarif_pph' => 'PPh'] as $key => $label)
                    <div class="space-y-3 text-center group">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ $label }}</label>
                        <div class="relative">
                            <input type="number" step="0.1" wire:model.live="{{ $key }}" class="w-full border-2 border-gray-50 bg-gray-50/50 rounded-2xl text-2xl font-black text-center py-5 focus:bg-white focus:ring-0 focus:border-blue-600 transition-all shadow-inner">
                            <span class="absolute right-4 top-6 text-[10px] font-black text-gray-300">%</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: RECEIPT STYLE --}}
        <div class="lg:col-span-5">
            <div class="bg-slate-900 rounded-[3rem] shadow-[0_35px_60px_-15px_rgba(15,44,89,0.3)] border-8 border-white/5 overflow-hidden sticky top-8">
                <div class="p-12 text-center relative overflow-hidden">
                    <div class="absolute -top-32 -right-32 w-80 h-80 bg-blue-600 rounded-full opacity-10 blur-[80px]"></div>
                    <h4 class="text-blue-400 font-black tracking-[0.5em] uppercase text-[10px] mb-10 relative z-10 opacity-70">Total Estimasi Pungutan</h4>
                    
                    <div class="flex flex-col items-center justify-center gap-1 relative z-10 mb-12">
                        <span class="text-white/20 text-3xl font-black italic tracking-widest leading-none">IDR</span>
                        <span class="text-6xl md:text-7xl font-black text-white font-mono tracking-tighter leading-none block mt-2">{{ number_format($total_pungutan, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="mt-10 pt-12 border-t border-white/10 relative z-10 space-y-8">
                        @php
                        $results = [
                            ['L' => 'Bea Masuk (BM)', 'V' => $bayar_bm, 'P' => $tarif_bm, 'C' => 'text-blue-400'],
                            ['L' => 'PPN Impor', 'V' => $bayar_ppn, 'P' => $tarif_ppn, 'C' => 'text-slate-300'],
                            ['L' => 'PPh Impor', 'V' => $bayar_pph, 'P' => $tarif_pph, 'C' => 'text-slate-300'],
                        ];
                        @endphp
                        @foreach($results as $item)
                        <div class="flex justify-between items-center group transition-all">
                            <div class="flex flex-col items-start gap-1">
                                <span class="text-[10px] font-black uppercase tracking-widest {{ $item['C'] }} opacity-70 group-hover:opacity-100 transition-opacity">{{ $item['L'] }}</span>
                                <span class="text-[9px] text-white/20 font-bold uppercase tracking-widest">Rate: {{ $item['P'] }}%</span>
                            </div>
                            <span class="font-mono font-black text-white text-2xl group-hover:scale-110 transition-transform">Rp {{ number_format($item['V'], 0, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="p-10 bg-white/5 backdrop-blur-3xl border-t border-white/10 space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <button wire:click="saveToHistory" class="py-5 bg-emerald-500 text-white font-black rounded-2xl hover:bg-emerald-600 transition-all text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 shadow-xl shadow-emerald-900/20 active:scale-95">
                            💾 Simpan
                        </button>
                        <button wire:click="copyToClipboard" 
                                x-data x-on:copy-text.window="navigator.clipboard.writeText($event.detail.text); $dispatch('notify', {type: 'success', message: 'Berhasil disalin!'})"
                                class="py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 transition-all text-[10px] uppercase tracking-widest flex items-center justify-center gap-2 shadow-xl shadow-blue-900/20 active:scale-95">
                            📋 Copy Result
                        </button>
                    </div>

                    <button wire:click="resetCalculator" class="w-full py-5 bg-white text-slate-900 font-black rounded-2xl hover:bg-red-600 hover:text-white transition-all text-[10px] uppercase tracking-[0.3em] flex items-center justify-center gap-3 active:scale-95">
                        RESET SIMULASI
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>