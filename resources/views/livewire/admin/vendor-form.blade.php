<div class="space-y-6">
    @section('header', 'Manage Vendors')

    {{-- FLASH MESSAGE --}}
    @if(session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    {{-- LIST VENDOR --}}
    <div class="bg-white rounded-xl shadow border">
        <div class="p-6 flex justify-between items-center border-b bg-gray-50">
            <input
                wire:model.debounce.500ms="search"
                type="text"
                placeholder="Cari Vendor..."
                class="w-72 border-gray-300 rounded-lg shadow-sm text-sm"
            >
            <button
                wire:click="create"
                class="bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg font-bold shadow">
                + Tambah Vendor
            </button>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Vendor</th>
                    <th class="px-4 py-3">Kontak Utama</th>
                    <th class="px-4 py-3">Bank</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($vendors as $vendor)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-bold">{{ $vendor->code }}</td>
                    <td class="px-4 py-3">
                        <div class="font-bold text-gray-800">{{ $vendor->name }}</div>
                        <div class="text-xs text-gray-500">{{ $vendor->category }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @php
                            $pic = $vendor->contacts->firstWhere('is_primary', true)
                                   ?? $vendor->contacts->first();
                        @endphp
                        @if($pic)
                            <div class="font-bold text-blue-700">{{ $pic->pic_name }}</div>
                            <div>{{ $pic->phone }}</div>
                            <div class="italic text-gray-500">{{ $pic->email }}</div>
                        @else
                            <span class="italic text-gray-400">Belum ada kontak</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs">
                        {{ $vendor->bank_details ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button
                            wire:click="edit({{ $vendor->id }})"
                            class="px-2 py-1 border rounded text-blue-600 hover:bg-blue-50">
                            Edit
                        </button>
                        <button
                            onclick="confirm('Yakin hapus vendor ini?') || event.stopImmediatePropagation()"
                            wire:click="delete({{ $vendor->id }})"
                            class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 ml-1">
                            Hapus
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-10 text-center italic text-gray-400">
                        Data vendor belum tersedia
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4 bg-gray-50 border-t">
            {{ $vendors->links() }}
        </div>
    </div>

    {{-- ================= MODAL CREATE / EDIT ================= --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-[9999] flex items-center justify-center">

        {{-- BACKDROP --}}
        <div class="absolute inset-0 bg-black/60" wire:click="closeModal"></div>

        {{-- MODAL --}}
        <div class="relative bg-white w-full max-w-4xl rounded-xl shadow-xl
                    max-h-[90vh] overflow-y-auto z-[10000] pointer-events-auto">

            <form wire:submit.prevent="save" class="flex flex-col">

                {{-- HEADER --}}
                <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white">
                    <h3 class="text-xl font-bold text-gray-800">
                        {{ $isEditing ? 'Edit Vendor' : 'Tambah Vendor Baru' }}
                    </h3>
                    <button type="button" wire:click="closeModal"
                            class="text-2xl text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                {{-- BODY --}}
                <div class="p-6 space-y-6">

                    <h4 class="font-bold text-gray-700 border-b pb-2">Informasi Vendor</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-bold">Nama Vendor *</label>
                            <input wire:model.defer="name"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                            @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>

                        <div>
                            <label class="text-sm font-bold">Kode Vendor</label>
                            <input wire:model="code" readonly
                                   class="w-full bg-gray-100 border-gray-300 rounded-lg shadow-sm font-bold text-sm">
                        </div>

                        <div>
                            <label class="text-sm font-bold">Kategori *</label>
                            <select wire:model.defer="category"
                                    class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                                <option value="">-- Pilih --</option>
                                <option>Shipping Line</option>
                                <option>Trucking</option>
                                <option>PPJK</option>
                                <option>Warehouse</option>
                                <option>Lainnya</option>
                            </select>
                            @error('category')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>

                        <div>
                            <label class="text-sm font-bold">NPWP</label>
                            <input wire:model.defer="npwp"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm font-bold">Alamat</label>
                            <textarea wire:model.defer="address"
                                      class="w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-sm font-bold">Info Bank</label>
                            <textarea wire:model.defer="bank_details"
                                      class="w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                        </div>

                        <div>
                            <label class="text-sm font-bold">Website</label>
                            <input wire:model.defer="website"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>
                    </div>

                    <h4 class="font-bold text-gray-700 border-b pb-2">Kontak Person (PIC)</h4>

                    @foreach($contacts as $i => $c)
                    <div wire:key="contact-{{ $i }}"
                         class="border border-gray-200 rounded-lg p-4 bg-gray-50 grid grid-cols-1 md:grid-cols-2 gap-4 relative">

                        <div>
                            <label class="text-xs font-bold">Nama PIC *</label>
                            <input wire:model.defer="contacts.{{ $i }}.pic_name"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="text-xs font-bold">Role / Divisi</label>
                            <input wire:model.defer="contacts.{{ $i }}.role"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="text-xs font-bold">Email *</label>
                            <input wire:model.defer="contacts.{{ $i }}.email"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>

                        <div>
                            <label class="text-xs font-bold">Telepon *</label>
                            <input wire:model.defer="contacts.{{ $i }}.phone"
                                   class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                        </div>

                        @if($i > 0)
                            <button type="button"
                                    wire:click="removeContact({{ $i }})"
                                    class="absolute top-2 right-2 text-red-500 hover:text-red-700">
                                ✕
                            </button>
                        @else
                            <span class="absolute top-2 right-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">
                                Kontak Utama
                            </span>
                        @endif
                    </div>
                    @endforeach

                    <button type="button"
                            wire:click="addContact"
                            class="text-green-600 font-bold text-sm hover:text-green-800">
                        + Tambah Kontak
                    </button>
                </div>

                {{-- FOOTER --}}
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-2 sticky bottom-0">
                    <button type="submit"
                            class="bg-blue-900 hover:bg-blue-800 text-white px-5 py-2 rounded-lg font-bold shadow">
                        Simpan
                    </button>
                    <button type="button" wire:click="closeModal"
                            class="px-5 py-2 border rounded-lg text-gray-700 hover:bg-gray-100">
                        Batal
                    </button>
                </div>

            </form>
        </div>
    </div>
    @endif
</div>
