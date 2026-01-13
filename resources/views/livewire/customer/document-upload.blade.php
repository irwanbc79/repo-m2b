<div class="bg-white rounded shadow p-4">
    <h3 class="font-medium mb-3">Upload Document</h3>

    <form wire:submit.prevent="upload" class="space-y-3">
        <div>
            <label class="text-sm">Select Shipment</label>
            <select wire:model="shipment_id" class="w-full rounded border px-3 py-2">
                <option value="">-- choose --</option>
                @foreach($availableShipments as $s)
                <option value="{{ $s->id }}">#{{ $s->tracking_number }} — {{ $s->destination }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm">File</label>
            <input type="file" wire:model="file" class="w-full" />
            @error('file') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Upload</button>
            <div wire:loading wire:target="upload" class="text-sm text-gray-500">Uploading…</div>
        </div>
    </form>
</div>
