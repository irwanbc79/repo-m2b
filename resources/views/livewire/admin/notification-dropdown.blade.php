<div class="relative" x-data="{ open: false }" wire:poll.30s="loadNotifications">
    
    {{-- TOMBOL LONCENG --}}
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-600 transition focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        
        @if($totalNotif > 0)
            <span class="absolute top-1 right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[9px] text-white font-bold justify-center items-center">
                    {{ $totalNotif > 9 ? '9+' : $totalNotif }}
                </span>
            </span>
        @endif
    </button>

    {{-- DROPDOWN MENU --}}
    <div x-show="open" @click.away="open = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-50 origin-top-right"
         style="display: none;">
        
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-700">Notifikasi</h3>
            <span class="text-xs text-gray-500">{{ $totalNotif }} Baru</span>
        </div>

        <div class="max-h-80 overflow-y-auto">
            @if($totalNotif == 0)
                <div class="p-6 text-center text-gray-400 text-sm">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    Tidak ada notifikasi baru.
                </div>
            @else
                
                {{-- 1. BOOKING BARU --}}
                @foreach($newBookings as $shipment)
                <a href="{{ route('admin.shipments.show', $shipment->id) }}" class="block p-3 hover:bg-blue-50 border-b border-gray-50 transition group">
                    <div class="flex gap-3">
                        <div class="bg-blue-100 text-blue-600 p-2 rounded-full h-8 w-8 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-800 group-hover:text-blue-700">Booking Baru #{{ $shipment->awb_number }}</p>
                            <p class="text-[10px] text-gray-500">{{ $shipment->customer->company_name ?? 'Unknown' }} • {{ $shipment->origin }}</p>
                            <p class="text-[9px] text-blue-400 mt-1">{{ $shipment->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
                @endforeach

                {{-- 2. INVOICE DUE --}}
                @foreach($dueInvoices as $inv)
                <a href="{{ route('admin.invoices.index') }}" class="block p-3 hover:bg-red-50 border-b border-gray-50 transition group">
                    <div class="flex gap-3">
                        <div class="bg-red-100 text-red-600 p-2 rounded-full h-8 w-8 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-800 group-hover:text-red-700">Tagihan Jatuh Tempo</p>
                            <p class="text-[10px] text-gray-500">{{ $inv->invoice_number }} • Rp {{ number_format($inv->grand_total) }}</p>
                            <p class="text-[9px] text-red-500 mt-1 font-bold">Due: {{ \Carbon\Carbon::parse($inv->due_date)->format('d M') }}</p>
                        </div>
                    </div>
                </a>
                @endforeach

                {{-- 3. CUSTOMER BARU --}}
                @foreach($newCustomers as $user)
                <a href="{{ route('admin.customers.index') }}" class="block p-3 hover:bg-green-50 border-b border-gray-50 transition group">
                    <div class="flex gap-3">
                        <div class="bg-green-100 text-green-600 p-2 rounded-full h-8 w-8 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-800 group-hover:text-green-700">Customer Baru</p>
                            <p class="text-[10px] text-gray-500">{{ $user->name }}</p>
                            <p class="text-[9px] text-green-500 mt-1">Join: {{ $user->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
                @endforeach

            @endif
        </div>
        
        <div class="bg-gray-50 p-2 text-center border-t border-gray-100">
            <p class="text-[9px] text-gray-400">Update otomatis setiap 30 detik</p>
        </div>
    </div>
</div>