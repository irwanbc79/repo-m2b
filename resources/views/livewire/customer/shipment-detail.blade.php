@php
    use App\Models\Shipment;
    
    // Helper function
    if (!function_exists('hasKeyword')) {
        function hasKeyword($haystack, $needles) {
            if (empty($haystack) || empty($needles)) return false;
            foreach ($haystack as $item) {
                foreach ($needles as $needle) {
                    if (str_contains(strtolower($item), strtolower($needle))) return true;
                }
            }
            return false;
        }
    }
    
    // Logic tracker visual
    $serviceType = strtolower($shipment->service_type ?? 'domestic');
    $statusFlow = Shipment::getStatusFlow($serviceType);
    
    // Filter hanya status non-optional
    $mainSteps = collect($statusFlow)->filter(fn($s) => !($s['optional'] ?? false))->values();
    $steps = $mainSteps->pluck('label')->toArray();
    
    // Helper: cek dokumen
    $docs = $shipment->documents->pluck('description')->map(fn($i) => strtolower($i ?? ''))->toArray();
    $hasDoc = function($keywords) use ($docs) {
        foreach ($docs as $doc) {
            foreach ((array)$keywords as $kw) {
                if (str_contains($doc, strtolower($kw))) return true;
            }
        }
        return false;
    };
    
    // Tentukan current step berdasarkan DOKUMEN
    $currentStep = 1;
    
    if ($serviceType === 'import') {
        if ($hasDoc(['bill of lading', 'bl', 'invoice', 'packing list'])) $currentStep = 2;
        if ($hasDoc(['manifest', 'bc 1.1', 'bc1.1'])) $currentStep = 3;
        if ($hasDoc(['billing', 'ebilling', 'pungutan'])) $currentStep = 4;
        if ($hasDoc(['sppb', 'pengeluaran', 'released'])) $currentStep = 5;
        if ($hasDoc(['sp2', 'surat jalan', 'delivery'])) $currentStep = 6;
        if ($shipment->status === 'completed') $currentStep = 7;
    } elseif ($serviceType === 'export') {
        if ($hasDoc(['invoice', 'packing list', 'si', 'shipping instruction'])) $currentStep = 2;
        if ($hasDoc(['peb', 'bc 3.0', 'bc3.0'])) $currentStep = 3;
        if ($hasDoc(['npe', 'persetujuan ekspor'])) $currentStep = 4;
        if ($hasDoc(['bl final', 'on board', 'shipped']) || $shipment->status === 'on_board') $currentStep = 5;
        if ($shipment->status === 'completed') $currentStep = 6;
    } else {
        if ($hasDoc(['pickup', 'tanda terima', 'penjemputan'])) $currentStep = 2;
        if ($hasDoc(['manifest', 'resi', 'transit'])) $currentStep = 3;
        if ($hasDoc(['surat jalan', 'delivery', 'antar'])) $currentStep = 4;
        if ($shipment->status === 'completed') $currentStep = 5;
    }
    
    // Label status deskriptif
    $stepLabels = array_column($mainSteps->toArray(), 'label');
    $currentStepLabel = $stepLabels[$currentStep - 1] ?? 'Booking';
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('customer.shipments.index') }}" class="hover:text-m2b-primary transition">Shipments</a>
        <span>/</span>
        <span class="font-bold text-gray-800">{{ $shipment->awb_number }}</span>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- KARTU 1: VISUAL TRACKER (Separuh Atas) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-m2b-primary p-6 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-black tracking-tight">{{ $shipment->awb_number }}</h1>
                <p class="text-blue-200 text-sm mt-1 flex items-center gap-2">
                    <span class="font-bold">{{ $shipment->origin }}</span>
                    <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    <span class="font-bold">{{ $shipment->destination }}</span>
                </p>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-xs uppercase tracking-widest text-blue-300 mb-1">Status</span>
                <span class="bg-white/10 px-3 py-1 rounded text-sm font-bold border border-white/20 capitalize">
                        {{ $currentStepLabel ?? str_replace('_', ' ', $shipment->status) }}
                </span>
            </div>
        </div>

        {{-- LOGIC TRACKER --}}
        <div class="p-8 border-b border-gray-100 overflow-x-auto">
            <div class="min-w-[700px]"> 
                <div class="relative mt-6 mb-2">
                    <div class="absolute top-1/2 left-0 w-full h-1 bg-gray-200 -translate-y-1/2 rounded-full z-0"></div>
                    <div class="absolute top-1/2 left-0 h-1 bg-m2b-accent -translate-y-1/2 rounded-full z-0 transition-all duration-1000 ease-out" style="width: {{ ($currentStep - 1) / (count($steps) - 1) * 100 }}%"></div>
                    <div class="relative z-10 flex justify-between w-full">
                        @foreach($steps as $index => $label)
                            @php $stepNum = $index + 1; @endphp
                            <div class="flex flex-col items-center gap-3 w-32">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 font-bold text-xs transition-all duration-500 shadow-sm {{ $currentStep >= $stepNum ? 'bg-m2b-accent border-m2b-accent text-white scale-110' : 'bg-white border-gray-300 text-gray-400' }}">
                                    {{ $currentStep > $stepNum ? '✓' : $stepNum }}
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] font-bold uppercase tracking-wide {{ $currentStep >= $stepNum ? 'text-m2b-primary' : 'text-gray-400' }}">{{ $label }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- KOLOM KIRI: LIST DOKUMEN (UPDATED - dengan tombol preview & download) --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-m2b-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Arsip Dokumen
                </h3>
                
                @if($shipment->documents->where('is_internal', false)->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($shipment->documents->where('is_internal', false) as $doc)
                        {{-- UPDATED: Tidak lagi sebagai <a> link, tapi <div> dengan tombol aksi --}}
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-slate-50 transition group relative">
                            <div class="bg-red-50 text-red-600 p-2.5 rounded mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="overflow-hidden flex-1">
                                <p class="text-sm font-bold text-gray-700 truncate group-hover:text-blue-800">{{ $doc->description }}</p>
                                <p class="text-[10px] text-gray-400 uppercase mt-0.5">
                                    {{ strtoupper(pathinfo($doc->filename, PATHINFO_EXTENSION)) }} • {{ \Carbon\Carbon::parse($doc->created_at)->format('d M Y') }}
                                </p>
                            </div>
                            {{-- TOMBOL PREVIEW & DOWNLOAD (BARU - adopsi dari admin) --}}
                            <div class="flex gap-1">
                                {{-- Tombol Preview (Mata) --}}
                                <button wire:click="viewDocument({{ $doc->id }})" class="p-1.5 text-blue-500 hover:bg-blue-100 rounded transition" title="Preview">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                {{-- Tombol Download --}}
                                <a href="{{ route('document.download', $doc->id) }}" download class="p-1.5 text-green-500 hover:bg-green-100 rounded transition" title="Download">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                        <p class="text-gray-400 text-sm">Belum ada dokumen tersedia.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- KOLOM KANAN: FORM UPLOAD (FITUR BARU) --}}
        <div class="lg:col-span-1">
            <div class="bg-blue-50 rounded-xl shadow-sm border border-blue-100 p-6 sticky top-6">
                <div class="flex items-center gap-2 mb-4 text-blue-900 border-b border-blue-200 pb-3">
                    <div class="bg-blue-600 text-white p-1.5 rounded">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm">Upload Dokumen</h4>
                        <p class="text-[10px] text-blue-600">Bantu kami mempercepat proses</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Dokumen</label>
                        <select wire:model="doc_type" class="w-full border-blue-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Invoice">Commercial Invoice</option>
                            <option value="Packing List">Packing List</option>
                            <option value="Bill of Lading">Bill of Lading (BL) / AWB</option>
                            <option value="Bukti Bayar">Bukti Transfer</option>
                            <option value="Lainnya">Dokumen Lainnya</option>
                        </select>
                        @error('doc_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">File (PDF/Gambar)</label>
                        <input type="file" wire:model="file_upload" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                        @error('file_upload') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan (Opsional)</label>
                        <textarea wire:model="custom_note" rows="2" class="w-full border-blue-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Keterangan tambahan..."></textarea>
                    </div>

                    <button wire:click="uploadDoc" wire:loading.attr="disabled" class="w-full bg-m2b-primary text-white font-bold py-2 rounded-lg hover:bg-blue-900 transition shadow-lg flex justify-center items-center gap-2">
                        <span wire:loading.remove wire:target="uploadDoc">Upload Sekarang</span>
                        <span wire:loading wire:target="uploadDoc" class="text-xs">Mengunggah...</span>
                    </button>
                </div>

                {{-- List Wajib --}}
                <div class="mt-6 pt-4 border-t border-blue-200">
                    <p class="text-[10px] font-bold text-gray-500 uppercase mb-2">Dokumen Wajib:</p>
                    <ul class="text-xs text-gray-600 space-y-1 list-disc pl-4">
                        <li>Commercial Invoice</li>
                        <li>Packing List</li>
                        @if(strtolower($shipment->service_type) == 'export')
                            <li>Shipping Instruction (SI)</li>
                        @else
                            <li>Bill of Lading (BL) / AWB</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================ --}}
    {{-- MODAL PREVIEW DOCUMENT (BARU - adopsi dari admin) --}}
    {{-- ============================================ --}}
    <div 
        x-data="{ 
            show: @entangle('showDocPreview'), 
            zoom: 100, 
            rotation: 0,
            zoomIn() { this.zoom = Math.min(this.zoom + 25, 300); },
            zoomOut() { this.zoom = Math.max(this.zoom - 25, 50); },
            rotate() { this.rotation = (this.rotation + 90) % 360; },
            reset() { this.zoom = 100; this.rotation = 0; }
        }" 
        x-show="show" 
        x-cloak
        @keydown.escape.window="show = false; $wire.closeDocPreview()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm"
        style="display: none;">
        
        {{-- Modal Container --}}
        <div class="relative w-full h-full max-w-7xl mx-auto p-4 flex flex-col">
            
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4 bg-gray-900/50 rounded-lg px-6 py-4 backdrop-blur-md">
                <div class="flex items-center gap-4">
                    <button @click="show = false; $wire.closeDocPreview()" class="text-white hover:text-red-400 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @if($previewDoc)
                        <div>
                            <h3 class="text-white font-bold text-lg">{{ $previewDoc->description }}</h3>
                            <p class="text-gray-400 text-sm">{{ $previewDoc->filename }} • {{ number_format($previewDoc->file_size / 1024, 1) }} KB</p>
                        </div>
                    @endif
                </div>
                
                <div class="flex items-center gap-3">
                    {{-- Zoom Controls --}}
                    <div class="flex items-center gap-2 bg-gray-800 rounded-lg px-3 py-2">
                        <button @click="zoomOut" class="text-white hover:text-blue-400 transition" title="Zoom Out">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                            </svg>
                        </button>
                        <span class="text-white text-sm font-bold min-w-[3rem] text-center" x-text="zoom + '%'"></span>
                        <button @click="zoomIn" class="text-white hover:text-blue-400 transition" title="Zoom In">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                            </svg>
                        </button>
                    </div>
                    
                    {{-- Rotate --}}
                    <button @click="rotate" class="bg-gray-800 hover:bg-gray-700 text-white rounded-lg px-4 py-2 transition" title="Rotate">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                    
                    {{-- Reset --}}
                    <button @click="reset" class="bg-gray-800 hover:bg-gray-700 text-white rounded-lg px-4 py-2 text-sm font-bold transition" title="Reset View">
                        Reset
                    </button>
                    
                    {{-- Download --}}
                    @if($previewDoc)
                        <a href="{{ route('document.download', $previewDoc->id) }}" download class="bg-green-600 hover:bg-green-700 text-white rounded-lg px-4 py-2 font-bold text-sm flex items-center gap-2 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download
                        </a>
                    @endif
                </div>
            </div>
            
            {{-- Preview Content --}}
            <div class="flex-1 bg-gray-900/30 rounded-lg overflow-hidden flex items-center justify-center">
                @if($previewDoc)
                    @php
                        $ext = strtolower(pathinfo($previewDoc->filename, PATHINFO_EXTENSION));
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                        $isPdf = $ext === 'pdf';
                    @endphp
                    
                    @if($isImage)
                        {{-- Image Preview --}}
                        <div class="overflow-auto max-h-full max-w-full p-8">
                            <img 
                                src="{{ route('document.view', $previewDoc->id) }}" 
                                alt="{{ $previewDoc->filename }}"
                                :style="`transform: scale(${zoom/100}) rotate(${rotation}deg); transition: transform 0.3s ease;`"
                                class="max-w-none"
                            >
                        </div>
                    @elseif($isPdf)
                        {{-- PDF Preview --}}
                        <iframe 
                            src="{{ route('document.view', $previewDoc->id) }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH" 
                            class="w-full h-full border-0"
                            :style="`transform: scale(${zoom/100}); transform-origin: top center;`"
                        ></iframe>
                    @else
                        {{-- Other File Types --}}
                        <div class="text-center text-white p-8">
                            <svg class="w-24 h-24 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="text-xl font-bold mb-2">Preview tidak tersedia</h3>
                            <p class="text-gray-400 mb-6">File tipe .{{ $ext }} tidak dapat di-preview di browser</p>
                            <a href="{{ route('document.download', $previewDoc->id) }}" download class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-6 py-3 font-bold transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download File
                            </a>
                        </div>
                    @endif
                @endif
            </div>
            
            {{-- Navigation Footer (jika lebih dari 1 dokumen) --}}
            @if($previewDoc && $allPublicDocs && $allPublicDocs->count() > 1)
                <div class="mt-4 bg-gray-900/50 rounded-lg px-6 py-4 backdrop-blur-md flex items-center justify-between">
                    <button wire:click="previousDocument" class="text-white hover:text-blue-400 flex items-center gap-2 font-bold transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $currentDocIndex <= 0 ? 'disabled' : '' }}>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Sebelumnya
                    </button>
                    
                    <span class="text-white font-bold">
                        {{ $currentDocIndex + 1 }} / {{ $allPublicDocs->count() }}
                    </span>
                    
                    <button wire:click="nextDocument" class="text-white hover:text-blue-400 flex items-center gap-2 font-bold transition disabled:opacity-50 disabled:cursor-not-allowed" {{ $currentDocIndex >= $allPublicDocs->count() - 1 ? 'disabled' : '' }}>
                        Berikutnya
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            @endif
        </div>
    </div>
    {{-- END MODAL PREVIEW --}}

</div>
