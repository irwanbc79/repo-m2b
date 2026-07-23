<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-blue-950 flex items-center gap-2">
                <span>📦 Create New Shipment Booking</span>
            </h2>
            <p class="text-gray-500 text-sm">Submit your freight order request to M2B Logistics team.</p>
        </div>
        <a href="{{ route('customer.shipments.index') }}" class="inline-flex items-center gap-1.5 text-gray-600 hover:text-blue-900 text-sm font-bold bg-white px-4 py-2 rounded-xl border border-gray-200 shadow-sm transition">
            &larr; Batal & Kembali
        </a>
    </div>

    {{-- STEPPER PROGRESS BAR UI --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
            <div class="flex items-center gap-3 p-2 bg-blue-50 rounded-xl border border-blue-200">
                <span class="w-8 h-8 rounded-lg bg-blue-900 text-white font-black flex items-center justify-center text-sm shadow">1</span>
                <div class="text-left">
                    <p class="text-xs font-black text-blue-950 uppercase">Rute & Moda</p>
                    <p class="text-[10px] text-blue-700">Origin, Dest & Mode</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl border border-gray-100">
                <span class="w-8 h-8 rounded-lg bg-gray-200 text-gray-700 font-bold flex items-center justify-center text-sm">2</span>
                <div class="text-left">
                    <p class="text-xs font-bold text-gray-700 uppercase">Detail Kargo</p>
                    <p class="text-[10px] text-gray-500">Dimensi, Weight, CBM</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl border border-gray-100">
                <span class="w-8 h-8 rounded-lg bg-gray-200 text-gray-700 font-bold flex items-center justify-center text-sm">3</span>
                <div class="text-left">
                    <p class="text-xs font-bold text-gray-700 uppercase">HS Code & Pajak</p>
                    <p class="text-[10px] text-gray-500">BTKI & Catatan</p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl border border-gray-100">
                <span class="w-8 h-8 rounded-lg bg-gray-200 text-gray-700 font-bold flex items-center justify-center text-sm">4</span>
                <div class="text-left">
                    <p class="text-xs font-bold text-gray-700 uppercase">Submit Booking</p>
                    <p class="text-[10px] text-gray-500">Proses ke Admin</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Pengingat kontekstual data perusahaan --}}
    @php($__bookCust = auth()->user()?->customer)
    @if($__bookCust && ($__bookDq = $__bookCust->dataQuality())['level'] !== 'good')
    <div class="rounded-2xl border {{ $__bookDq['level'] === 'bad' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200' }} p-4 flex items-start gap-3 shadow-sm">
        <svg class="w-6 h-6 shrink-0 mt-0.5 {{ $__bookDq['level'] === 'bad' ? 'text-red-500' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
        <div class="flex-1">
            <p class="text-sm font-bold {{ $__bookDq['level'] === 'bad' ? 'text-red-800' : 'text-amber-800' }}">Data Perusahaan Anda Belum Lengkap</p>
            <p class="text-xs {{ $__bookDq['level'] === 'bad' ? 'text-red-700' : 'text-amber-700' }} mt-0.5">
                Booking tetap dapat dikirim, tetapi proses dokumen kepabeanan/invoice membutuhkan kelengkapan nama resmi, NPWP, &amp; alamat perusahaan.
            </p>
            <a href="{{ route('customer.profile') }}" class="inline-flex items-center gap-1 mt-2 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition shadow {{ $__bookDq['level'] === 'bad' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700' }}">
                👤 Lengkapi Data Profil
            </a>
        </div>
    </div>
    @endif

    {{-- GRID FORM & LIVE SUMMARY SIDEBAR --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- FORM UTAMA (2 COLS) --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 md:p-8 space-y-6">
                    
                    {{-- 1. PRESET RUTE CEPAT & ORIGIN/DESTINATION --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-xs font-black uppercase text-blue-900 tracking-wider">⚡ Preset Rute Populer</label>
                            <span class="text-[11px] text-gray-400">Klik untuk isi otomatis</span>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <button type="button" wire:click="setQuickRoute('Shanghai, China', 'Jakarta, Indonesia')" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 font-semibold px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                <span>🇨🇳 Shanghai</span> ➔ <span>🇮🇩 Jakarta</span>
                            </button>
                            <button type="button" wire:click="setQuickRoute('Guangzhou, China', 'Surabaya, Indonesia')" class="text-xs bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 font-semibold px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                <span>🇨🇳 Guangzhou</span> ➔ <span>🇮🇩 Surabaya</span>
                            </button>
                            <button type="button" wire:click="setQuickRoute('Ningbo, China', 'Semarang, Indonesia')" class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                <span>🇨🇳 Ningbo</span> ➔ <span>🇮🇩 Semarang</span>
                            </button>
                            <button type="button" wire:click="setQuickRoute('Singapore', 'Jakarta, Indonesia')" class="text-xs bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-semibold px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                <span>🇸🇬 Singapore</span> ➔ <span>🇮🇩 Jakarta</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Origin (Kota / Pelabuhan Asal) <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live="origin" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-blue-600 focus:ring-0" placeholder="e.g. Shanghai, China">
                                @error('origin') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Destination (Kota / Pelabuhan Tujuan) <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live="destination" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:border-blue-600 focus:ring-0" placeholder="e.g. Jakarta, Indonesia">
                                @error('destination') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- 2. MODA & SERVICE TYPE --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Service Type</label>
                            <select wire:model.live="service_type" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold bg-gray-50 focus:border-blue-600 focus:ring-0">
                                <option value="import">📥 Import</option>
                                <option value="export">📤 Export</option>
                                <option value="domestic">🚚 Domestic</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Transport Mode</label>
                            <select wire:model.live="shipment_type" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold bg-gray-50 focus:border-blue-600 focus:ring-0">
                                <option value="sea">🚢 Sea Freight (Laut)</option>
                                <option value="air">✈️ Air Freight (Udara)</option>
                                <option value="land">🚛 Land Freight (Darat)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Container Mode</label>
                            <select wire:model.live="container_mode" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold bg-gray-50 focus:border-blue-600 focus:ring-0">
                                <option value="LCL">📦 LCL (Less Container)</option>
                                <option value="FCL">🚢 FCL (Full Container)</option>
                                <option value="Non-Container">🏗️ Non-Container / Bulk</option>
                            </select>
                        </div>
                    </div>

                    {{-- 3. CARGO DETAILS --}}
                    <div class="bg-gradient-to-br from-blue-50/80 to-indigo-50/50 p-5 rounded-2xl border border-blue-100 space-y-4">
                        <h4 class="text-xs font-black text-blue-900 uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Detail Barang & Dimensi Kargo
                        </h4>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Deskripsi Barang & Container Info</label>
                            <input type="text" wire:model.live="container_info" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 text-sm bg-white focus:border-blue-600 focus:ring-0" placeholder="e.g. Spareparts Mesin / 2x40HC atau Dimensi 120x100x80 cm">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Jumlah (Qty) <span class="text-red-500">*</span></label>
                                <input type="number" wire:model.live="pieces" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm text-center font-bold bg-white focus:border-blue-600 focus:ring-0" placeholder="1">
                                @error('pieces') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- SEARCHABLE KEMASAN COMBOBOX --}}
                            <div x-data="packageTypeSelect()" class="relative">
                                <label class="block text-xs font-bold text-gray-600 mb-1">Kemasan <span class="text-red-500">*</span></label>
                                <button type="button" @click="toggle" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm font-bold bg-white text-left flex items-center justify-between focus:border-blue-600 focus:ring-0">
                                    <span x-text="selectedLabel || '-- Pilih Jenis Kemasan --'" :class="selectedLabel ? 'text-gray-900 font-bold' : 'text-gray-400 font-normal'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>

                                <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 w-full mt-1 bg-white border-2 border-blue-500 rounded-xl shadow-2xl overflow-hidden max-h-72 flex flex-col">
                                    <div class="p-2 border-b border-gray-100 bg-gray-50">
                                        <input type="text" x-model="search" x-ref="searchInput" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="🔍 Cari kemasan (Ctn, Box, Drum, Unit)..." autocomplete="off">
                                    </div>
                                    <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
                                        <template x-for="item in filteredOptions" :key="item.value">
                                            <div @click="select(item)" class="px-3 py-2 text-xs hover:bg-blue-50 cursor-pointer flex items-center justify-between transition" :class="selected === item.value ? 'bg-blue-50/80 font-bold text-blue-900' : 'text-gray-700'">
                                                <div>
                                                    <span class="font-bold" x-text="item.label"></span>
                                                    <span class="text-[10px] text-gray-400 block" x-text="item.group"></span>
                                                </div>
                                                <span x-show="selected === item.value" class="text-blue-600 font-bold text-sm">✓</span>
                                            </div>
                                        </template>
                                        <div x-show="filteredOptions.length === 0" class="p-4 text-center text-xs text-gray-400 italic">
                                            Tidak ada jenis kemasan ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Berat Total (Kg)</label>
                                <input type="number" step="0.01" wire:model.live="weight" class="w-full border-2 border-gray-200 rounded-xl px-3 py-2 text-sm text-right font-bold bg-white focus:border-blue-600 focus:ring-0" placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Volume Total (CBM / M³)</label>
                            <input type="number" step="0.001" wire:model.live="volume" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 text-sm text-right font-bold bg-white focus:border-blue-600 focus:ring-0" placeholder="0.000">
                        </div>

                        {{-- HS CODE AUTOCOMPLETE WITH 8 DIGIT SUPPORT --}}
                        <div x-data="hsCodeAutocomplete()" class="relative pt-1">
                            <label class="block text-xs font-bold text-gray-600 mb-1">HS Code BTKI (Opsional / Direkomendasikan)</label>
                            <div class="relative">
                                <input type="text" x-model="search" @input.debounce.300ms="fetchResults" @focus="fetchResults" @click.away="showDropdown = false" class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 text-sm font-mono bg-white focus:border-blue-600 focus:ring-0" placeholder="Ketik 8 digit (e.g. 23096060 / 2309.60.60) atau nama barang..." autocomplete="off">
                                <div x-show="isLoading" class="absolute right-3 top-2.5">
                                    <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            </div>
                            
                            {{-- Autocomplete Dropdown List --}}
                            <div x-show="showDropdown && results.length > 0" x-cloak class="absolute z-50 w-full mt-1 bg-white border-2 border-blue-500 rounded-xl shadow-2xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                                <template x-for="item in results" :key="item.hs_code">
                                    <div @click="selectItem(item)" class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer transition flex items-center justify-between gap-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-sm font-black text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200" x-text="item.hs_code"></span>
                                                <span x-show="item.import_duty" class="text-[10px] bg-green-100 text-green-700 font-bold px-1.5 py-0.5 rounded" x-text="'BM ' + item.import_duty + (isFinite(item.import_duty) ? '%' : '')"></span>
                                            </div>
                                            <p class="text-xs text-gray-700 line-clamp-1 mt-1 font-medium" x-text="item.description_id || item.description_en"></p>
                                        </div>
                                        <span class="text-blue-600 text-xs font-bold shrink-0">Pilih ➔</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- 4. CATATAN / SPECIAL INSTRUCTIONS --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Catatan / Instruksi Khusus</label>
                        <textarea wire:model.live="notes" rows="3" class="w-full border-2 border-gray-200 rounded-xl p-3 text-sm focus:border-blue-600 focus:ring-0" placeholder="Contoh: Barang butuh kontainer pendingin (Reefer) / Penanganan khusus muatan berharga..."></textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- LIVE SUMMARY STICKY SIDEBAR (1 COL) --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-xl border-2 border-blue-600 p-6 sticky top-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <h3 class="font-black text-sm uppercase tracking-wider text-blue-950 flex items-center gap-2">
                        <span class="text-lg">📋</span>
                        <span>Ringkasan Order</span>
                    </h3>
                    <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 border border-blue-200 rounded-full text-[10px] font-black uppercase">Live Preview</span>
                </div>

                {{-- Visual Route Badge --}}
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200 space-y-1.5 shadow-sm">
                    <div class="text-[10px] font-black text-blue-900 uppercase tracking-wider">Rute Pengiriman</div>
                    <div class="flex items-center justify-between text-sm font-black text-slate-900">
                        <span class="text-blue-950 font-extrabold">{{ $origin ?: 'Kota / Pelabuhan Asal' }}</span>
                        <span class="text-blue-600 font-bold text-base">➔</span>
                        <span class="text-blue-950 font-extrabold">{{ $destination ?: 'Kota / Pelabuhan Tujuan' }}</span>
                    </div>
                </div>

                {{-- Mode & Cargo Specs --}}
                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span class="text-gray-500 font-bold">Layanan & Moda:</span>
                        <span class="font-black text-blue-900 uppercase">{{ $service_type }} ({{ $shipment_type }})</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span class="text-gray-500 font-bold">Moda Kontainer:</span>
                        <span class="font-black text-purple-900 uppercase bg-purple-50 px-2 py-0.5 rounded border border-purple-200">{{ $container_mode }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span class="text-gray-500 font-bold">Jumlah & Kemasan:</span>
                        <span class="font-black text-slate-900">{{ $pieces ?: 0 }} {{ $package_type }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span class="text-gray-500 font-bold">Berat Total:</span>
                        <span class="font-black text-slate-900">{{ $weight ? number_format($weight, 2, ',', '.') . ' Kg' : '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span class="text-gray-500 font-bold">Volume Total:</span>
                        <span class="font-black text-slate-900">{{ $volume ? number_format($volume, 3, ',', '.') . ' CBM' : '-' }}</span>
                    </div>
                    @if($hs_code)
                    <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                        <span class="text-gray-500 font-bold">HS Code BTKI:</span>
                        <span class="font-mono font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ $hs_code }}</span>
                    </div>
                    @endif
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button wire:click="save" wire:loading.attr="disabled" class="w-full bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-black py-4 px-6 rounded-xl shadow-lg transform transition hover:-translate-y-0.5 flex items-center justify-center gap-2 text-base">
                        <svg wire:loading.remove wire:target="save" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <svg wire:loading wire:target="save" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span wire:loading.remove wire:target="save">Kirim Booking Sekarang</span>
                        <span wire:loading wire:target="save">Memproses Booking...</span>
                    </button>
                    <p class="text-[11px] text-gray-500 text-center mt-2 font-medium">Tim operasional M2B akan langsung memproses booking Anda.</p>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- JAVASCRIPT HELPERS UNTUK HS CODE & SEARCHABLE KEMASAN --}}
<script>
function hsCodeAutocomplete() {
    return {
        search: '',
        results: [],
        showDropdown: false,
        isLoading: false,
        init() {
            this.search = this.$wire.get('hs_code') || '';
            this.$watch('$wire.hs_code', (val) => {
                if (val !== undefined && val !== this.search) {
                    this.search = val || '';
                }
            });
        },
        async fetchResults() {
            let q = (this.search || '').trim();

            // Auto-format 8 digit tanpa titik e.g. 23096060 -> 2309.60.60
            let cleanDigits = q.replace(/[^0-9]/g, '');
            if (cleanDigits.length === 8 && !q.includes('.')) {
                q = cleanDigits.substring(0,4) + '.' + cleanDigits.substring(4,6) + '.' + cleanDigits.substring(6,8);
                this.search = q;
            }

            this.$wire.set('hs_code', q);

            if (q.length < 2) {
                this.results = [];
                this.showDropdown = false;
                return;
            }

            this.isLoading = true;
            this.showDropdown = true;
            try {
                const response = await fetch(`/api/hs-codes/search?q=${encodeURIComponent(q)}`);
                const data = await response.json();
                this.results = data || [];
            } catch (e) {
                this.results = [];
            } finally {
                this.isLoading = false;
            }
        },
        selectItem(item) {
            this.search = item.hs_code;
            this.showDropdown = false;
            this.$wire.set('hs_code', item.hs_code);
        }
    }
}

function packageTypeSelect() {
    return {
        open: false,
        search: '',
        selected: '',
        options: [
            { value: 'Colli', label: 'Colli - Colli', group: '📦 Packaging' },
            { value: 'Ctn', label: 'Ctn - Cartons', group: '📦 Packaging' },
            { value: 'Box', label: 'Box - Kotak', group: '📦 Packaging' },
            { value: 'Pkgs', label: 'Pkgs - Packages', group: '📦 Packaging' },
            { value: 'Plt', label: 'Plt - Pallet', group: '📦 Packaging' },
            { value: 'Crate', label: 'Crate - Krat', group: '📦 Packaging' },
            { value: 'Case', label: 'Case - Peti', group: '📦 Packaging' },
            { value: 'Skid', label: 'Skid - Alas Kayu', group: '📦 Packaging' },
            { value: 'Bdl', label: 'Bdl - Bundle', group: '🔗 Bundle/Gulungan' },
            { value: 'Bale', label: 'Bale - Bal', group: '🔗 Bundle/Gulungan' },
            { value: 'Coil', label: 'Coil - Gulungan', group: '🔗 Bundle/Gulungan' },
            { value: 'Roll', label: 'Roll - Roll', group: '🔗 Bundle/Gulungan' },
            { value: 'Reel', label: 'Reel - Kumparan', group: '🔗 Bundle/Gulungan' },
            { value: 'Pcs', label: 'Pcs - Pieces', group: '🔢 Satuan' },
            { value: 'Unit', label: 'Unit - Unit', group: '🔢 Satuan' },
            { value: 'Set', label: 'Set - Set', group: '🔢 Satuan' },
            { value: 'Pair', label: 'Pair - Pasang', group: '🔢 Satuan' },
            { value: 'Dozen', label: 'Dozen - Lusin', group: '🔢 Satuan' },
            { value: 'Ea', label: 'Ea - Each', group: '🔢 Satuan' },
            { value: 'Bag', label: 'Bag - Tas', group: '🛢️ Wadah/Container' },
            { value: 'Sack', label: 'Sack - Karung', group: '🛢️ Wadah/Container' },
            { value: 'Drum', label: 'Drum - Drum', group: '🛢️ Wadah/Container' },
            { value: 'Barrel', label: 'Barrel - Barel', group: '🛢️ Wadah/Container' },
            { value: 'IBC', label: 'IBC - IBC Tank', group: '🛢️ Wadah/Container' },
            { value: 'Jerrycan', label: 'Jerrycan - Jerigen', group: '🛢️ Wadah/Container' },
            { value: 'Bottle', label: 'Bottle - Botol', group: '🛢️ Wadah/Container' },
            { value: 'Can', label: 'Can - Kaleng', group: '🛢️ Wadah/Container' },
            { value: 'Cylinder', label: 'Cylinder - Tabung Gas', group: '🛢️ Wadah/Container' },
            { value: 'Tubes', label: 'Tubes - Tabung', group: '🛢️ Wadah/Container' },
            { value: 'Tote', label: 'Tote - Tote Bag', group: '🛢️ Wadah/Container' },
            { value: 'Kg', label: 'Kg - Kilogram', group: '⚖️ Berat' },
            { value: 'Ton', label: 'Ton - Metric Ton', group: '⚖️ Berat' },
            { value: 'MT', label: 'MT - Metric Ton', group: '⚖️ Berat' },
            { value: 'Lbs', label: 'Lbs - Pounds', group: '⚖️ Berat' },
            { value: 'Gram', label: 'Gram - Gram', group: '⚖️ Berat' },
            { value: 'M3', label: 'M3 - Cubic Meter', group: '📐 Volume' },
            { value: 'CBM', label: 'CBM - Cubic Meter', group: '📐 Volume' },
            { value: 'Ltr', label: 'Ltr - Liter', group: '📐 Volume' },
            { value: 'Gal', label: 'Gal - Gallon', group: '📐 Volume' },
            { value: 'CFT', label: 'CFT - Cubic Feet', group: '📐 Volume' },
            { value: 'Mtr', label: 'Mtr - Meter', group: '📏 Panjang/Luas' },
            { value: 'Ft', label: 'Ft - Feet', group: '📏 Panjang/Luas' },
            { value: 'Yard', label: 'Yard - Yard', group: '📏 Panjang/Luas' },
            { value: 'SQM', label: 'SQM - Square Meter', group: '📏 Panjang/Luas' },
            { value: 'SQF', label: 'SQF - Square Feet', group: '📏 Panjang/Luas' },
            { value: 'TEU', label: 'TEU - 20ft Container', group: '🚢 Logistik' },
            { value: 'FEU', label: 'FEU - 40ft Container', group: '🚢 Logistik' },
            { value: 'Lot', label: 'Lot - Lot', group: '🚢 Logistik' },
            { value: 'Shipment', label: 'Shipment - Pengiriman', group: '🚢 Logistik' },
            { value: 'Other', label: 'Other - Lainnya', group: '📋 Lainnya' }
        ],
        init() {
            this.selected = this.$wire.get('package_type') || 'Colli';
            this.$watch('$wire.package_type', (val) => {
                if (val && val !== this.selected) {
                    this.selected = val;
                }
            });
        },
        get selectedLabel() {
            const found = this.options.find(o => o.value === this.selected);
            return found ? found.label : this.selected;
        },
        get filteredOptions() {
            if (!this.search) return this.options;
            const s = this.search.toLowerCase();
            return this.options.filter(o => 
                o.label.toLowerCase().includes(s) || 
                o.value.toLowerCase().includes(s) || 
                o.group.toLowerCase().includes(s)
            );
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.search = '';
                this.$nextTick(() => {
                    if (this.$refs.searchInput) this.$refs.searchInput.focus();
                });
            }
        },
        select(item) {
            this.selected = item.value;
            this.$wire.set('package_type', item.value);
            this.open = false;
        }
    }
}
</script>