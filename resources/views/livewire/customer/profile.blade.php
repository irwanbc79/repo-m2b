<div class="space-y-6">
    @section('header', 'My Profile')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-m2b-primary mb-4 flex items-center border-b pb-2">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Data Perusahaan
                </h3>

                <form wire:submit.prevent="updateProfile">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Perusahaan</label>
                            <input type="text" wire:model="company_name" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-m2b-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NPWP Perusahaan</label>
                            <input type="text" wire:model="npwp" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="00.000.000.0-000.000">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap (Kantor)</label>
                        <textarea wire:model="address" rows="2" class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota / Wilayah</label>
                            <input type="text" wire:model="city" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon / HP PIC</label>
                            <input type="text" wire:model="phone" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="0812...">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Gudang (Warehouse)</label>
                        <textarea wire:model="warehouse_address" rows="2" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Jika berbeda dengan alamat kantor"></textarea>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 mb-4 mt-8 flex items-center border-b pb-2">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Akun Login (PIC)
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap PIC</label>
                            <input type="text" wire:model="name" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Login</label>
                            <input type="email" wire:model="email" class="w-full border-gray-300 rounded-md shadow-sm bg-gray-50" readonly>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                         @if (session()->has('message'))
                            <span class="text-green-600 text-sm font-bold mr-4 flex items-center">{{ session('message') }}</span>
                        @endif
                        <button type="submit" wire:loading.attr="disabled" class="bg-m2b-primary text-white px-6 py-2 rounded-lg hover:bg-blue-900 transition shadow-lg font-bold">
                            <span wire:loading.remove>Simpan Perubahan</span>
                            <span wire:loading>Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center border-b pb-2 border-red-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Ganti Password
                </h3>

                @if (session()->has('password_message'))
                    <div class="bg-green-50 text-green-700 p-3 rounded mb-4 text-sm border border-green-200">
                        {{ session('password_message') }}
                    </div>
                @endif

                <form wire:submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                        <input type="password" wire:model="current_password" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500">
                        @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password Baru</label>
                        <input type="password" wire:model="new_password" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500">
                        @error('new_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ulangi Password Baru</label>
                        <input type="password" wire:model="new_password_confirmation" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="w-full bg-white border border-red-600 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg transition font-bold">
                        Update Password
                    </button>
                </form>
            </div>

            <div class="mt-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
                <p class="text-xs text-gray-500 uppercase font-bold mb-1">Customer Code</p>
                <p class="text-2xl font-black text-m2b-primary font-mono tracking-wider">{{ $customer_code }}</p>
            </div>
        </div>

    </div>
</div>