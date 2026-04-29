<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-100">🗒️ Catatan Pajak</h1>
            <p class="text-sm text-gray-400 mt-1">Pajak &amp; Keuangan · Catatan per periode</p>
        </div>
        @if($this->canCreate())
        <button wire:click="openCreate"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            + Tambah Catatan
        </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm text-gray-300">
            <thead>
                <tr class="bg-gray-700 text-gray-400 uppercase text-xs tracking-wider">
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Shipment</th>
                    <th class="px-4 py-3 text-left">Catatan</th>
                    <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($notes as $note)
                <tr class="hover:bg-gray-750 transition-colors">
                    <td class="px-4 py-3 font-mono font-medium text-blue-300">{{ $note->periode }}</td>
                    <td class="px-4 py-3">
                        @if($note->shipment)
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-900/50 border border-indigo-700 rounded text-xs text-indigo-300 font-mono">
                                📦 {{ $note->shipment->awb_number ?: $note->shipment->bl_number ?: '#'.$note->shipment->id }}
                            </span>
                        @else
                            <span class="text-gray-600 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-300 max-w-xs">
                        {{ Str::limit($note->catatan, 80) }}
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $note->user?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $note->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @if($this->canEdit($note))
                            <button wire:click="openEdit({{ $note->id }})"
                                class="text-xs px-2 py-1 bg-yellow-600 hover:bg-yellow-500 text-white rounded transition-colors">
                                Edit
                            </button>
                            @endif
                            @if($this->canDelete())
                            <button wire:click="confirmDelete({{ $note->id }})"
                                class="text-xs px-2 py-1 bg-red-700 hover:bg-red-600 text-white rounded transition-colors">
                                Hapus
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                        Belum ada catatan pajak.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($notes->hasPages())
    <div class="mt-4">{{ $notes->links() }}</div>
    @endif

    {{-- Create / Edit Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                <h2 class="text-lg font-semibold text-gray-100">
                    {{ $isEditing ? 'Edit Catatan Pajak' : 'Tambah Catatan Pajak' }}
                </h2>
                <button wire:click="closeModal" class="text-gray-400 hover:text-white transition-colors">✕</button>
            </div>
            <form wire:submit.prevent="save" class="px-6 py-5 space-y-4">

                {{-- Periode --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">Periode</label>
                    <select wire:model="periode"
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($periodeOptions as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('periode') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Referensi Shipment --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                        Referensi Shipment <span class="text-gray-600 normal-case font-normal">(opsional)</span>
                    </label>
                    <input wire:model.live.debounce.300ms="shipmentSearch"
                        type="text"
                        placeholder="Cari AWB / BL / nama customer..."
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">

                    <select wire:model="shipment_id"
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tidak terkait shipment tertentu —</option>
                        @foreach($shipmentOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @error('shipment_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">Catatan</label>
                    <textarea wire:model="catatan" rows="6"
                        placeholder="Tuliskan catatan pajak untuk periode ini..."
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"></textarea>
                    @error('catatan') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-gray-600 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        {{ $isEditing ? 'Simpan Perubahan' : 'Simpan Catatan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirm Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 text-center">
            <div class="text-4xl mb-3">🗑️</div>
            <h3 class="text-lg font-semibold text-gray-100 mb-2">Hapus Catatan?</h3>
            <p class="text-sm text-gray-400 mb-6">Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete"
                    class="px-4 py-2 text-sm border border-gray-600 text-gray-300 hover:text-white rounded-lg transition-colors">
                    Batal
                </button>
                <button wire:click="deleteNote"
                    class="px-4 py-2 text-sm bg-red-700 hover:bg-red-600 text-white font-medium rounded-lg transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
