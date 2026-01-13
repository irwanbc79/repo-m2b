<div class="space-y-6">
    @section('header', 'Journal Entry / Jurnal Umum')

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 shadow-sm">{{ session('message') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div class="relative w-64">
                <input wire:model.live="search" type="text" placeholder="Cari No Jurnal / Ket..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <button wire:click="create" class="bg-m2b-primary text-white px-4 py-2 rounded-lg font-bold shadow-sm hover:bg-blue-900 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Buat Jurnal
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 font-bold text-gray-600 uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-3">No Jurnal</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3 text-center">Total</th>
                        <th class="px-6 py-3 text-center">Oleh</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($journals as $j)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-m2b-primary">{{ $j->journal_number }}</td>
                        <td class="px-6 py-4">{{ $j->transaction_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $j->description }}</div>
                            <div class="text-xs text-gray-500">Ref: {{ $j->reference_no ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-center font-mono text-gray-800">
                            Rp {{ number_format($j->items->sum('debit'), 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                            {{ $j->creator->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="delete({{ $j->id }})" wire:confirm="Hapus Jurnal ini? Data akan hilang permanen." class="text-red-500 border border-red-200 px-2 py-1 rounded hover:bg-red-50 text-xs font-bold">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="p-8 text-center text-gray-500 italic">Belum ada transaksi jurnal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t bg-gray-50">{{ $journals->links() }}</div>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow-2xl flex flex-col max-h-[95vh]">
            <div class="p-5 border-b flex justify-between items-center bg-gray-50 rounded-t-xl">
                <h3 class="font-bold text-lg text-m2b-primary">Input Jurnal Umum</h3>
                <button wire:click="closeModal" class="text-2xl text-gray-400 hover:text-red-500">&times;</button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div>
                        <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Tanggal Transaksi</label>
                        <input type="date" wire:model="transaction_date" class="w-full border border-gray-300 rounded p-2 text-sm focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-blue-800 uppercase mb-1">No. Referensi</label>
                        <input type="text" wire:model="reference_no" class="w-full border border-gray-300 rounded p-2 text-sm focus:ring-blue-500" placeholder="Contoh: INV-001">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Keterangan (Memo)</label>
                        <input type="text" wire:model="description" class="w-full border border-gray-300 rounded p-2 text-sm focus:ring-blue-500" placeholder="Contoh: Bayar Listrik Bulan Juni">
                    </div>
                </div>

                @error('balance')
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center font-bold shadow-sm animate-pulse">
                    {{ $message }}
                </div>
                @enderror

                <div class="border rounded-lg overflow-hidden border-gray-300">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 font-bold text-gray-700 border-b border-gray-300">
                            <tr>
                                <th class="p-3 text-left w-5/12">Akun Perkiraan (COA)</th>
                                <th class="p-3 text-right w-3/12">Debit (Rp)</th>
                                <th class="p-3 text-right w-3/12">Kredit (Rp)</th>
                                <th class="p-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $index => $item)
                            <tr wire:key="item-{{ $index }}">
                                <td class="p-2">
                                    <select wire:model="items.{{ $index }}.account_id" class="w-full border border-gray-300 rounded p-2 text-sm focus:ring-blue-500">
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="number" wire:model.blur="items.{{ $index }}.debit" wire:change="calculateTotal" class="w-full border border-gray-300 rounded p-2 text-right font-mono focus:ring-blue-500" placeholder="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" wire:model.blur="items.{{ $index }}.credit" wire:change="calculateTotal" class="w-full border border-gray-300 rounded p-2 text-right font-mono focus:ring-blue-500" placeholder="0">
                                </td>
                                <td class="p-2 text-center">
                                    <button wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold border-t border-gray-300">
                            <tr>
                                <td class="p-3">
                                    <button wire:click="addItem" class="text-blue-600 text-xs font-bold hover:underline flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Tambah Baris
                                    </button>
                                </td>
                                <td class="p-3 text-right font-mono {{ $totalDebit != $totalCredit ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($totalDebit) }}
                                </td>
                                <td class="p-3 text-right font-mono {{ $totalDebit != $totalCredit ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($totalCredit) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="flex justify-between items-center text-xs">
                    <div class="text-gray-500 italic">* Sistem otomatis mengecek keseimbangan Debit & Kredit.</div>
                    <div class="font-bold {{ $totalDebit != $totalCredit ? 'text-red-600' : 'text-green-600' }}">
                        Selisih: Rp {{ number_format($totalDebit - $totalCredit) }}
                    </div>
                </div>
            </div>

            <div class="p-5 border-t bg-gray-50 flex justify-end gap-3 rounded-b-xl">
                <button wire:click="closeModal" class="px-5 py-2.5 border border-gray-300 rounded-lg bg-white text-gray-700 font-medium hover:bg-gray-100 transition">Batal</button>
                <button wire:click="save" class="px-6 py-2.5 bg-m2b-primary text-white rounded-lg font-bold hover:bg-blue-900 transition shadow-md disabled:opacity-50" 
                        @if($totalDebit != $totalCredit || $totalDebit == 0) disabled @endif>
                    Simpan Jurnal
                </button>
            </div>
        </div>
    </div>
    @endif
</div>