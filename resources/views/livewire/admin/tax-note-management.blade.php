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
                    <th class="px-3 py-3 text-center w-10">Status</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Jenis Pajak</th>
                    <th class="px-4 py-3 text-left">Referensi Invoice</th>
                    <th class="px-4 py-3 text-right">Nominal</th>
                    <th class="px-4 py-3 text-left">Catatan</th>
                    <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                    <th class="px-4 py-3 text-center w-28">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($notes as $note)
                @php
                    $attachCount = count($note->attachments ?? []);
                @endphp
                <tr class="hover:bg-gray-750 transition-colors {{ $note->is_resolved ? 'opacity-60' : '' }}">

                    {{-- Status toggle --}}
                    <td class="px-3 py-3 text-center">
                        @if($this->canEdit($note))
                        <button wire:click="toggleResolved({{ $note->id }})"
                            title="{{ $note->is_resolved ? 'Tandai Belum Selesai' : 'Tandai Selesai' }}"
                            class="text-lg leading-none transition-transform hover:scale-125">
                            {{ $note->is_resolved ? '✅' : '🔴' }}
                        </button>
                        @else
                            <span class="text-lg">{{ $note->is_resolved ? '✅' : '🔴' }}</span>
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

                    {{-- Invoice --}}
                    <td class="px-4 py-3">
                        @if($note->invoice)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-900/50 border border-emerald-700 rounded text-xs text-emerald-300 font-mono">
                                🧾 {{ $note->invoice->invoice_number }}
                            </span>
                            @if($note->invoice->customer)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $note->invoice->customer->company_name }}</div>
                            @endif
                        @else
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

                    {{-- Catatan + lampiran --}}
                    <td class="px-4 py-3 max-w-xs">
                        <div class="text-gray-300">{{ Str::limit($note->catatan, 70) }}</div>
                        @if($note->is_resolved && $note->resolved_at)
                            <div class="text-xs text-green-500 mt-0.5">Selesai {{ $note->resolved_at->format('d M Y') }}</div>
                        @endif
                        @if($attachCount > 0)
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($note->attachments as $path)
                                @php
                                    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                    $icon = match(true) {
                                        in_array($ext, ['jpg','jpeg','png','gif','webp']) => '🖼️',
                                        $ext === 'pdf'                                     => '📄',
                                        in_array($ext, ['doc','docx'])                    => '📝',
                                        in_array($ext, ['xls','xlsx'])                    => '📊',
                                        default                                            => '📎',
                                    };
                                @endphp
                                <a href="{{ Storage::url($path) }}" target="_blank"
                                    title="{{ basename($path) }}"
                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 bg-gray-700 hover:bg-gray-600 border border-gray-600 rounded text-xs text-gray-300 transition-colors">
                                    {{ $icon }} <span class="max-w-[80px] truncate">{{ basename($path) }}</span>
                                </a>
                                @endforeach
                            </div>
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
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] flex flex-col">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700 flex-shrink-0">
                <h2 class="text-lg font-semibold text-gray-100">
                    {{ $isEditing ? 'Edit Catatan Pajak' : 'Tambah Catatan Pajak' }}
                </h2>
                <button wire:click="closeModal" class="text-gray-400 hover:text-white transition-colors">✕</button>
            </div>

            {{-- Scrollable body --}}
            <form wire:submit.prevent="save" class="px-6 py-5 space-y-4 overflow-y-auto flex-1">

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
                        <input wire:model="nominal" type="number" min="0" step="1" placeholder="0"
                            class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('nominal') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
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

                {{-- Catatan — dengan drag & drop + paste file --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">Catatan</label>
                    <div
                        x-data="{ dragging: false }"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="
                            dragging = false;
                            const files = Array.from($event.dataTransfer.files);
                            if (files.length) $wire.uploadMultiple('newAttachments', files, () => {}, () => {});
                        "
                        :class="dragging ? 'ring-2 ring-blue-400' : ''"
                        class="relative rounded-lg">
                        <textarea wire:model="catatan" rows="5"
                            placeholder="Tuliskan catatan pajak... atau drag &amp; drop / paste file lampiran langsung di sini"
                            @paste="
                                const files = Array.from($event.clipboardData.files);
                                if (files.length) {
                                    $event.preventDefault();
                                    $wire.uploadMultiple('newAttachments', files, () => {}, () => {});
                                }
                            "
                            class="w-full bg-gray-700 border border-gray-600 text-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"></textarea>
                        <div x-show="dragging"
                            class="absolute inset-0 flex items-center justify-center bg-blue-900/60 rounded-lg border-2 border-dashed border-blue-400 pointer-events-none">
                            <span class="text-blue-200 font-semibold text-sm">📂 Lepas file di sini</span>
                        </div>
                    </div>
                    @error('catatan') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Lampiran --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase tracking-wide">
                        Lampiran <span class="text-gray-600 normal-case font-normal">(PDF, Gambar, DOC, XLS · maks. 10 MB/file)</span>
                    </label>

                    {{-- File picker button --}}
                    <label class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 hover:bg-gray-600 border border-gray-600 border-dashed rounded-lg cursor-pointer transition-colors text-sm text-gray-300">
                        <span>📎</span>
                        <span>Pilih File</span>
                        <input type="file" wire:model="newAttachments" multiple
                            accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx"
                            class="hidden">
                    </label>
                    @error('newAttachments.*') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

                    {{-- Upload progress --}}
                    <div wire:loading wire:target="newAttachments" class="mt-2 text-xs text-blue-400">
                        ⏳ Mengunggah...
                    </div>

                    {{-- Existing attachments (edit mode) --}}
                    @if(!empty($existingAttachments))
                    <div class="mt-2 space-y-1">
                        <p class="text-xs text-gray-500 mb-1">Sudah tersimpan:</p>
                        @foreach($existingAttachments as $i => $path)
                        @php
                            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            $icon = match(true) {
                                in_array($ext, ['jpg','jpeg','png','gif','webp']) => '🖼️',
                                $ext === 'pdf'                                     => '📄',
                                in_array($ext, ['doc','docx'])                    => '📝',
                                in_array($ext, ['xls','xlsx'])                    => '📊',
                                default                                            => '📎',
                            };
                        @endphp
                        <div class="flex items-center gap-2 px-2 py-1 bg-gray-700/60 rounded text-xs text-gray-300">
                            <span>{{ $icon }}</span>
                            <a href="{{ Storage::url($path) }}" target="_blank"
                                class="flex-1 truncate hover:text-blue-300 transition-colors">
                                {{ basename($path) }}
                            </a>
                            <button type="button" wire:click="removeExistingAttachment({{ $i }})"
                                class="text-red-400 hover:text-red-300 flex-shrink-0 leading-none">✕</button>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Newly selected files preview --}}
                    @if(!empty($newAttachments))
                    <div class="mt-2 space-y-1">
                        <p class="text-xs text-gray-500 mb-1">Akan diunggah:</p>
                        @foreach($newAttachments as $i => $file)
                        <div class="flex items-center gap-2 px-2 py-1 bg-blue-900/30 border border-blue-800 rounded text-xs text-blue-300">
                            <span>📎</span>
                            <span class="flex-1 truncate">{{ $file->getClientOriginalName() }}</span>
                            <span class="text-gray-500 flex-shrink-0">{{ number_format($file->getSize() / 1024, 0) }} KB</span>
                            <button type="button" wire:click="removeNewAttachment({{ $i }})"
                                class="text-red-400 hover:text-red-300 flex-shrink-0 leading-none">✕</button>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-2 pb-1">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-gray-600 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        class="px-5 py-2 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <span wire:loading.remove>{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Catatan' }}</span>
                        <span wire:loading>Menyimpan...</span>
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
            <p class="text-sm text-gray-400 mb-6">Semua lampiran akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.</p>
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
