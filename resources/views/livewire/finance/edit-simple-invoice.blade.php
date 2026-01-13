<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900">✏️ Edit Invoice</h1>
            <p class="text-gray-600 mt-1">Update invoice details</p>
        </div>

        @if (session()->has('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        <form wire:submit.prevent="update">
            <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Customer *</label>
                        <input type="text" wire:model="customer_name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('customer_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Customer</label>
                        <textarea wire:model="customer_address" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Invoice *</label>
                    <input type="date" wire:model="invoice_date"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('invoice_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Line Items *</label>
                    <div class="border border-gray-300 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700">Keterangan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 w-24">Qty</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 w-40">Harga (Rp)</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 w-40">Jumlah (Rp)</th>
                                    <th class="px-4 py-3 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="text" wire:model="items.{{ $index }}.description"
                                               class="w-full px-2 py-1 border border-gray-300 rounded">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" wire:model.lazy="items.{{ $index }}.quantity"
                                               class="w-full px-2 py-1 border border-gray-300 rounded" min="1">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" wire:model.lazy="items.{{ $index }}.unit_price"
                                               class="w-full px-2 py-1 border border-gray-300 rounded" step="1" min="0">
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold">
                                            Rp {{ number_format($item['amount'], 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $index }})"
                                                class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <button type="button" wire:click="addItem"
                            class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        + Tambah Item
                    </button>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-lg font-semibold text-gray-700">TOTAL:</span>
                        <span class="text-2xl font-bold text-blue-600">
                            Rp {{ number_format($this->subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 italic">
                        Terbilang: {{ $this->terbilang }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                    <textarea wire:model="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex justify-between pt-4 border-t">
                    <a href="{{ route('finance.simple-invoice.index') }}"
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        ← Kembali
                    </a>
                    <button type="submit"
                            class="px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        💾 Update Invoice
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
