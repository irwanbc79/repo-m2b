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
                    <th class="px-4 py-3 text-left w-8">Status</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Jenis Pajak</th>
                    <th class="px-4 py-3 text-left">Referensi</th>
                    <th class="px-4 py-3 text-left">Nominal</th>
                    <th class="px-4 py-3 text-left">Catatan</th>
                    <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                    <th class="px-4 py-3 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($notes as $note)
                <tr class="hover:bg-gray-750 transition-colors {{ $note->is_resolved ? 'opacity-60' : '' }}">

                    {{-- Status resolved toggle --}}
                    <td class="px-4 py-3 text-center">
                        @if($this->canEdit($note))
                        <button wire:click="toggleResolved({{ $note->id }})"
                            title="{{ $note->is_resolved ? 'Tandai Belum Selesai' : 'Tandai Selesai' }}"
                            class="text-lg leading-none transition-transform hover:scale-110">
                            {{ $note->is_resolved ? '✅' : '🔴' }}
                        </button>
                        @else
                            {{ $note->is_resolved ? '✅' : '🔴' }}
                        @endif
                    </td>

                    <td class="px-4 py-3 font-mono font-medium text-blue-300">{{ $note->periode }}</td>

                    {{-- Jenis Pajak --}}
                    <td class="px-4 py-3">
                        @if($note->jenis_pajak)
                            <span class="px-2 py-1 bg-yellow-900/50 border border-yellow-700 rounded text-xs text-yellow-300 font-semibold">
                                {{ $note->jenis_pajak }}
                            </span>
                        @else
                            <span class="text-gray-600 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Referensi Shipment / Invoice --}}
                    <td class="px-4 py-3 space-y-1">
                        @if($note->shipment)
                            <div>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-900/50 border border-indigo-700 rounded text-xs text-indigo-300 font-mono">
                                    📦 {{ $note->shipment->awb_number ?: $note->shipment->bl_number ?: '#'.$note->shipment->id }}
                                </span>
                            </div>
                        @endif
                        @if($note->invoice)
                            <div>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-900/50 border border-emerald-700 rounded text-xs text-emerald-300 font-mono">
                                    🧾 {{ $note->invoice->invoice_number }}
                                </span>
                            </div>
                        @endif
                        @if(!$note->shipment && !$note->invoice)
                            <span class="text-gray-600 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Nominal --}}
                    <td class="px-4 py-3 text-right font-mono text-xs">
                        @if($note->nominal !== null)
                            <span class="text-gray-200">Rp {{ number_format($note->nominal, 0, ',', '.') }}</span>
                        @else
                            <span class="text-gray-600">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-gray-300 max-w-xs">
                        {{ Str::limit($note->catatan, 70) }}
                        @if($note->is_resolved && $note->resolved_at)
                            <div class="text-xs text-green-500 mt-0.5">Selesai {{ $note->resolved_at->format('d M Y') }}</div>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $note->user?->name ?? '-' }}</td>

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
                    <td colspan="8" class="px-4 py-10 text-center text-gray-500">
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
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 sticky top-0 bg-gray-800 z-10">
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

                {{-- Jenis Pajak + Nominal --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                            Jenis Pajak <span class="text-gray-600 normal-case font-normal">(opsional)</span>
                        </label>
                        <select wire:model="jenis_pajak"
                            class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Pilih jenis —</option>
                            @foreach($jenisPajakList as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis_pajak') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                            Nominal <span class="text-gray-600 normal-case font-normal">(opsional)</span>
                        </label>
                        <input wire:model="nominal" type="number" min="0" step="1"
                            placeholder="0"
                            class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('nominal') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
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
                        <option value="">— Tidak terkait shipment —</option>
                        @foreach($shipmentOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @error('shipment_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Referensi Invoice --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                        Referensi Invoice <span class="text-gray-600 normal-case font-normal">(opsional)</span>
                    </label>
                    <input wire:model.live.debounce.300ms="invoiceSearch"
                        type="text"
                        placeholder="Cari nomor invoice / nama customer..."
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2">
                    <select wire:model="invoice_id"
                        class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tidak terkait invoice —</option>
                        @foreach($invoiceOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @error('invoice_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Catatan --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">Catatan</label>
                    <textarea wire:model="catatan" rows="5"
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
