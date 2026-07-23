<div class="max-w-7xl mx-auto space-y-6 pb-12">
    @section('header', 'HS Code Explorer BTKI 2022')

    {{-- HEADER SECTION WITH KUM HS MODAL --}}
    <div x-data="{ showKum: false }">
        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-200 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-black uppercase text-blue-900 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full tracking-wider">BTKI 2022 Official Database</span>
                        <span class="text-xs font-bold text-gray-500">• {{ number_format(\DB::table('hs_codes')->count()) }} Kode HS Active</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <span>🔍 HS Code Explorer</span>
                    </h1>
                    <p class="text-slate-600 text-sm font-medium">
                        Cari kode tarif Bea Masuk &amp; Lartas resmi Kepabeanan Indonesia. Dukungan pencarian 8-digit tanpa titik &amp; nama komoditas.
                    </p>
                </div>
                
                {{-- KUM HS Toggle Button --}}
                <div class="shrink-0">
                    <button @click="showKum = true" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2">
                        <span>📚</span>
                        <span>Lihat KUM HS (Panduan Klasifikasi)</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- KUM HS MODAL OVERLAY --}}
        <div x-show="showKum" x-cloak class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4" @click.self="showKum = false">
            <div class="bg-white rounded-3xl shadow-2xl max-w-3xl w-full max-h-[85vh] overflow-hidden flex flex-col border border-gray-100">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-emerald-900 to-teal-900 text-white">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📚</span>
                        <div>
                            <h3 class="font-black text-base uppercase tracking-wider text-emerald-200">KUM HS - Ketentuan Umum Menginterpretasikan HS</h3>
                            <p class="text-xs text-emerald-100/80">Panduan resmi Bea Cukai untuk mengklasifikasikan barang secara sah</p>
                        </div>
                    </div>
                    <button @click="showKum = false" class="text-white/70 hover:text-white transition text-2xl font-bold leading-none">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4 bg-slate-50">
                    @foreach(DB::table('hs_kum')->orderBy('rule_number')->get() as $kum)
                    <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm space-y-2" x-data="{ expanded: false }">
                        <h4 class="font-black text-sm text-emerald-800">{{ $kum->title }}</h4>
                        <div class="text-xs text-slate-700 leading-relaxed">
                            <span x-show="!expanded">{{ Str::limit($kum->content, 180) }}</span>
                            <span x-show="expanded" x-cloak>{{ $kum->content }}</span>
                            @if(strlen($kum->content) > 180)
                            <button @click="expanded = !expanded" class="text-emerald-700 hover:text-emerald-900 text-xs font-bold block mt-2">
                                <span x-show="!expanded">Selengkapnya ➔</span>
                                <span x-show="expanded">Sembunyikan</span>
                            </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="p-4 bg-gray-100 border-t border-gray-200 text-right">
                    <button @click="showKum = false" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition">
                        Tutup Modal
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- SEARCH & QUICK FILTER CARD --}}
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-gray-200 space-y-6">
        <div class="space-y-2">
            <label class="block text-xs font-black uppercase text-blue-950 tracking-wider">Cari Kode HS / Nama Barang</label>
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search" class="w-full border-2 border-gray-200 rounded-2xl pl-11 pr-24 py-3.5 text-base font-medium focus:border-blue-600 focus:ring-0 transition placeholder:text-gray-400" placeholder="Ketik 8 digit (cth: 23096060 / 2309.60.60) atau nama komoditas (cth: Laptop, Genset, Sepatu)...">
                <div class="absolute left-4 top-4 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                @if($search || $selectedChapter)
                <button wire:click="$set('search', ''); $set('selectedChapter', '')" class="absolute right-3 top-2.5 bg-red-100 hover:bg-red-200 text-red-700 font-bold px-3 py-1.5 rounded-xl text-xs transition">
                    ✕ Clear Filter
                </button>
                @endif
            </div>
        </div>

        {{-- QUICK FILTER CHAPTER CHIPS --}}
        <div class="border-t border-gray-100 pt-4 space-y-2">
            <span class="text-xs font-black text-blue-900 uppercase tracking-wider block">⚡ Quick Filter Bab BTKI Populer</span>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="filterByChapter('85')" class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ $selectedChapter == '85' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100' }}">
                    ⚡ Bab 85: Elektronik &amp; HP
                </button>
                <button type="button" wire:click="filterByChapter('84')" class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ $selectedChapter == '84' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-purple-50 text-purple-800 border-purple-200 hover:bg-purple-100' }}">
                    ⚙️ Bab 84: Mesin &amp; Sparepart
                </button>
                <button type="button" wire:click="filterByChapter('39')" class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ $selectedChapter == '39' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                    🧪 Bab 39: Plastik &amp; Bahan Baku
                </button>
                <button type="button" wire:click="filterByChapter('62')" class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ $selectedChapter == '62' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}">
                    👕 Bab 62: Pakaian &amp; Tekstil
                </button>
                <button type="button" wire:click="filterByChapter('87')" class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ $selectedChapter == '87' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-indigo-50 text-indigo-800 border-indigo-200 hover:bg-indigo-100' }}">
                    🚗 Bab 87: Otomotif &amp; Kendaraan
                </button>
                <button type="button" wire:click="filterByChapter('30')" class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ $selectedChapter == '30' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-teal-50 text-teal-800 border-teal-200 hover:bg-teal-100' }}">
                    💊 Bab 30: Farmasi &amp; Obat
                </button>
            </div>
        </div>

        {{-- STATUS INDICATOR --}}
        @if($search)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-900 font-bold flex items-center justify-between">
            <span>📌 Hasil Pencarian: "{{ $search }}" ({{ $results->total() }} kode HS ditemukan)</span>
            <span class="text-[11px] text-blue-700">Dukungan format bertitik &amp; 8-digit murni</span>
        </div>
        @elseif($selectedChapter)
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-xs text-emerald-900 font-bold flex items-center justify-between">
            <span>📁 Filter Bab BTKI {{ $selectedChapter }} ({{ $results->total() }} kode HS ditemukan)</span>
        </div>
        @endif
    </div>

    {{-- HIERARCHY CLASSIFICATION DRAWER / PANEL --}}
    @if($selectedCode && is_array($hierarchy) && count($hierarchy) > 0)
    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-3xl p-6 border-2 border-emerald-500 shadow-xl space-y-6">
        <div class="flex items-center justify-between border-b border-emerald-200 pb-4">
            <h3 class="text-base font-black text-emerald-950 flex items-center gap-2">
                <span>📊 Hierarki Klasifikasi:</span>
                <span class="font-mono text-lg text-emerald-800 bg-white px-3 py-0.5 rounded-xl border border-emerald-300">{{ $selectedCode }}</span>
            </h3>
            <button wire:click="closeHierarchy" class="bg-white hover:bg-red-50 text-gray-600 hover:text-red-600 font-bold px-3 py-1.5 rounded-xl border border-gray-200 text-xs transition">
                ✕ Tutup
            </button>
        </div>

        {{-- Section --}}
        @if(isset($hierarchy['section']))
        <div class="bg-amber-50 rounded-2xl p-4 border border-amber-200 space-y-1">
            <div class="font-bold text-xs text-amber-900">📦 Bagian {{ $hierarchy['section']['number'] }}</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                <span class="font-bold text-amber-950">{{ $hierarchy['section']['title_id'] }}</span>
                <span class="text-amber-800 italic">{{ $hierarchy['section']['title_en'] }}</span>
            </div>
        </div>
        @endif

        {{-- Chapter --}}
        @if(isset($hierarchy['chapter']))
        <div class="bg-blue-50 rounded-2xl p-4 border border-blue-200 space-y-1">
            <div class="font-bold text-xs text-blue-900">📁 Bab {{ $hierarchy['chapter']['number'] }}</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                <span class="font-bold text-blue-950">{{ $hierarchy['chapter']['title_id'] }}</span>
                <span class="text-blue-800 italic">{{ $hierarchy['chapter']['title_en'] }}</span>
            </div>
        </div>
        @endif

        {{-- Levels Tree --}}
        @if(isset($hierarchy['levels']) && count($hierarchy['levels']) > 0)
        <div class="space-y-2 pl-2 md:pl-4">
            @foreach($hierarchy['levels'] as $level)
            <div class="p-3 rounded-xl border-l-4 {{ (isset($level['is_selected']) && $level['is_selected']) ? 'bg-red-50 border-red-500 shadow-md' : 'bg-white border-blue-400' }} space-y-1 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-black text-gray-900">{{ $level['code'] }}</span>
                    <span class="text-[10px] bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded">{{ $level['level'] }} Digit</span>
                    @if(isset($level['is_selected']) && $level['is_selected'])
                    <span class="text-[10px] bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded">← Dipilih</span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    <span class="text-gray-800 font-medium">{{ $level['description_id'] ?? '-' }}</span>
                    <span class="text-gray-500 italic">{{ $level['description_en'] ?? '-' }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Explanatory Note --}}
        @php $explanatoryNote = $this->getExplanatoryNote($selectedCode); @endphp
        @if($explanatoryNote)
        <div class="bg-purple-50 rounded-2xl p-5 border border-purple-200 space-y-2">
            <h4 class="font-bold text-xs text-purple-900">📖 Catatan Penjelasan (Explanatory Notes)</h4>
            @if($explanatoryNote->note_title)<p class="font-bold text-xs text-purple-800">{{ $explanatoryNote->note_title }}</p>@endif
            <p class="text-xs text-purple-950 leading-relaxed whitespace-pre-wrap">{{ $explanatoryNote->note_content }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- RESULTS TABLE / CARDS --}}
    <div wire:loading class="text-center py-12">
        <div class="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-xs text-gray-500 font-bold mt-2">Mencari database BTKI...</p>
    </div>

    <div wire:loading.remove class="space-y-4">
        @if($results->count() > 0)
        <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 md:p-6 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-black text-sm text-gray-800 uppercase tracking-wider">📊 Hasil Pencarian Kode HS ({{ $results->total() }} Data)</h3>
                <span class="text-xs text-gray-500">Klik 'Detail' untuk hierarki &amp; 'Booking' untuk order</span>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($results as $code)
                <div class="p-4 md:p-6 hover:bg-blue-50/50 transition flex flex-col lg:flex-row lg:items-center justify-between gap-4 {{ $selectedCode == $code->hs_code ? 'bg-emerald-50/70 border-l-4 border-emerald-500' : '' }}">
                    
                    {{-- HS Code & Description --}}
                    <div class="space-y-1.5 lg:flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-sm font-black text-blue-900 bg-blue-50 px-3 py-1 rounded-xl border border-blue-200 shadow-sm">
                                {{ $code->hs_code }}
                            </span>
                            <span class="text-[10px] font-bold bg-gray-100 text-gray-700 px-2 py-0.5 rounded-lg">
                                {{ $code->hs_level }} Digit
                            </span>
                            @if($code->chapter_number)
                            <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-lg">
                                Bab {{ $code->chapter_number }}
                            </span>
                            @endif
                        </div>
                        <p class="text-sm font-bold text-gray-800 leading-snug">{{ $code->description_id ?: '-' }}</p>
                        @if($code->description_en)
                        <p class="text-xs text-gray-500 italic">{{ $code->description_en }}</p>
                        @endif
                    </div>

                    {{-- Duty Badges & Actions --}}
                    <div class="flex items-center gap-3 shrink-0 flex-wrap border-t lg:border-t-0 pt-3 lg:pt-0 border-gray-100">
                        {{-- Bea Masuk Badge --}}
                        <div class="text-center px-3 py-1.5 rounded-xl border {{ ($code->hs_level == 8 && $code->import_duty) ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-gray-50 border-gray-200 text-gray-400' }}">
                            <span class="block text-[9px] font-black uppercase tracking-wider opacity-75">Bea Masuk</span>
                            <span class="text-xs font-black">
                                @if($code->hs_level == 8 && $code->import_duty)
                                {{ $code->import_duty }}{{ is_numeric($code->import_duty) ? '%' : '' }}
                                @else
                                -
                                @endif
                            </span>
                        </div>

                        {{-- Bea Keluar Badge --}}
                        <div class="text-center px-3 py-1.5 rounded-xl border {{ ($code->hs_level == 8 && $code->export_duty && $code->export_duty != '-') ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-gray-50 border-gray-200 text-gray-400' }}">
                            <span class="block text-[9px] font-black uppercase tracking-wider opacity-75">Bea Keluar</span>
                            <span class="text-xs font-black">
                                @if($code->hs_level == 8 && $code->export_duty && $code->export_duty != '-')
                                {{ $code->export_duty }}{{ is_numeric($code->export_duty) ? '%' : '' }}
                                @else
                                -
                                @endif
                            </span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-2">
                            <button wire:click="showHierarchy('{{ $code->hs_code }}')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3 py-2 rounded-xl transition flex items-center gap-1 shadow-sm">
                                👁 Detail
                            </button>
                            <a href="{{ route('customer.calculator', ['hs_code' => $code->hs_code]) }}" class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs px-3 py-2 rounded-xl transition flex items-center gap-1 shadow-sm">
                                🧮 Hitung
                            </a>
                            <a href="{{ route('customer.shipments.create', ['hs_code' => $code->hs_code]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-3 py-2 rounded-xl transition flex items-center gap-1 shadow-sm">
                                📦 Booking
                            </a>
                        </div>

                    </div>

                </div>
                @endforeach
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-200">
                {{ $results->links() }}
            </div>
        </div>
        @else
        <div class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-200 space-y-3">
            <div class="text-5xl">🔍</div>
            <h3 class="text-lg font-bold text-gray-700">Tidak ada kode HS ditemukan</h3>
            <p class="text-xs text-gray-500 max-w-md mx-auto">Coba masukkan 8 digit angka murni (cth: 23096060), format bertitik (2309.60.60), atau nama komoditas lainnya.</p>
        </div>
        @endif
    </div>

    {{-- DISCLAIMER SECTION --}}
    <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-2xl p-6 border border-gray-200 text-xs text-slate-600 space-y-2">
        <div class="flex items-center gap-2 font-bold text-slate-800">
            <span>⚠️ Penyangkalan (Disclaimer) Tarif BTKI 2022</span>
        </div>
        <p class="leading-relaxed">
            Data tarif Bea Masuk (BM) dan Bea Keluar (BK) bersumber dari Buku Tarif Kepabeanan Indonesia (BTKI 2022) dan bersifat informatif. Untuk regulasi Lartas (Larangan &amp; Pembatasan) dan tarif resmi terkini, silakan merujuk pada portal resmi <a href="https://insw.go.id/intr" target="_blank" class="text-blue-600 font-bold underline">INSW</a> dan <a href="https://www.beacukai.go.id" target="_blank" class="text-blue-600 font-bold underline">Bea Cukai DJBC</a>.
        </p>
    </div>
</div>
