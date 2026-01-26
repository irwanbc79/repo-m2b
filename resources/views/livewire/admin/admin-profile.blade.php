<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">👤 Admin Profile</h1>
            <p class="text-gray-600">Kelola informasi akun administrator Anda</p>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Profile Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Informasi Profil</h2>
                
                <form wire:submit.prevent="updateProfile">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" wire:model="name" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="email" disabled
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                            <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                            <input type="text" wire:model="phone" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="081234567890">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach((array)$roles as $r)
                                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $this->getRoleBadgeColor($r) }}">
                                        {{ ucfirst(str_replace('_', ' ', $r)) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        
                        <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🔐 Ubah Password</h2>
                
                <form wire:submit.prevent="updatePassword">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                            <input type="password" wire:model="current_password" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('current_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input type="password" wire:model="new_password" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('new_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                            <input type="password" wire:model="new_password_confirmation" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <button type="submit" 
                            class="w-full bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                            🔑 Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Info -->
        <div class="mt-6 bg-gray-50 rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">ℹ️ Informasi Akun</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">User ID:</span>
                    <span class="font-medium ml-2">{{ auth()->id() }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Login Terakhir:</span>
                    <span class="font-medium ml-2">{{ auth()->user()->last_login_at ? \Carbon\Carbon::parse(auth()->user()->last_login_at)->format('d M Y H:i') : '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Akun Dibuat:</span>
                    <span class="font-medium ml-2">{{ auth()->user()->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
