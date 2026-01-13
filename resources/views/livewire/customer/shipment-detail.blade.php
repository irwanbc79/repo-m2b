@php
    // Helper function wajib ada di atas
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
                    {{ str_replace('_', ' ', $shipment->status) }}
                </span>
            </div>
        </div>

        {{-- LOGIC TRACKER --}}
        <div class="p-8 border-b border-gray-100 overflow-x-auto">
            <div class="min-w-[700px]"> 
                @php
                    $type = strtolower($shipment->service_type);
                    $docs = $shipment->documents->pluck('description')->map(fn($i) => strtolower($i))->toArray();
                    $currentStep = 1;
                    $steps = []; 

                    // Logic Tracker (Sama seperti sebelumnya)
                    if ($type == 'import') {
                        $steps = ['Pending', 'Manifest / BC 1.1', 'Customs Billing', 'Released (SPPB)', 'Delivery', 'Completed'];
                        if (hasKeyword($docs, ['manifest', 'bc 1.1'])) $currentStep = 2;
                        if (hasKeyword($docs, ['billing', 'ebilling'])) $currentStep = 3;
                        if (hasKeyword($docs, ['sppb released', 'pengeluaran barang', 'hptd'])) $currentStep = 4;
                        if (hasKeyword($docs, ['sp2', 'surat jalan'])) $currentStep = 5;
                        if ($shipment->status == 'completed') $currentStep = 6;
                    } elseif ($type == 'export') {
                        $steps = ['Booking', 'Stuffing', 'Customs (NPE)', 'On Board', 'Completed'];
                        if (hasKeyword($docs, ['gate in', 'stuffing'])) $currentStep = 2;
                        if (hasKeyword($docs, ['npe', 'pib'])) $currentStep = 3;
                        if (hasKeyword($docs, ['bl final', 'on board'])) $currentStep = 4;
                        if ($shipment->status == 'completed') $currentStep = 5;
                    } else {
                        $steps = ['Booking', 'Cargo In', 'Sailing', 'Arrived', 'Delivered'];
                        if (hasKeyword($docs, ['tanda terima'])) $currentStep = 2;
                        if (hasKeyword($docs, ['manifest', 'resi'])) $currentStep = 3;
                        if ($shipment->estimated_arrival && now() >= \Carbon\Carbon::parse($shipment->estimated_arrival)) $currentStep = 4;
                        if ($shipment->status == 'completed') $currentStep = 5;
                    }
                @endphp

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
        
        {{-- KOLOM KIRI: LIST DOKUMEN --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-m2b-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Arsip Dokumen
                </h3>
                
                @if($shipment->documents->where('is_internal', false)->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($shipment->documents->where('is_internal', false) as $doc)
                        <a href="{{ route('document.view', $doc->id) }}" target="_blank" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-200 transition group bg-white">
                            <div class="bg-red-50 text-red-600 p-2.5 rounded mr-3 group-hover:bg-red-100 transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 00-2 2v9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-sm font-bold text-gray-700 truncate group-hover:text-blue-800">{{ $doc->description }}</p>
                                <p class="text-[10px] text-gray-400 uppercase mt-0.5">
                                    {{ strtoupper(pathinfo($doc->filename, PATHINFO_EXTENSION)) }} • {{ \Carbon\Carbon::parse($doc->created_at)->format('d M Y') }}
                                </p>
                            </div>
                        </a>
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
</div>