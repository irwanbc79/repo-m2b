<div>
    {{-- MODAL HANYA MUNCUL JIKA $isOpen BERNILAI TRUE --}}
    @if($isOpen)
    <div 
        x-data
        x-init="$el.classList.add('opacity-100')"
        class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity opacity-100"
    >
        {{-- KOTAK MODAL --}}
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] animate-fade-in-up overflow-hidden">
            
            {{-- Header Modal --}}
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <div>
                    <h3 class="font-bold text-lg text-gray-800">
                        {{ $isEditing ? 'Edit Data Customer' : 'Tambah Customer Baru' }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $isEditing ? 'Perbarui informasi detail pelanggan.' : 'Lengkapi data perusahaan dan legalitas.' }}
                    </p>
                </div>
                {{-- Tombol Close (Silang) --}}
                <button wire:click="closeModal" class="text-gray-400 hover:text-red-500 transition p-2 hover:bg-gray-100 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Body Modal (Scrollable) --}}
            <div class="p-6 overflow-y-auto flex-1">
                <form wire:submit.prevent="store" class="space-y-6">
                    
                    {{-- INFORMASI PERUSAHAAN --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Perusahaan (PT/CV)</label>
                            <input type="text" wire:model="companyName" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-m2b-primary focus:border-m2b-primary text-sm p-2.5 border" placeholder="Contoh: PT. MAJU JAYA">
                            @error('companyName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kota / Negara</label>
                            <input type="text" wire:model="city" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2.5 border">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">No. Telepon</label>
                            <input type="text" wire:model="phone" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2.5 border">
                        </div>
                    </div>

                    {{-- DATA AKUN (LOGIN) --}}
                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <h4 class="text-xs font-bold text-blue-800 uppercase mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Data Akun PIC (Login)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nama PIC</label>
                                <input type="text" wire:model="name" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2 border bg-white">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Email</label>
                                <input type="email" wire:model="email" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2 border bg-white">
                                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 mb-1">
                                    Password 
                                    @if($isEditing) <span class="text-gray-400 font-normal">(Isi hanya jika ingin ubah password)</span> @endif
                                </label>
                                <input type="password" wire:model="password" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2 border bg-white" placeholder="{{ $isEditing ? 'Biarkan kosong jika tidak diubah' : 'Minimal 6 karakter' }}">
                                @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    {{-- DATA PAJAK (FEATURE BARU) --}}
                    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-orange-100 rounded-full opacity-50"></div>
                        <h4 class="text-xs font-bold text-orange-800 uppercase mb-3 flex items-center gap-2 relative z-10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Data Perpajakan (E-Faktur)
                        </h4>
                        <div class="grid grid-cols-1 gap-4 relative z-10">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Nomor NPWP</label>
                                <input type="text" wire:model="npwp" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2 border font-mono bg-white" placeholder="00.000.000.0-000.000">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Alamat Pajak (Sesuai SKT)</label>
                                <textarea wire:model="taxAddress" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2 border h-16 bg-white placeholder-gray-400" placeholder="Alamat yang tertera pada kartu NPWP..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- ALAMAT OPERASIONAL --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Kantor</label>
                            <textarea wire:model.live="address" class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2.5 border h-24" placeholder="Alamat domisili kantor..."></textarea>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-sm font-bold text-gray-700">Alamat Gudang</label>
                                <label class="flex items-center cursor-pointer gap-2">
                                    <input type="checkbox" wire:model.live="useOfficeAsWarehouse" class="rounded text-blue-600 w-4 h-4">
                                    <span class="text-xs text-gray-500">Sama dengan Kantor</span>
                                </label>
                            </div>
                            <textarea 
                                wire:model="warehouseAddress" 
                                class="w-full border-gray-300 rounded-lg shadow-sm text-sm p-2.5 border h-24 disabled:bg-gray-100 disabled:text-gray-400" 
                                placeholder="Alamat pengiriman barang..."
                                @if($useOfficeAsWarehouse) disabled @endif
                            ></textarea>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Footer Modal --}}
            <div class="p-5 border-t bg-gray-50 flex justify-end gap-3">
                <button wire:click="closeModal" class="px-5 py-2.5 border border-gray-300 rounded-lg bg-white hover:bg-gray-100 text-sm font-bold text-gray-600 transition">Batal</button>
                <button wire:click="store" class="px-6 py-2.5 bg-m2b-primary text-white rounded-lg font-bold text-sm hover:bg-blue-900 shadow-lg shadow-blue-900/20 transition transform hover:-translate-y-0.5">
                    {{ $isEditing ? 'Simpan Perubahan' : 'Buat Customer' }}
                </button>
            </div>
        </div>
    </div>
    @endif
</div>