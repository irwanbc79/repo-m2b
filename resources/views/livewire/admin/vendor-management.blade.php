<div class="space-y-6">
    @section('header', 'Manage Vendors')

    {{-- ALERT MESSAGES --}}
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-10 gap-3">
        <div class="bg-white rounded-xl p-3 shadow-sm border border-gray-100 text-center cursor-pointer hover:shadow-md transition" wire:click="$set('filterCategory', '')">
            <p class="text-xl font-black text-gray-800">{{ $stats["total"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">Total</p>
        </div>
        <div class="bg-amber-50 rounded-xl p-3 shadow-sm border border-amber-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Trucking' ? 'ring-2 ring-amber-500' : '' }}" wire:click="$set('filterCategory', 'Trucking')">
            <p class="text-xl font-black text-amber-600">{{ $stats["trucking"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">🚛 Trucking</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-3 shadow-sm border border-blue-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Shipping Line' ? 'ring-2 ring-blue-500' : '' }}" wire:click="$set('filterCategory', 'Shipping Line')">
            <p class="text-xl font-black text-blue-600">{{ $stats["shipping"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">🚢 Shipping</p>
        </div>
        <div class="bg-indigo-50 rounded-xl p-3 shadow-sm border border-indigo-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'PPJK' ? 'ring-2 ring-indigo-500' : '' }}" wire:click="$set('filterCategory', 'PPJK')">
            <p class="text-xl font-black text-indigo-600">{{ $stats["ppjk"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">📋 PPJK</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-3 shadow-sm border border-purple-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'TPS' ? 'ring-2 ring-purple-500' : '' }}" wire:click="$set('filterCategory', 'TPS')">
            <p class="text-xl font-black text-purple-600">{{ $stats["tps"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">📦 TPS</p>
        </div>
        <div class="bg-green-50 rounded-xl p-3 shadow-sm border border-green-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Depo' ? 'ring-2 ring-green-500' : '' }}" wire:click="$set('filterCategory', 'Depo')">
            <p class="text-xl font-black text-green-600">{{ $stats["depo"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">🏗️ Depo</p>
        </div>
        <div class="bg-cyan-50 rounded-xl p-3 shadow-sm border border-cyan-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Ground Handling' ? 'ring-2 ring-cyan-500' : '' }}" wire:click="$set('filterCategory', 'Ground Handling')">
            <p class="text-xl font-black text-cyan-600">{{ $stats["ground_handling"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">🛠️ GH</p>
        </div>
        <div class="bg-teal-50 rounded-xl p-3 shadow-sm border border-teal-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Operator Pelabuhan' ? 'ring-2 ring-teal-500' : '' }}" wire:click="$set('filterCategory', 'Operator Pelabuhan')">
            <p class="text-xl font-black text-teal-600">{{ $stats["operator_pelabuhan"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">⚓ Port</p>
        </div>
        <div class="bg-rose-50 rounded-xl p-3 shadow-sm border border-rose-100 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Airline' ? 'ring-2 ring-rose-500' : '' }}" wire:click="$set('filterCategory', 'Airline')">
            <p class="text-xl font-black text-rose-600">{{ $stats["airline"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">✈️ Airline</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 shadow-sm border border-gray-200 text-center cursor-pointer hover:shadow-md transition {{ $filterCategory == 'Lainnya' ? 'ring-2 ring-gray-500' : '' }}" wire:click="$set('filterCategory', 'Lainnya')">
            <p class="text-xl font-black text-gray-600">{{ $stats["lainnya"] ?? 0 }}</p>
            <p class="text-[10px] text-gray-500">📁 Lainnya</p>
        </div>
    </div>

    {{-- LIST VENDOR --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <input wire:model.live="search" type="text" placeholder="Cari Vendor..." class="w-64 pl-4 pr-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500">
                <select wire:model.live="filterCategory" class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Kategori</option>
                    <option value="Trucking">🚛 Trucking</option>
                    <option value="Shipping Line">🚢 Shipping Line</option>
                    <option value="PPJK">📋 PPJK</option>
                    <option value="Warehouse">🏭 Warehouse</option>
                    <option value="Airline">✈️ Airline</option>
                    <option value="Ground Handling">🛠️ Ground Handling</option>
                    <option value="TPS">📦 TPS</option>
                    <option value="Operator Pelabuhan">⚓ Operator Pelabuhan</option>
                    <option value="Depo">🏗️ Depo</option>
                    <option value="Lainnya">📁 Lainnya</option>
                </select>
                @if($filterCategory)
                <button wire:click="$set('filterCategory', '')" class="text-gray-500 hover:text-red-500 text-sm flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Reset
                </button>
                @endif
            </div>
            <button wire:click="create" class="bg-blue-900 hover:bg-blue-800 text-white px-4 py-2 rounded-lg font-bold flex items-center shadow-md shrink-0">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Vendor Baru
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 font-bold text-gray-600 uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-4 w-1/5">Kode / Kategori</th>
                        <th class="px-6 py-4 w-1/4">Nama Vendor</th>
                        <th class="px-6 py-4 w-1/4">Kontak Person</th>
                        <th class="px-6 py-4 w-1/5">Info Bank</th>
                        <th class="px-6 py-4 w-auto text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vendors as $vendor)
                    <tr wire:key="row-{{ $vendor->id }}" class="hover:bg-gray-50 transition">
                        
                        {{-- KOLOM 1: KODE / KATEGORI --}}
                        <td class="px-6 py-4">
                             <span class="block text-xs font-bold text-gray-800">{{ $vendor->code ?? '-' }}</span>
                            <span class="text-[10px] bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded font-bold uppercase">{{ $vendor->category }}</span>
                        </td>
                        
                        {{-- KOLOM 2: NAMA VENDOR --}}
                        <td class="px-6 py-4">
                            <span class="block font-bold text-gray-800">{{ $vendor->name }}</span>
                            <span class="text-xs text-gray-500">{{ $vendor->address }}</span>
                        </td>

                        {{-- KOLOM 3: KONTAK PERSON (HORIZONTAL LAYOUT) --}}
                        <td class="px-6 py-4">
                            @if($vendor->contacts->count() > 0)
                                <div class="flex flex-wrap gap-4">
                                    @foreach($vendor->contacts as $contact)
                                        <div class="min-w-[120px]">
                                            <span class="font-bold {{ $contact->is_primary ? 'text-blue-700' : 'text-gray-600' }} block text-sm">
                                                {{ $contact->pic_name }}
                                                @if($contact->is_primary)
                                                    <span class="text-[8px] bg-blue-100 text-blue-600 px-1 py-0.5 rounded ml-0.5">Utama</span>
                                                @endif
                                            </span>
                                            <span class="text-xs text-gray-500 block">{{ $contact->phone }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Belum ada kontak</span>
                            @endif
                        </td>
                        {{-- KOLOM 4: INFO BANK (NPWP + DETAIL BANK) --}}
                        <td class="px-6 py-4 text-xs text-gray-600">
                            <div class="space-y-1">
                                @if($vendor->npwp)
                                     <p class="font-bold text-gray-800 text-[11px]"><span class="text-gray-500 font-normal">NPWP:</span> {{ $vendor->npwp }}</p>
                                @endif
                                
                                @if($vendor->bank_details)
                                     <div class="p-2 bg-yellow-50 border border-yellow-200 rounded max-w-xs whitespace-pre-line text-[11px]">
                                         {{ $vendor->bank_details }}
                                     </div>
                                @elseif(!$vendor->npwp)
                                     <span class="text-xs text-gray-400 italic">Belum ada info bank</span>
                                @endif
                            </div>
                        </td>
                        
                        {{-- KOLOM 5: AKSI --}}
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <button wire:click="edit({{ $vendor->id }})" class="p-1.5 text-blue-600 border rounded hover:bg-blue-50" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button wire:click="delete({{ $vendor->id }})" wire:confirm="Yakin ingin menghapus vendor ini?" class="p-1.5 text-red-600 border rounded hover:bg-red-50" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">Data vendor tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t bg-gray-50">{{ $vendors->links() }}</div>
    </div>


    {{-- MODAL CREATE/EDIT VENDOR --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:click.self="closeModal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
            
            <div class="align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all w-full max-w-4xl">
                {{-- FIX: Form harus memiliki wire:submit.prevent --}}
                <form wire:submit.prevent="save">
                    {{-- Header Modal --}}
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-xl leading-6 font-bold text-gray-900">{{ $isEditing ? 'Edit Data Vendor' : 'Tambah Vendor Baru' }}</h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">&times;</button>
                    </div>

                    <div class="p-6 space-y-6">
                        
                        <h4 class="font-bold text-gray-700 border-b border-gray-200 pb-2">Informasi Perusahaan</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            {{-- Nama Vendor --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Vendor / PT *</label>
                                {{-- KUNCI: Ubah ke wire:model standar --}}
                                <input type="text" wire:model="name" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="PT. Vendor Jaya Logistik">
                                @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                            
                            {{-- Kode Vendor (FIX: ganti disabled jadi readonly) --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kode Vendor (Auto) *</label>
                                {{-- Kunci: wire:model.defer untuk field readonly agar nilainya terkirim --}}
                                <input type="text" wire:model="code" readonly class="w-full border-gray-300 rounded-lg shadow-sm text-sm bg-gray-100 text-gray-500 font-bold" placeholder="VEN-00X">
                                @error('code')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                            
                            {{-- Kategori --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kategori *</label>
                                {{-- KUNCI: Ubah ke wire:model standar --}}
                                <select wire:model.live="category" class="w-full border-gray-300 rounded-lg shadow-sm text-sm">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Shipping Line">Shipping Line</option>
                                    <option value="Trucking">Trucking</option>
                                    <option value="PPJK">PPJK</option>
                                    <option value="Warehouse">Warehouse</option>
                                    <option value="Airline">Airline</option>
                                    <option value="Ground Handling">Ground Handling</option>
                                    <option value="TPS">TPS (Terminal Petikemas/CFS)</option>
                                    <option value="Operator Pelabuhan">Operator Pelabuhan</option>
                                    <option value="Depo">Depo</option>
                                    <option value="Lainnya">Lainnya</option>
                                    <option value="custom">➕ Kategori Baru...</option>
                                </select>
                                @error('category')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                {{-- Input Custom Category --}}
                                @if($category === "custom")
                                <div class="mt-2">
                                    <input type="text" wire:model="customCategory" placeholder="Masukkan nama kategori baru..." class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-2 focus:ring-blue-500" required>
                                    @error("customCategory")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                </div>
                                @endif
                            </div>
                            
                            {{-- NPWP --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">NPWP</label>
                                <input type="text" wire:model="npwp" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="00.000.000.0-000.000">
                                @error('npwp')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                            
                            {{-- Alamat --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Lengkap</label>
                                <textarea wire:model="address" class="w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                                @error('address')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                            
                            {{-- Info Bank --}}
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Info Rekening Bank (Nama Bank, No Rek, Atas Nama)</label>
                                <textarea wire:model="bank_details" class="w-full border-gray-300 rounded-lg shadow-sm text-sm"></textarea>
                                @error('bank_details')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                            
                            {{-- Website --}}
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Website</label>
                                <input type="url" wire:model="website" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="https://">
                                @error('website')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <h4 class="font-bold text-gray-700 border-b border-gray-200 pb-2 pt-4">Kontak Person (PIC)</h4>
                        
                        {{-- LIST KONTAK PIC DINAMIS --}}
                        <div class="space-y-4">
                            @foreach($contacts as $index => $contact)
                            <div wire:key="contact-{{ $index }}" class="p-4 border rounded-lg bg-gray-50 flex items-start gap-4 shadow-sm relative">
                                
                                <div class="w-8/12 grid grid-cols-2 gap-3">
                                    {{-- Jika data lama masih ada di properti PIC_NAME_OLD, kita tampilkan notifikasi migrasi --}}
                                    @if($index === 0 && $pic_name_old)
                                        <div class="col-span-2 text-xs text-red-600 font-bold p-1 bg-red-100 rounded">
                                            Data ini diambil dari kolom kontak lama. Silakan verifikasi dan tambahkan PIC lain di bawah.
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500">Nama PIC *</label>
                                        {{-- FIX: Gunakan wire:model.blur untuk validasi instan --}}
                                        <input type="text" wire:model.blur="contacts.{{ $index }}.pic_name" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Nama Lengkap">
                                        {{-- FIX: Error display wajib ada --}}
                                        @error("contacts.$index.pic_name")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500">Role / Divisi</label>
                                        <input type="text" wire:model="contacts.{{ $index }}.role" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="Contoh: Operasional">
                                        @error("contacts.$index.role")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500">Email</label>
                                        <input type="email" wire:model="contacts.{{ $index }}.email" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="email@vendor.com">
                                        @error("contacts.$index.email")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500">Telepon *</label>
                                        <div class="flex items-center space-x-2">
                                            {{-- FIX: Gunakan wire:model.blur untuk validasi instan --}}
                                            <input type="text" wire:model.blur="contacts.{{ $index }}.phone" class="w-full border-gray-300 rounded-lg shadow-sm text-sm" placeholder="0812...">
                                        </div>
                                        {{-- FIX: Error display wajib ada --}}
                                        @error("contacts.$index.phone")<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <div class="w-4/12 text-right">
                                    @if($index === 0)
                                        <span class="text-[10px] bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full border border-blue-200">Kontak Utama</span>
                                    @else
                                        <button type="button" wire:click="removeContact({{ $index }})" class="text-red-400 hover:text-red-600 transition p-1">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <button type="button" wire:click="addContact" class="mt-4 text-sm text-green-600 font-bold hover:text-green-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah Kontak Lain
                        </button>

                    </div>
                    
                    {{-- Footer Modal --}}
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                        <button type="submit" wire:loading.attr="disabled" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-900 text-base font-medium text-white hover:bg-blue-800 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            <span wire:loading.remove wire:target="save">Simpan Data Vendor</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>