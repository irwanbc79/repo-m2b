<div class="space-y-6">
    @section('header', 'Manage Customers')

    {{-- Toast Notifications --}}
    @if (session()->has('message'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-2" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        {{ session('message') }}
        <button @click="show = false" class="ml-auto">&times;</button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl shadow-sm flex items-center gap-2" x-data="{ show: true }" x-show="show">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        {{ session('error') }}
        <button @click="show = false" class="ml-auto">&times;</button>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-gray-800">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-500">Total Customer</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-green-600">{{ $stats['new_this_month'] }}</p>
                    <p class="text-xs text-gray-500">Baru Bulan Ini</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-purple-600">{{ $stats['with_shipments'] }}</p>
                    <p class="text-xs text-gray-500">Aktif Kirim</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 rounded-lg">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-orange-600">{{ $stats['cities'] }}</p>
                    <p class="text-xs text-gray-500">Kota</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-black text-yellow-600">{{ $stats['vip_count'] }}</p>
                    <p class="text-xs text-gray-500">VIP Customer</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-lg font-black text-indigo-600">Rp {{ number_format($stats['total_credit_limit']/1000000, 0) }}M</p>
                    <p class="text-xs text-gray-500">Total Kredit</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Header with Search, Filter, Actions --}}
        <div class="p-4 border-b border-gray-100 bg-gray-50 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                {{-- Search --}}
                <div class="relative flex-1 max-w-md">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari customer, kode, NPWP, kota..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model.live="filterCity" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 py-2">
                        <option value="">Semua Kota</option>
                        @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterTag" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 py-2">
                        <option value="">Semua Tag</option>
                        @foreach($availableTags as $tag)
                        <option value="{{ $tag }}">{{ $tag }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="perPage" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 py-2">
                        <option value="10">10/hal</option>
                        <option value="25">25/hal</option>
                        <option value="50">50/hal</option>
                        <option value="100">100/hal</option>
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2">
                    @if(count($selectedCustomers) > 0)
                    <button wire:click="bulkDelete" wire:confirm="Yakin hapus {{ count($selectedCustomers) }} customer terpilih?" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-semibold hover:bg-red-600 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus ({{ count($selectedCustomers) }})
                    </button>
                    @endif

                    <button wire:click="exportExcel" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>

                    <button wire:click="create" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Customer
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs border-b">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-200" wire:click="sortBy('customer_code')">
                            <div class="flex items-center gap-1">
                                Kode / NPWP
                                @if($sortField === 'customer_code')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left cursor-pointer hover:bg-gray-200" wire:click="sortBy('company_name')">
                            <div class="flex items-center gap-1">
                                Perusahaan / Kota
                                @if($sortField === 'company_name')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                @endif
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">Kontak / HP</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Tag</th>
                        <th class="px-4 py-3 text-center">Shipments</th>
                        <th class="px-4 py-3 text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $c)
                    <tr class="hover:bg-blue-50/50 transition group cursor-pointer" wire:click="quickView({{ $c->id }})">
                        <td class="px-4 py-3" wire:click.stop>
                            <input type="checkbox" wire:model.live="selectedCustomers" value="{{ $c->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono font-bold text-blue-600 text-sm">{{ $c->customer_code }}</div>
                            @if($c->npwp)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-orange-100 text-orange-700 mt-1">
                                {{ $c->npwp }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $c->company_name }}</div>
                            @if($c->city)
                            <span class="text-xs text-gray-500 flex items-center mt-0.5">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                {{ $c->city }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-800 font-medium">{{ $c->user->name ?? '-' }}</div>
                            @if($c->phone)
                            <span class="text-xs text-blue-600">{{ $c->phone }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-sm">
                            {{ $c->user->email ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center" wire:click.stop>
                            <select wire:change="updateTag({{ $c->id }}, $event.target.value)" class="text-xs border-0 bg-transparent focus:ring-0 cursor-pointer font-semibold
                                {{ $c->business_type === 'VIP' ? 'text-yellow-600' : '' }}
                                {{ $c->business_type === 'Priority' ? 'text-purple-600' : '' }}
                                {{ $c->business_type === 'New' ? 'text-green-600' : '' }}
                                {{ $c->business_type === 'Inactive' ? 'text-red-600' : '' }}
                                {{ !$c->business_type || $c->business_type === 'Regular' ? 'text-gray-500' : '' }}">
                                <option value="Regular" {{ ($c->business_type ?? 'Regular') === 'Regular' ? 'selected' : '' }}>Regular</option>
                                <option value="VIP" {{ $c->business_type === 'VIP' ? 'selected' : '' }}>⭐ VIP</option>
                                <option value="Priority" {{ $c->business_type === 'Priority' ? 'selected' : '' }}>🔥 Priority</option>
                                <option value="New" {{ $c->business_type === 'New' ? 'selected' : '' }}>🆕 New</option>
                                <option value="Inactive" {{ $c->business_type === 'Inactive' ? 'selected' : '' }}>⏸ Inactive</option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold {{ $c->shipments_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $c->shipments_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center" wire:click.stop>
                            <div class="flex justify-center gap-1">
                                <button wire:click="quickView({{ $c->id }})" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Quick View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button wire:click="edit({{ $c->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button wire:click="confirmDelete({{ $c->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p class="font-medium">Tidak ada customer ditemukan</p>
                            <p class="text-sm">Coba ubah filter atau tambah customer baru</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-600">
                Menampilkan <span class="font-semibold">{{ $customers->firstItem() ?? 0 }}</span> - <span class="font-semibold">{{ $customers->lastItem() ?? 0 }}</span> dari <span class="font-semibold">{{ $customers->total() }}</span> customer
            </div>
            {{ $customers->links() }}
        </div>
    </div>

    {{-- Quick View Modal --}}
    @if($showQuickView && $quickViewCustomer)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeQuickView">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">{{ $quickViewCustomer->company_name }}</h3>
                    <p class="text-sm text-gray-500 font-mono">{{ $quickViewCustomer->customer_code }}</p>
                </div>
                <button wire:click="closeQuickView" class="text-gray-400 hover:text-gray-600 p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh] space-y-6">
                {{-- Stats Row --}}
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-black text-blue-600">{{ $quickViewCustomer->shipments_count ?? 0 }}</p>
                        <p class="text-xs text-gray-600">Total Shipment</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-black text-green-600">{{ $quickViewCustomer->invoices_count ?? 0 }}</p>
                        <p class="text-xs text-gray-600">Total Invoice</p>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-4 text-center">
                        <p class="text-lg font-black text-purple-600">Rp {{ number_format($quickViewCustomer->total_revenue ?? 0, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-600">Total Revenue</p>
                    </div>
                </div>

                {{-- Info Grid --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 text-xs uppercase mb-1">Kontak</p>
                        <p class="font-semibold">{{ $quickViewCustomer->user->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase mb-1">Email</p>
                        <p class="font-semibold">{{ $quickViewCustomer->user->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase mb-1">Telepon</p>
                        <p class="font-semibold">{{ $quickViewCustomer->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase mb-1">Kota</p>
                        <p class="font-semibold">{{ $quickViewCustomer->city ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase mb-1">NPWP</p>
                        <p class="font-semibold font-mono">{{ $quickViewCustomer->npwp ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-xs uppercase mb-1">Credit Limit</p>
                        <p class="font-semibold">Rp {{ number_format($quickViewCustomer->credit_limit ?? 0, 0, ',', '.') }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-gray-500 text-xs uppercase mb-1">Alamat</p>
                        <p class="font-semibold">{{ $quickViewCustomer->address ?? '-' }}</p>
                    </div>
                </div>

                {{-- Recent Shipments --}}
                @if($quickViewCustomer->shipments && $quickViewCustomer->shipments->count() > 0)
                <div>
                    <p class="text-gray-500 text-xs uppercase mb-2">Shipment Terakhir</p>
                    <div class="space-y-2">
                        @foreach($quickViewCustomer->shipments as $shipment)
                        <div class="flex justify-between items-center bg-gray-50 rounded-lg p-3">
                            <div>
                                <span class="font-mono text-sm font-semibold text-blue-600">{{ $shipment->awb_number ?? 'AWB-' . $shipment->id }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ $shipment->created_at?->format('d M Y') }}</span>
                            </div>
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $shipment->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($shipment->status ?? 'pending') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
                <button wire:click="closeQuickView" class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg transition">Tutup</button>
                <button wire:click="edit({{ $quickViewCustomer->id }})" wire:click.prefetch="closeQuickView" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Edit Customer</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Customer?</h3>
            <p class="text-gray-600 mb-6">Data customer akan dihapus permanen dan tidak dapat dikembalikan.</p>
            <div class="flex gap-3 justify-center">
                <button wire:click="cancelDelete" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">Batal</button>
                <button wire:click="delete" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">Ya, Hapus</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($isModalOpen)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="closeModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-800">{{ $isEditing ? 'Edit Customer' : 'Tambah Customer Baru' }}</h3>
            </div>
            <form wire:submit.prevent="save">
                <div class="p-6 overflow-y-auto max-h-[60vh] space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kode Customer</label>
                            <input type="text" wire:model="customer_code" class="w-full border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tag</label>
                            <select wire:model="customer_tag" class="w-full border-gray-300 rounded-lg">
                                <option value="">Regular</option>
                                @foreach($availableTags as $tag)
                                <option value="{{ $tag }}">{{ $tag }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Perusahaan *</label>
                            <input type="text" wire:model="company_name" class="w-full border-gray-300 rounded-lg focus:ring-blue-500" required>
                            @error('company_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NPWP</label>
                            <input type="text" wire:model="npwp" class="w-full border-gray-300 rounded-lg" placeholder="XX.XXX.XXX.X-XXX.XXX">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama PIC / Kontak *</label>
                            <input type="text" wire:model="name" class="w-full border-gray-300 rounded-lg focus:ring-blue-500" required>
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                            <input type="text" wire:model="phone" class="w-full border-gray-300 rounded-lg">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" wire:model="email" class="w-full border-gray-300 rounded-lg focus:ring-blue-500" required>
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password {{ $isEditing ? '(kosongkan jika tidak diubah)' : '*' }}</label>
                            <input type="password" wire:model="password" class="w-full border-gray-300 rounded-lg" {{ $isEditing ? '' : 'required' }}>
                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota</label>
                            <input type="text" wire:model="city" class="w-full border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Credit Limit</label>
                            <input type="number" wire:model="credit_limit" class="w-full border-gray-300 rounded-lg" min="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea wire:model="address" rows="2" class="w-full border-gray-300 rounded-lg"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Gudang</label>
                        <textarea wire:model="warehouse_address" rows="2" class="w-full border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-semibold">Batal</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">
                        {{ $isEditing ? 'Update' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
