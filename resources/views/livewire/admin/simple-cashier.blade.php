<div class="space-y-6" x-data x-on:scroll-to-form.window="document.getElementById('cashier-form').scrollIntoView({behavior: 'smooth', block: 'start'})">
    @section('header', 'Manajemen Kasir')

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- LEFT COLUMN: INPUT FORM --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Input Transaksi Kas
                        @if($editingId)
                            <span class="ml-2 px-2 py-0.5 bg-orange-100 text-orange-700 text-xs rounded-full font-bold animate-pulse">✏️ Mode Edit #{{ $editingId }}</span>
                        @endif
                    </h3>
                    <button 
                        wire:click="resetForm" 
                        type="button"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" id="cashier-form" class="space-y-4">
                    
                    {{-- Tanggal & Tipe Transaksi --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="date" 
                                wire:model.live="transaction_date"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipe Transaksi <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-4 mt-2">
                                <label class="flex items-center">
                                    <input 
                                        type="radio" 
                                        wire:model.live="transaction_type" 
                                        value="cash_in"
                                        class="mr-2 text-green-600 focus:ring-green-500"
                                    >
                                    <span class="text-sm font-medium text-green-700">💰 Terima Uang</span>
                                </label>
                                <label class="flex items-center">
                                    <input 
                                        type="radio" 
                                        wire:model.live="transaction_type" 
                                        value="cash_out"
                                        class="mr-2 text-red-600 focus:ring-red-500"
                                    >
                                    <span class="text-sm font-medium text-red-700">💸 Keluar Uang</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Counterpart Type --}}
                    @if($transaction_type)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $transaction_type === 'cash_in' ? 'Terima Dari' : 'Bayar Ke' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="flex space-x-4 mt-2">
                            <label class="flex items-center">
                                <input 
                                    type="radio" 
                                    wire:model.live="counterpart_type" 
                                    value="customer"
                                    class="mr-2 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm">👤 Customer</span>
                            </label>
                            <label class="flex items-center">
                                <input 
                                    type="radio" 
                                    wire:model.live="counterpart_type" 
                                    value="vendor"
                                    class="mr-2 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm">🏢 Vendor</span>
                            </label>
                        </div>
                    </div>
                    @endif

                    {{-- Customer/Vendor Selection with Search --}}
                    {{-- wire:ignore mencegah Livewire morphdom me-reset Alpine state (open/selected) saat re-render --}}
                    @if($counterpart_type)
                    <div wire:ignore>
                    <div x-data="{
                        open: false,
                        search: '',
                        selected: $wire.counterpart_id,
                        allCustomers: @js($customers),
                        allVendors: @js($vendors),
                        items: [],
                        init() {
                            this.items = this.$wire.counterpart_type === 'customer'
                                ? this.allCustomers
                                : this.allVendors;
                            this.$watch('$wire.counterpart_type', (value) => {
                                this.items = value === 'customer' ? this.allCustomers : this.allVendors;
                                this.selected = null;
                                this.search = '';
                                this.open = false;
                            });
                            this.$watch('$wire.counterpart_id', (value) => {
                                this.selected = value;
                            });
                        },
                        get filteredItems() {
                            if (!this.search) return this.items;
                            return this.items.filter(item =>
                                (item.code + ' ' + item.name).toLowerCase().includes(this.search.toLowerCase())
                            );
                        },
                        get selectedLabel() {
                            const item = this.items.find(i => i.id == this.selected);
                            return item ? item.code + ' - ' + item.name : '';
                        },
                        get typeLabel() {
                            return this.$wire.counterpart_type === 'customer' ? 'Customer' : 'Vendor';
                        },
                        selectItem(id) {
                            this.selected = id;
                            this.open = false;
                            this.search = '';
                            this.$wire.set('counterpart_id', id);
                        }
                    }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Pilih <span x-text="typeLabel"></span> <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <button type="button" @click="open = !open" class="w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <span x-text="selectedLabel || ('-- Pilih ' + typeLabel + ' --')" class="block truncate" :class="{'text-gray-400': !selected}"></span>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-[100] mt-1 w-full bg-white shadow-lg max-h-60 rounded-md overflow-hidden border border-gray-200">
                                <div class="p-2 border-b sticky top-0 bg-white">
                                    <input type="text" x-model="search" placeholder="Ketik untuk mencari..." class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" @click.stop>
                                </div>
                                <ul class="max-h-48 overflow-y-auto">
                                    <template x-for="item in filteredItems" :key="item.id">
                                        <li @mousedown.prevent="selectItem(item.id)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-50" :class="{'bg-blue-100': selected == item.id}">
                                            <span x-text="item.code + ' - ' + item.name" class="block truncate text-sm"></span>
                                        </li>
                                    </template>
                                    <li x-show="filteredItems.length === 0" class="py-2 pl-3 text-gray-500 text-sm">Tidak ditemukan</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    </div>
                    @endif

                    {{-- Shipment Selection with Search --}}
                    @if($counterpart_id && count($shipments) > 0)
                    <div x-data="{
                        open: false,
                        search: '',
                        selected: $wire.shipment_id,
                        items: @js($shipments),
                        get filteredItems() {
                            if (!this.search) return this.items;
                            return this.items.filter(item => {
                                const text = (item.awb_number || '') + ' ' + (item.origin || '') + ' ' + (item.destination || '') + ' ' + (item.status || '');
                                return text.toLowerCase().includes(this.search.toLowerCase());
                            });
                        },
                        get selectedLabel() {
                            const item = this.items.find(i => i.id == this.selected);
                            if (!item) return '';
                            return (item.awb_number || '-') + ' | ' + (item.customer_name || '-') + ' | ' + (item.origin || '-') + ' → ' + (item.destination || '-') + ' (' + (item.status || '-').toUpperCase() + ')';
                        },
                        selectItem(id) {
                            this.selected = id;
                            $wire.set('shipment_id', id);
                            this.open = false;
                            this.search = '';
                        }
                    }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Untuk Shipment (Opsional)
                        </label>
                        <div class="relative">
                            <button type="button" @click="open = !open" class="w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <span x-text="selectedLabel || '-- Pilih Shipment --'" class="block truncate" :class="{'text-gray-400': !selected}"></span>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md overflow-hidden border border-gray-200">
                                <div class="p-2 border-b sticky top-0 bg-white">
                                    <input type="text" x-model="search" placeholder="Cari AWB, origin, destination..." class="w-full border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" @click.stop>
                                </div>
                                <ul class="max-h-48 overflow-y-auto">
                                    <li @click="selectItem('')" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-50 text-gray-500 text-sm">-- Tanpa Shipment --</li>
                                    <template x-for="item in filteredItems" :key="item.id">
                                        <li @click="selectItem(item.id)" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-50" :class="{'bg-blue-100': selected == item.id}">
                                            <span class="block truncate text-sm" x-text="(item.awb_number || '-') + ' | ' + (item.customer_name || '-') + ' | ' + (item.origin || '-') + ' → ' + (item.destination || '-') + ' (' + (item.status || '-').toUpperCase() + ')'"></span>
                                        </li>
                                    </template>
                                    <li x-show="filteredItems.length === 0" class="py-2 pl-3 text-gray-500 text-sm">Tidak ditemukan</li>
                                </ul>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Pilih shipment jika transaksi terkait dengan pengiriman tertentu</p>
                    </div>
                    @endif
                    {{-- Cost Category (if cash out) --}}
                    @if($transaction_type === 'cash_out')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kategori Biaya <span class="text-red-500">*</span>
                        </label>
                        <div class="flex space-x-4 mt-2">
                            <label class="flex items-center">
                                <input 
                                    type="radio" 
                                    wire:model.live="cost_category" 
                                    value="shipment"
                                    class="mr-2 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm">📦 Biaya Shipment</span>
                            </label>
                            <label class="flex items-center">
                                <input 
                                    type="radio" 
                                    wire:model.live="cost_category" 
                                    value="overhead"
                                    class="mr-2 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-sm">🏢 Biaya Overhead</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            @if($cost_category === 'shipment')
                                Biaya ini akan masuk ke Job Costing dan mempengaruhi profit margin shipment
                            @else
                                Biaya umum operasional (tidak masuk job costing shipment)
                            @endif
                        </p>
                    </div>
                    @endif

                    {{-- Amount & Currency --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Jumlah <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                wire:model.live.debounce.500ms="amount"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Mata Uang
                            </label>
                            <select 
                                wire:model.live="currency"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="IDR">IDR</option>
                                <option value="USD">USD</option>
                                <option value="CNY">CNY</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>

                    {{-- Exchange Rate (if not IDR) --}}
                    @if($currency !== 'IDR')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kurs (1 {{ $currency }} = ? IDR) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            wire:model.live="exchange_rate"
                            step="0.01"
                            min="0"
                            placeholder="16000"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                        @if($amount && $exchange_rate)
                        <p class="mt-1 text-xs text-gray-600">
                            = Rp {{ number_format($amount * $exchange_rate, 0, ',', '.') }}
                        </p>
                        @endif
                    </div>
                    @endif

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Keterangan
                        </label>
                        <textarea 
                            wire:model.live="description"
                            rows="2"
                            placeholder="Contoh: Pembayaran freight Shanghai - Belawan"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>

                    {{-- Attachment --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Bukti Pembayaran / Kwitansi
                        </label>
                        <input 
                            type="file" 
                            wire:model="attachment"
                            accept="image/*,.pdf"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                        <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, PDF (Max 5MB)</p>
                        
                        @if($attachment)
                        <div class="mt-2 flex items-center text-sm text-green-600">
                            <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            File siap diupload: {{ $attachment->getClientOriginalName() }}
                        </div>
                        @endif
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button 
                            type="button"
                            wire:click="resetForm"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Reset
                        </button>
                        <button 
                            type="submit"
                            wire:loading.attr="disabled"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="save">{{ $editingId ? '✏️ Update Transaksi' : '💾 Simpan Transaksi' }}</span>
                            <span wire:loading wire:target="save">
                                <svg class="animate-spin h-5 w-5 inline" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT COLUMN: PREVIEW --}}
        <div class="lg:col-span-1">
            
            {{-- Journal Preview --}}
            @if($showPreview && $preview)
            <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl shadow-sm border border-slate-200 p-6 sticky top-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Preview Journal Entry
                </h3>

                <div class="bg-white rounded-lg p-4 mb-4">
                    <div class="text-xs text-gray-500 mb-1">No. Jurnal (Auto)</div>
                    <div class="text-sm font-mono font-bold text-gray-800">{{ $preview['journal_number'] ?? 'JV-PREVIEW' }}</div>
                </div>

                <div class="bg-white rounded-lg overflow-hidden mb-4">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-700 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">AKUN</th>
                                <th class="px-3 py-2 text-right font-semibold">DEBIT</th>
                                <th class="px-3 py-2 text-right font-semibold">KREDIT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach(($preview['entries'] ?? []) as $entry)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-800">{{ $entry['account_code'] }}</div>
                                    <div class="text-gray-600 text-xs">{{ $entry['account_name'] }}</div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @if($entry['debit'] > 0)
                                    <span class="text-green-600 font-semibold">
                                        {{ number_format($entry['debit'], 0, ',', '.') }}
                                    </span>
                                    @else
                                    <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @if($entry['credit'] > 0)
                                    <span class="text-blue-600 font-semibold">
                                        {{ number_format($entry['credit'], 0, ',', '.') }}
                                    </span>
                                    @else
                                    <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                    @endforeach
                            <tr class="bg-slate-100 font-bold">
                                <td class="px-3 py-2">TOTAL</td>
                                <td class="px-3 py-2 text-right text-green-700">
                                    {{ number_format(($preview['total']['debit'] ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-right text-blue-700">
                                    {{ number_format(($preview['total']['credit'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                </div>
                
                {{-- Explanation --}}
                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-xs text-blue-800 leading-relaxed">{{ $preview['explanation'] ?? '' }}</p>
                    </div>
                </div>

                {{-- Impact Preview --}}
                @if(isset($preview['impact']) && is_array($preview['impact']) && count($preview['impact']) > 0)
                <div class="mt-4 space-y-2">
                    <div class="text-xs font-semibold text-gray-700 mb-2">📊 Impact:</div>
                    @foreach(($preview['impact'] ?? []) as $impact)
                        @if($impact['type'] === 'account')
                        <div class="bg-white rounded p-2 text-xs">
                            <div class="font-medium text-gray-700">{{ $impact['label'] }}</div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-gray-600">{{ $impact['current'] }}</span>
                                <svg class="w-4 h-4 {{ $impact['change'] === 'increase' ? 'text-green-500' : 'text-red-500' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-semibold {{ $impact['change'] === 'increase' ? 'text-green-600' : 'text-red-600' }}">{{ $impact['after'] }}</span>
                            </div>
                        </div>
                        @elseif($impact['type'] === 'shipment')
                        <div class="bg-amber-50 rounded p-2 text-xs border border-amber-200">
                            <div class="font-medium text-amber-900">{{ $impact['label'] }}</div>
                            <div class="mt-1 space-y-1 text-amber-800">
                                <div class="flex justify-between">
                                    <span>Revenue:</span>
                                    <span class="font-semibold">{{ $impact['revenue'] }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Cost:</span>
                                    <span>{{ $impact['current_cost'] }} → <strong>{{ $impact['new_cost'] }}</strong></span>
                                </div>
                                <div class="flex justify-between border-t border-amber-300 pt-1">
                                    <span>Profit:</span>
                                    <span class="font-bold">{{ $impact['profit'] }} ({{ $impact['margin'] }})</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                @endif

                <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded">
                    <p class="text-xs text-yellow-800">
                        ⚠️ Ini hanya preview. Journal entry akan otomatis tersimpan setelah Anda klik "Simpan Transaksi".
                    </p>
                </div>
            </div>
            @else
            <div class="bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-600">Preview jurnal akan muncul saat Anda mengisi form</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Recent Transactions (Bottom Section) --}}
    {{-- @if(count($recentTransactions) > 0) --}}

    {{-- Summary Stats Bar --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6">
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex flex-col">
            <div class="text-xs font-semibold text-green-600 uppercase tracking-wide">Total Masuk</div>
            <div class="mt-1 text-lg font-bold text-green-700 truncate">IDR {{ number_format($summaryTotalIn, 0, ',', '.') }}</div>
            <div class="text-xs text-green-500 mt-0.5">Dana diterima periode ini</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex flex-col">
            <div class="text-xs font-semibold text-red-600 uppercase tracking-wide">Total Keluar</div>
            <div class="mt-1 text-lg font-bold text-red-700 truncate">IDR {{ number_format($summaryTotalOut, 0, ',', '.') }}</div>
            <div class="text-xs text-red-500 mt-0.5">Dana dikeluarkan periode ini</div>
        </div>
        @php $netCash = $summaryTotalIn - $summaryTotalOut; @endphp
        <div class="{{ $netCash >= 0 ? 'bg-blue-50 border-blue-200' : 'bg-orange-50 border-orange-200' }} border rounded-xl p-4 flex flex-col">
            <div class="text-xs font-semibold {{ $netCash >= 0 ? 'text-blue-600' : 'text-orange-600' }} uppercase tracking-wide">Net Kas</div>
            <div class="mt-1 text-lg font-bold {{ $netCash >= 0 ? 'text-blue-700' : 'text-orange-700' }} truncate">
                {{ $netCash < 0 ? '-' : '' }}IDR {{ number_format(abs($netCash), 0, ',', '.') }}
            </div>
            <div class="text-xs {{ $netCash >= 0 ? 'text-blue-500' : 'text-orange-500' }} mt-0.5">Selisih masuk vs keluar</div>
        </div>
        <div class="{{ $filterNoJournal ? 'bg-orange-50 border-2 border-orange-400' : 'bg-gray-50 border border-gray-200' }} rounded-xl p-4 flex flex-col transition-all">
            <div class="text-xs font-semibold {{ $filterNoJournal ? 'text-orange-600' : 'text-gray-500' }} uppercase tracking-wide">
                {{ $filterNoJournal ? 'Tanpa Jurnal' : 'Transaksi' }}
            </div>
            <div class="mt-1 text-lg font-bold {{ $filterNoJournal ? 'text-orange-700' : 'text-gray-700' }}">{{ number_format($summaryCount, 0, ',', '.') }}</div>
            <div class="text-xs {{ $filterNoJournal ? 'text-orange-500 font-medium' : 'text-gray-400' }} mt-0.5">
                {{ $filterNoJournal ? 'Perlu rekonsiliasi' : 'Total hasil filter' }}
            </div>
        </div>
    </div>

    {{-- Main Transactions Card --}}
    <div class="{{ $filterNoJournal ? 'border-2 border-orange-400' : 'border border-gray-200' }} bg-white rounded-xl shadow-sm mt-4 overflow-hidden transition-all">

        {{-- Card Header: Title + Search + Controls --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3 px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2 flex-shrink-0">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-bold text-gray-900 whitespace-nowrap">Transaksi Kas</h3>
                @if($filterNoJournal)
                <span class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs font-bold rounded-full animate-pulse">Mode Rekonsiliasi</span>
                @endif
            </div>

            {{-- Live Search --}}
            <div class="relative flex-1">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       wire:model.live.debounce.300ms="searchTerm"
                       placeholder="Cari customer, vendor, AWB shipment, keterangan..."
                       class="w-full pl-9 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                @if(!empty($searchTerm))
                <button wire:click="$set('searchTerm', '')" class="absolute right-2.5 top-1.5 w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                @php
                    $activeFilterCount = ($filterType !== 'all' ? 1 : 0)
                        + ($filterStatus !== 'all' ? 1 : 0)
                        + ($filterCounterpartType !== 'all' ? 1 : 0)
                        + ($filterCostCategory !== 'all' ? 1 : 0)
                        + ($filterCurrency !== 'all' ? 1 : 0)
                        + (!empty($filterAmountMin) ? 1 : 0)
                        + (!empty($filterAmountMax) ? 1 : 0)
                        + ($filterNoJournal ? 1 : 0);
                @endphp
                <button wire:click="toggleFilters"
                        class="relative px-3 py-2 text-sm font-medium border rounded-lg transition-colors {{ $showFilters ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                    @if($activeFilterCount > 0)
                    <span class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center leading-none">{{ $activeFilterCount }}</span>
                    @endif
                </button>
                <button wire:click="exportExcel" class="px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Excel
                </button>
                <button wire:click="exportPdf" class="px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 flex items-center gap-1.5 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </button>
            </div>
        </div>

        {{-- Advanced Filter Panel (collapsible) --}}
        @if($showFilters)
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 space-y-3">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
                    <input type="date" wire:model.live="filterDateFrom"
                           class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
                    <input type="date" wire:model.live="filterDateTo"
                           class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Transaksi</label>
                    <select wire:model.live="filterType"
                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Tipe</option>
                        <option value="in">Terima (Masuk)</option>
                        <option value="out">Keluar (Bayar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status Posting</label>
                    <select wire:model.live="filterStatus"
                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Status</option>
                        <option value="posted">Posted</option>
                        <option value="draft">Draft / Pending</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Mitra</label>
                    <select wire:model.live="filterCounterpartType"
                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Mitra</option>
                        <option value="customer">Customer</option>
                        <option value="vendor">Vendor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori Biaya</label>
                    <select wire:model.live="filterCostCategory"
                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Kategori</option>
                        <option value="shipment">Shipment</option>
                        <option value="overhead">Overhead</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Mata Uang</label>
                    <select wire:model.live="filterCurrency"
                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Semua Mata Uang</option>
                        <option value="IDR">IDR (Rupiah)</option>
                        <option value="USD">USD (Dolar)</option>
                        <option value="CNY">CNY (Yuan)</option>
                        <option value="EUR">EUR (Euro)</option>
                    </select>
                </div>
                <div class="flex items-end pb-0.5">
                    <label class="flex items-start gap-3 cursor-pointer group w-full p-3 rounded-lg border-2 border-dashed {{ $filterNoJournal ? 'border-orange-400 bg-orange-50' : 'border-gray-300 hover:border-orange-300 hover:bg-orange-50/50' }} transition-all">
                        <input type="checkbox" wire:model.live="filterNoJournal"
                               class="mt-0.5 rounded border-gray-300 text-orange-500 focus:ring-orange-500 focus:ring-2">
                        <div>
                            <div class="text-sm font-semibold {{ $filterNoJournal ? 'text-orange-700' : 'text-gray-700' }}">Tanpa Jurnal</div>
                            <div class="text-xs {{ $filterNoJournal ? 'text-orange-500' : 'text-gray-400' }}">Temukan selisih akuntansi</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Minimal (IDR)</label>
                    <input type="number" wire:model.live.debounce.500ms="filterAmountMin"
                           placeholder="0"
                           class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Maksimal (IDR)</label>
                    <input type="number" wire:model.live.debounce.500ms="filterAmountMax"
                           placeholder="Tidak terbatas"
                           class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="col-span-2 flex items-end">
                    <button wire:click="clearFilters"
                            class="w-full px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reset Semua Filter
                    </button>
                </div>
            </div>

            {{-- Active Filter Tags --}}
            @php
                $activeTags = [];
                if ($filterType !== 'all') $activeTags[] = ['label' => 'Tipe: ' . ($filterType === 'in' ? 'Masuk' : 'Keluar'), 'action' => "\$set('filterType', 'all')"];
                if ($filterStatus !== 'all') $activeTags[] = ['label' => 'Status: ' . ($filterStatus === 'posted' ? 'Posted' : 'Draft'), 'action' => "\$set('filterStatus', 'all')"];
                if ($filterCounterpartType !== 'all') $activeTags[] = ['label' => 'Mitra: ' . ucfirst($filterCounterpartType), 'action' => "\$set('filterCounterpartType', 'all')"];
                if ($filterCostCategory !== 'all') $activeTags[] = ['label' => 'Kategori: ' . ucfirst($filterCostCategory), 'action' => "\$set('filterCostCategory', 'all')"];
                if ($filterCurrency !== 'all') $activeTags[] = ['label' => 'Mata Uang: ' . $filterCurrency, 'action' => "\$set('filterCurrency', 'all')"];
                if (!empty($filterAmountMin)) $activeTags[] = ['label' => 'Min: IDR ' . number_format((float)$filterAmountMin, 0, ',', '.'), 'action' => "\$set('filterAmountMin', '')"];
                if (!empty($filterAmountMax)) $activeTags[] = ['label' => 'Maks: IDR ' . number_format((float)$filterAmountMax, 0, ',', '.'), 'action' => "\$set('filterAmountMax', '')"];
                if ($filterNoJournal) $activeTags[] = ['label' => 'Tanpa Jurnal', 'action' => "\$set('filterNoJournal', false)"];
            @endphp
            @if(count($activeTags) > 0)
            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-200">
                <span class="text-xs text-gray-500 self-center font-medium">Filter aktif:</span>
                @foreach($activeTags as $tag)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                    {{ $tag['label'] }}
                    <button wire:click="{{ $tag['action'] }}" class="text-blue-500 hover:text-blue-800 font-bold text-sm leading-none ml-0.5">&times;</button>
                </span>
                @endforeach
            </div>
            @endif

        </div>
        @endif
        
        <div class="overflow-x-auto">
            <table wire:key="transactions-{{ $perPage }}-{{ $currentPage }}" class="w-full text-sm">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Tanggal</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Tipe</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Customer/Vendor</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Shipment</th>
                        <th class="px-4 py-2 text-left font-semibold text-gray-700">Keterangan</th>
                        <th class="px-4 py-2 text-right font-semibold text-gray-700">Jumlah</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-700">Bukti</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-2 text-center font-semibold text-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentTransactions as $trx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($trx['transaction_date'])->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if(($trx['type'] ?? 'out') === 'in')
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                💰 Terima
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                💸 Keluar
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(isset($trx['customer']) && $trx['customer'])
                            <div class="font-medium text-gray-900">{{ $trx['customer']['company_name'] ?? '-' }}</div>
                            <div class="text-xs text-green-600">Customer</div>
                            @elseif(isset($trx['vendor']) && $trx['vendor'])
                            <div class="font-medium text-gray-900">{{ $trx['vendor']['name'] ?? '-' }}</div>
                            <div class="text-xs text-orange-600">Vendor</div>
                            @else
                            <div class="font-medium text-gray-400">-</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(($trx['shipment'] ?? null))
                            <div class="text-xs font-mono text-blue-600">{{ ($trx['shipment'] ?? null)['awb_number'] }}</div>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(!empty($trx['description']))
                            <div class="text-sm text-gray-700 max-w-xs" title="{{ $trx['description'] }}">
                                {{ Str::limit($trx['description'], 60) }}
                            </div>
                            @else
                            <span class="text-gray-400 text-xs italic">- tidak ada keterangan -</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-semibold {{ ($trx['type'] ?? 'out') === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $trx['currency'] ?? 'IDR' }} {{ number_format($trx['amount'], 0, ',', '.') }}
                            </span>
                        </td>
                        {{-- Kolom Bukti Transaksi --}}
                        <td class="px-4 py-3 text-center">
                            @if(!empty($trx['proof_file']))
                            <button
                                wire:click="openProofModal({{ $trx['id'] }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition"
                                title="Lihat bukti transaksi">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                Ada
                            </button>
                            @else
                            <button
                                wire:click="openUploadProofModal({{ $trx['id'] }})"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition"
                                title="Upload bukti transaksi">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Upload
                            </button>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php $journalStatus = $trx['journal']['status'] ?? null; @endphp
                            @if(empty($trx['journal_id']) || $journalStatus === null)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-300" title="Tidak ada jurnal akuntansi — perlu rekonsiliasi">
                                ⚠ No Journal
                            </span>
                            @elseif($journalStatus === 'posted')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Posted
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                ⏳ Pending
                            </span>
                            @endif
                        </td>
                    {{-- Kolom Aksi - Semua yang punya akses halaman kasir --}}
                        <td class="px-4 py-3 text-center">
                            @if(auth()->user()->hasRole(['admin', 'director', 'manager', 'supervisor', 'cashier', 'staff_accounting']))
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="editTransaction({{ $trx['id'] }})" class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                <button wire:click="removeTransaction({{ $trx['id'] }})" class="p-1.5 text-red-600 hover:bg-red-100 rounded-lg transition" title="Hapus"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                            </div>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        {{-- Pagination Controls --}}
        </div>
        <div class="px-6 py-4 bg-white border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-700">
                    Menampilkan 
                    <span class="font-semibold">{{ (($currentPage - 1) * $perPage) + 1 }}</span>
                    sampai 
                    <span class="font-semibold">{{ min($currentPage * $perPage, $totalRecords) }}</span>
                    dari 
                    <span class="font-semibold text-blue-600">{{ number_format($totalRecords, 0, ',', '.') }}</span>
                    transaksi
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Per halaman:</label>
                    <select wire:model.live="perPage" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                @php $totalPages = ceil($totalRecords / $perPage); @endphp
                
                <button wire:click="previousPage" {{ $currentPage <= 1 ? 'disabled' : '' }} class="px-3 py-2 text-sm rounded-lg border {{ $currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                
                @if($totalPages <= 7)
                    @for($i = 1; $i <= $totalPages; $i++)
                        <button wire:click="goToPage({{ $i }})" class="px-3 py-2 text-sm font-medium rounded-lg transition {{ $currentPage == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">{{ $i }}</button>
                    @endfor
                @else
                    @if($currentPage <= 3)
                        @for($i = 1; $i <= 5; $i++)
                            <button wire:click="goToPage({{ $i }})" class="px-3 py-2 text-sm font-medium rounded-lg transition {{ $currentPage == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">{{ $i }}</button>
                        @endfor
                        <span class="px-2 text-gray-500">...</span>
                        <button wire:click="goToPage({{ $totalPages }})" class="px-3 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition">{{ $totalPages }}</button>
                    @elseif($currentPage >= $totalPages - 2)
                        <button wire:click="goToPage(1)" class="px-3 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition">1</button>
                        <span class="px-2 text-gray-500">...</span>
                        @for($i = $totalPages - 4; $i <= $totalPages; $i++)
                            <button wire:click="goToPage({{ $i }})" class="px-3 py-2 text-sm font-medium rounded-lg transition {{ $currentPage == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">{{ $i }}</button>
                        @endfor
                    @else
                        <button wire:click="goToPage(1)" class="px-3 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition">1</button>
                        <span class="px-2 text-gray-500">...</span>
                        @for($i = $currentPage - 1; $i <= $currentPage + 1; $i++)
                            <button wire:click="goToPage({{ $i }})" class="px-3 py-2 text-sm font-medium rounded-lg transition {{ $currentPage == $i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300' }}">{{ $i }}</button>
                        @endfor
                        <span class="px-2 text-gray-500">...</span>
                        <button wire:click="goToPage({{ $totalPages }})" class="px-3 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 transition">{{ $totalPages }}</button>
                    @endif
                @endif
                
                <button wire:click="nextPage" {{ $currentPage >= ceil($totalRecords / $perPage) ? 'disabled' : '' }} class="px-3 py-2 text-sm rounded-lg border {{ $currentPage >= ceil($totalRecords / $perPage) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>
    {{-- @endif --}}

{{-- Delete Confirmation Modal --}}
@if($showDeleteConfirm)
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Konfirmasi Hapus</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="items-center px-4 py-3 space-y-2">
                <button wire:click="executeDelete" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700">Ya, Hapus</button>
                <button wire:click="cancelDelete" class="px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200">Batal</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Proof Preview Modal --}}
@if($showProofModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-70 overflow-y-auto h-full w-full z-[200]" wire:click.self="closeProofModal">
    <div class="relative mx-auto mt-10 mb-10 w-full max-w-3xl bg-white rounded-xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b bg-gray-50">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h3 class="text-base font-semibold text-gray-800">Bukti Transaksi</h3>
            </div>
            <button wire:click="closeProofModal" class="p-1.5 hover:bg-gray-200 rounded-lg transition">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- File Preview --}}
        <div class="p-5">
            @if($proofIsImage)
                <div class="flex justify-center bg-gray-100 rounded-lg p-2 min-h-48">
                    <img src="{{ $proofUrl }}" alt="{{ $proofFilename }}" class="max-w-full max-h-[60vh] object-contain rounded shadow">
                </div>
            @elseif($proofIsPdf)
                <iframe src="{{ $proofUrl }}" class="w-full rounded-lg border" style="height:65vh;" title="{{ $proofFilename }}"></iframe>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-gray-500 gap-3">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm">Format file tidak dapat ditampilkan. Gunakan tombol Download.</p>
                </div>
            @endif

            <p class="mt-2 text-xs text-gray-500 text-center truncate">{{ $proofFilename }}</p>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-t bg-gray-50">
            <button
                wire:click="openUploadProofModal({{ $proofTransactionId }})"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-amber-50 text-amber-700 border border-amber-300 hover:bg-amber-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Ganti File
            </button>
            <div class="flex gap-2">
                <a href="{{ $proofUrl }}" download="{{ $proofFilename }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download
                </a>
                <button wire:click="closeProofModal" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Upload Proof Modal --}}
@if($showUploadProofModal)
<div class="fixed inset-0 bg-gray-900 bg-opacity-70 overflow-y-auto h-full w-full z-[200]" wire:click.self="closeUploadProofModal">
    <div class="relative mx-auto mt-20 mb-10 w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b bg-gray-50">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <h3 class="text-base font-semibold text-gray-800">Upload Bukti Transaksi</h3>
            </div>
            <button wire:click="closeUploadProofModal" class="p-1.5 hover:bg-gray-200 rounded-lg transition">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-5 space-y-4">
            {{-- File dropzone --}}
            <div
                x-data="{ dragging: false }"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                class="relative border-2 border-dashed rounded-xl p-8 text-center transition cursor-pointer"
                @click="$refs.fileInput.click()">
                <svg class="mx-auto w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p class="text-sm text-gray-600 font-medium">Klik atau seret file ke sini</p>
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, WEBP, PDF — maks. 5MB</p>
                <input
                    x-ref="fileInput"
                    type="file"
                    class="hidden"
                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                    wire:model="proofAttachment">
            </div>

            {{-- Live preview after upload --}}
            @if($proofAttachment)
                @php
                    $tmpExt = strtolower($proofAttachment->getClientOriginalExtension());
                    $tmpIsImg = in_array($tmpExt, ['jpg','jpeg','png','gif','webp']);
                @endphp
                <div class="rounded-lg overflow-hidden border bg-gray-50 p-2">
                    @if($tmpIsImg)
                        <img src="{{ $proofAttachment->temporaryUrl() }}" alt="Preview" class="max-w-full max-h-48 mx-auto rounded object-contain">
                    @else
                        <div class="flex items-center gap-3 p-3">
                            <svg class="w-8 h-8 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $proofAttachment->getClientOriginalName() }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($proofAttachment->getSize() / 1024, 0) }} KB</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Validation errors --}}
            @error('proofAttachment')
                <p class="text-sm text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $message }}
                </p>
            @enderror

            {{-- Upload progress indicator --}}
            <div wire:loading wire:target="proofAttachment" class="flex items-center gap-2 text-sm text-blue-600">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Memproses file...
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 px-5 py-4 border-t bg-gray-50">
            <button wire:click="closeUploadProofModal" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                Batal
            </button>
            <button
                wire:click="saveProof"
                wire:loading.attr="disabled"
                wire:target="saveProof,proofAttachment"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="saveProof">Simpan</span>
                <span wire:loading wire:target="saveProof">Menyimpan...</span>
            </button>
        </div>
    </div>
</div>
@endif
</div>