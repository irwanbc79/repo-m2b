<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
    <div class="mb-4">
        <h3 class="text-base font-semibold text-gray-900">Upload Document</h3>
        <p class="text-xs text-gray-500 mt-0.5">
            Shipment #{{ $shipment->tracking_number ?? $shipment->bl_number ?? $shipment->id }}
            @if(!empty($shipment->destination))
                &bull; {{ $shipment->destination }}
            @endif
        </p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-md">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="uploadDocuments" class="space-y-4">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Document Type <span class="text-red-500">*</span></label>
            <select wire:model="documentType" class="w-full text-xs rounded border border-gray-300 px-3 py-2 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                <option value="invoice">Invoice</option>
                <option value="packing_list">Packing List</option>
                <option value="certificate">Certificate</option>
                <option value="other">Other</option>
            </select>
            @error('documentType') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
            <input type="text" wire:model="description" placeholder="Optional notes about the document" class="w-full text-xs rounded border border-gray-300 px-3 py-2 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
            @error('description') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">File(s) <span class="text-red-500">*</span></label>
            <input type="file" wire:model="files" multiple class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
            <p class="text-[11px] text-gray-400 mt-1">PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (Max 10MB per file)</p>
            @error('files') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
            @error('files.*') <div class="text-red-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-4 py-2 rounded transition-colors disabled:opacity-50">
                <span wire:loading.remove wire:target="uploadDocuments">Upload Document</span>
                <span wire:loading wire:target="uploadDocuments">Uploading...</span>
            </button>
        </div>
    </form>
</div>
