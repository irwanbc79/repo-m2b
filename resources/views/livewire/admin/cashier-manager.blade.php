<div class="space-y-6">
    @section('header', 'Cashier – Kas Masuk & Keluar')

    {{-- FLASH MESSAGE --}}
    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow">
            {{ session('success') }}
        </div>
    @endif

    {{-- =======================
        FORM KAS
    ======================== --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 p-6">

        {{-- TAB --}}
        <div class="flex gap-2 mb-6">
            <button wire:click="$set('mode','in')"
                class="px-4 py-2 rounded font-bold text-sm
                {{ $mode === 'in' ? 'bg-blue-900 text-white' : 'bg-gray-100 text-gray-600' }}">
                Dana Masuk
            </button>
            <button wire:click="$set('mode','out')"
                class="px-4 py-2 rounded font-bold text-sm
                {{ $mode === 'out' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                Dana Keluar
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- TANGGAL --}}
            <div>
                <label class="block text-sm font-bold mb-1">Tanggal Transaksi</label>
                <input type="date" wire:model="transaction_date"
                    class="w-full border-gray-300 rounded-lg">
                @error('transaction_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- NOMINAL --}}
            <div>
                <label class="block text-sm font-bold mb-1">Nominal</label>
                <input type="number" wire:model="amount" id="amountInput"
                    class="w-full border-gray-300 rounded-lg text-right font-mono">
                @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                <div class="text-xs text-gray-600 mt-1 italic" id="terbilangText">
                    {{ $this->amountTerbilang }}
                </div>
            </div>

            {{-- AKUN KAS / BANK --}}
            <div>
                <label class="block text-sm font-bold mb-1">Akun Kas / Bank</label>
                <select wire:model="cash_account_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">-- Pilih Akun --</option>
                    @foreach($cashAccounts as $acc)
                        <option value="{{ $acc->id }}">
                            {{ $acc->code }} – {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @error('cash_account_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- AKUN LAWAN --}}
            <div>
                <label class="block text-sm font-bold mb-1">
                    {{ $mode === 'in' ? 'Akun Pendapatan / Piutang' : 'Akun Beban / Aset' }}
                </label>
                <select wire:model="counter_account_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">-- Pilih Akun --</option>
                    @foreach(\App\Models\Account::orderBy('code')->get() as $acc)
                        <option value="{{ $acc->id }}">
                            {{ $acc->code }} – {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @error('counter_account_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- INVOICE (HANYA DANA MASUK) --}}
            @if($mode === 'in')
            <div class="md:col-span-2">
                <label class="block text-sm font-bold mb-1">Invoice (Opsional)</label>
                <select wire:model="invoice_id" class="w-full border-gray-300 rounded-lg">
                    <option value="">-- Pilih Invoice --</option>
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}">
                            {{ $inv->invoice_number }} – {{ number_format($inv->grand_total) }}
                        </option>
                    @endforeach
                </select>
                @error('invoice_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            @endif

            {{-- DESKRIPSI --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-bold mb-1">Keterangan</label>
                <textarea wire:model="description"
                    class="w-full border-gray-300 rounded-lg"></textarea>
            </div>

            {{-- BUKTI --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-bold mb-1">Bukti Transaksi (Opsional)</label>
                <input type="file" wire:model="proof" class="w-full text-sm">
                @error('proof') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- PENANDA TANGAN (UI ONLY, UNTUK PRINT) --}}
            <div class="md:col-span-2 border-t pt-4">
                <h4 class="font-bold mb-2">Penanda Tangan (Untuk Cetak)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input id="sign_dibuat" placeholder="Dibuat oleh"
                        class="border rounded p-2 text-sm">
                    <input id="sign_diperiksa" placeholder="Diperiksa oleh"
                        class="border rounded p-2 text-sm">
                    <input id="sign_disetujui" placeholder="Disetujui oleh"
                        class="border rounded p-2 text-sm">
                </div>
            </div>
        </div>

        {{-- ACTION --}}
        <div class="mt-6 flex justify-end">
            <button
                wire:click="save"
                onclick="injectSignatories()"
                class="{{ $mode === 'in' ? 'bg-blue-900' : 'bg-red-600' }}
                       text-white px-6 py-2 rounded-lg font-bold">
                Simpan {{ $mode === 'in' ? 'Dana Masuk' : 'Dana Keluar' }}
            </button>
        </div>
    </div>

    {{-- =======================
        RIWAYAT KAS
    ======================== --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 p-6">
        <h3 class="font-bold text-lg mb-4">Riwayat Kas</h3>

        <div class="overflow-x-auto">
            <table class="w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2">Tanggal</th>
                        <th class="border p-2">Jenis</th>
                        <th class="border p-2 text-right">Nominal</th>
                        <th class="border p-2">Akun Kas</th>
                        <th class="border p-2">Akun Lawan</th>
                        <th class="border p-2">Keterangan</th>
                        <th class="border p-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->cashHistories as $row)
                        <tr>
                            <td class="border p-2">{{ $row->transaction_date }}</td>
                            <td class="border p-2 font-bold {{ $row->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                {{ strtoupper($row->type) }}
                            </td>
                            <td class="border p-2 text-right font-mono">
                                {{ number_format($row->amount) }}
                            </td>
                            <td class="border p-2">{{ $row->account->name ?? '-' }}</td>
                            <td class="border p-2">{{ $row->counterAccount->name ?? '-' }}</td>
                            <td class="border p-2">{{ $row->description }}</td>
                            <td class="border p-2 text-center">
                                <a href="{{ route('admin.cashier.print', $row->id) }}"
                                   target="_blank"
                                   class="text-blue-600 font-bold">
                                    Print
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-4 text-gray-500">
                                Belum ada transaksi
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- =======================
    SCRIPT
======================= --}}
<script>
function injectSignatories() {
    @this.set('signatories', {
        dibuat_oleh: document.getElementById('sign_dibuat').value,
        diperiksa_oleh: document.getElementById('sign_diperiksa').value,
        disetujui_oleh: document.getElementById('sign_disetujui').value
    });
}
</script>
