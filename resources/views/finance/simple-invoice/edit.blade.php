@extends('layouts.admin')

@section('content')
<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('finance.simple-invoice.index') }}" 
           class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali</span>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">✏️ Edit Invoice</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $invoice->invoice_number }}</p>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('finance.simple-invoice.update', $invoice->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            {{-- Customer Section --}}
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold mb-4">📋 Informasi Customer</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Customer *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $invoice->customer_name) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Invoice *</label>
                        <input type="date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Customer</label>
                    <textarea name="customer_address" rows="2"
                              class="w-full px-4 py-2 border rounded-lg">{{ old('customer_address', $invoice->customer_address) }}</textarea>
                </div>
            </div>

            {{-- Items Section --}}
            <div class="p-6 border-b">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">📦 Item Invoice</h3>
                    <button type="button" onclick="addItem()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        + Tambah Item
                    </button>
                </div>

                <div id="itemsContainer">
                    @foreach($invoice->items as $index => $item)
                    <div class="item-row bg-gray-50 p-4 rounded-lg mb-3">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-sm font-semibold text-gray-700">Item #{{ $index + 1 }}</span>
                            <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800 text-sm {{ $invoice->items->count() == 1 ? 'hidden' : '' }}">
                                🗑️ Hapus
                            </button>
                        </div>
                        <div class="grid grid-cols-12 gap-3">
                            <div class="col-span-6">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan *</label>
                                <input type="text" name="items[{{ $index }}][description]" value="{{ $item->description }}" required
                                       class="w-full px-3 py-2 border rounded-lg text-sm">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Qty *</label>
                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" required min="1"
                                       class="w-full px-3 py-2 border rounded-lg text-sm" onchange="calculateTotal()">
                            </div>
                            <div class="col-span-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Harga *</label>
                                <input type="number" name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}" required min="0"
                                       class="w-full px-3 py-2 border rounded-lg text-sm" onchange="calculateTotal()">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">TOTAL:</span>
                        <span id="totalDisplay" class="text-xl font-bold text-blue-600">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Bank Account Section --}}
            <div class="p-6 border-b bg-gray-50/50">
                <h3 class="text-lg font-semibold mb-4">🏦 Rekening Penerima (Tujuan Transfer)</h3>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Template Rekening</label>
                    <select id="bankTemplateSelect" class="w-full md:w-1/2 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" 
                                {{ old('bank_account_number', $invoice->bank_account_number) === $account->account_number ? 'selected' : '' }}>
                                {{ $account->account_holder }} ({{ $account->bank_name }} - {{ $account->account_number }})
                            </option>
                        @endforeach
                        <option value="custom" {{ old('bank_account_number', $invoice->bank_account_number) && !$bankAccounts->contains('account_number', old('bank_account_number', $invoice->bank_account_number)) ? 'selected' : '' }}>-- Input Kustom --</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Memilih template akan otomatis mengisi input di bawah.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Bank *</label>
                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $invoice->bank_name ?? 'PT BANK MANDIRI (Persero) Tbk') }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No. Rekening *</label>
                        <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $invoice->bank_account_number ?? '106-00-5598889-6') }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Atas Nama *</label>
                        <input type="text" name="bank_account_holder" id="bank_account_holder" value="{{ old('bank_account_holder', $invoice->bank_account_holder ?? 'PT. MORA MULTI BERKAH') }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="mt-4" id="saveBankAccountContainer" style="display: none;">
                    <label class="inline-flex items-center text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="save_bank_account" id="save_bank_account" value="1" {{ old('save_bank_account') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                        💾 Simpan rekening ini sebagai template untuk penggunaan berikutnya
                    </label>
                </div>
            </div>

            {{-- Notes --}}
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold mb-4">📝 Catatan</h3>
                <textarea name="notes" rows="3" class="w-full px-4 py-2 border rounded-lg">{{ old('notes', $invoice->notes) }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="p-6 bg-gray-50 flex justify-between">
                <a href="{{ route('finance.simple-invoice.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold">
                    ❌ Batal
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                    💾 Update Invoice
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let itemCount = {{ $invoice->items->count() }};

function addItem() {
    const container = document.getElementById('itemsContainer');
    const itemHtml = `
        <div class="item-row bg-gray-50 p-4 rounded-lg mb-3">
            <div class="flex justify-between items-start mb-3">
                <span class="text-sm font-semibold text-gray-700">Item #${itemCount + 1}</span>
                <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800 text-sm">🗑️ Hapus</button>
            </div>
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-6">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Keterangan *</label>
                    <input type="text" name="items[${itemCount}][description]" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Qty *</label>
                    <input type="number" name="items[${itemCount}][quantity]" required min="1" value="1" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="calculateTotal()">
                </div>
                <div class="col-span-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga *</label>
                    <input type="number" name="items[${itemCount}][unit_price]" required min="0" value="0" class="w-full px-3 py-2 border rounded-lg text-sm" onchange="calculateTotal()">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
    itemCount++;
    updateDeleteButtons();
}

function removeItem(btn) {
    btn.closest('.item-row').remove();
    updateDeleteButtons();
    calculateTotal();
}

function updateDeleteButtons() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((item, index) => {
        const deleteBtn = item.querySelector('button[onclick*="removeItem"]');
        if (items.length > 1) {
            deleteBtn.classList.remove('hidden');
        } else {
            deleteBtn.classList.add('hidden');
        }
        item.querySelector('.text-sm.font-semibold').textContent = `Item #${index + 1}`;
    });
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
        const price = parseFloat(row.querySelector('input[name*="[unit_price]"]').value) || 0;
        total += qty * price;
    });
    document.getElementById('totalDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

calculateTotal();

// Bank template select handler
document.addEventListener('DOMContentLoaded', function() {
    const bankTemplateSelect = document.getElementById('bankTemplateSelect');
    const bankNameInput = document.getElementById('bank_name');
    const bankAccNoInput = document.getElementById('bank_account_number');
    const bankHolderInput = document.getElementById('bank_account_holder');
    const saveBankAccountContainer = document.getElementById('saveBankAccountContainer');
    const saveBankAccountCheckbox = document.getElementById('save_bank_account');
    
    if (bankTemplateSelect) {
        const templates = {
            @foreach($bankAccounts as $account)
            "{{ $account->id }}": {
                bank_name: "{{ $account->bank_name }}",
                bank_account_number: "{{ $account->account_number }}",
                bank_account_holder: "{{ $account->account_holder }}"
            },
            @endforeach
        };

        function toggleSaveCheckbox() {
            if (bankTemplateSelect.value === 'custom') {
                saveBankAccountContainer.style.display = 'block';
            } else {
                saveBankAccountContainer.style.display = 'none';
                saveBankAccountCheckbox.checked = false;
            }
        }

        bankTemplateSelect.addEventListener('change', function() {
            const val = this.value;
            if (templates[val]) {
                bankNameInput.value = templates[val].bank_name;
                bankAccNoInput.value = templates[val].bank_account_number;
                bankHolderInput.value = templates[val].bank_account_holder;
            }
            toggleSaveCheckbox();
        });

        // Run on page load
        toggleSaveCheckbox();
    }
});
</script>
@endsection
