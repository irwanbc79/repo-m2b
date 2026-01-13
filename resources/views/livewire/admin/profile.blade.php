<div class="space-y-6">
    @section('header', 'My Profile')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Administrator Info
                </h3>

                @if (session()->has('message'))
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-3 mb-4 text-sm font-medium">
                        {{ session('message') }}
                    </div>
                @endif

                <form wire:submit.prevent="updateProfile" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Role</label>
                            <input type="text" value="Super Admin" class="mt-1 block w-full bg-gray-100 border border-gray-300 rounded-md shadow-sm py-2 px-3 text-gray-500 sm:text-sm font-bold" readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">Full Name</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Email Address</label>
                        <input type="email" wire:model="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                        @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" wire:loading.attr="disabled" class="bg-gray-900 text-white px-5 py-2 rounded-lg hover:bg-gray-800 transition shadow-sm text-sm font-bold tracking-wide">
                            <span wire:loading.remove wire:target="updateProfile">SAVE CHANGES</span>
                            <span wire:loading wire:target="updateProfile">SAVING...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-m2b-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Admin Security
                </h3>

                @if (session()->has('password_message'))
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-3 mb-4 text-sm font-medium">
                        {{ session('password_message') }}
                    </div>
                @endif

                <form wire:submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" wire:model="current_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-m2b-accent focus:border-m2b-accent sm:text-sm">
                        @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" wire:model="new_password" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-m2b-accent focus:border-m2b-accent sm:text-sm">
                        @error('new_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" wire:model="new_password_confirmation" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-m2b-accent focus:border-m2b-accent sm:text-sm">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="w-full bg-white border-2 border-m2b-accent text-m2b-accent hover:bg-red-50 px-4 py-2 rounded-lg transition shadow-sm text-sm font-bold mt-2">
                        <span wire:loading.remove wire:target="updatePassword">UPDATE PASSWORD</span>
                        <span wire:loading wire:target="updatePassword">UPDATING...</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>