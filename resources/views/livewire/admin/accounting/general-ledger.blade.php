<div class="space-y-6">
    @section('header', 'General Ledger (Buku Besar)')

    <!-- FILTER SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Akun</label>
                <select wire:model.live="account_id" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-900 focus:border-blue-900">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" wire:model.live="start_date" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" wire:model.live="end_date" class="w-full border border-gray-300 rounded-lg p-2.5 text-sm">
            </div>
        </div>
    </div>

    <!-- REPORT CONTENT -->
    @if($selectedAccount)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-blue-900">{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</h3>
                <p class="text-xs text-gray-500">Periode: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <span class="block text-xs text-gray-500 uppercase">Saldo Akhir</span>
                @php
                    $isDebitNormal = in_array($selectedAccount->type, ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain']);
                    $endingBalance = $isDebitNormal ? ($openingBalance + $totalDebit - $totalCredit) : ($openingBalance + $totalCredit - $totalDebit);
                @endphp
                <span class="text-xl font-black {{ $endingBalance < 0 ? 'text-red-600' : 'text-green-600' }}">
                    Rp {{ number_format($endingBalance, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs border-b">
                    <tr>
                        <th class="px-6 py-3 w-32">Tanggal</th>
                        <th class="px-6 py-3 w-32">No. Jurnal</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3 text-right w-32">Debit</th>
                        <th class="px-6 py-3 text-right w-32">Kredit</th>
                        <th class="px-6 py-3 text-right w-40">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <!-- SALDO AWAL -->
                    <tr class="bg-yellow-50 font-bold">
                        <td class="px-6 py-3 text-center">-</td>
                        <td class="px-6 py-3 text-center">-</td>
                        <td class="px-6 py-3">Saldo Awal (Opening Balance)</td>
                        <td class="px-6 py-3 text-right">-</td>
                        <td class="px-6 py-3 text-right">-</td>
                        <td class="px-6 py-3 text-right">{{ number_format($openingBalance, 0, ',', '.') }}</td>
                    </tr>

                    @php $runningBalance = $openingBalance; @endphp

                    @forelse($ledgerItems as $item)
                        @php
                            if ($isDebitNormal) {
                                $runningBalance += $item->debit - $item->credit;
                            } else {
                                $runningBalance += $item->credit - $item->debit;
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 whitespace-nowrap">{{ $item->journal->transaction_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 font-mono text-blue-600 text-xs">{{ $item->journal->journal_number }}</td>
                            <td class="px-6 py-3">
                                <div class="font-medium text-gray-800">{{ $item->journal->description }}</div>
                                @if($item->journal->reference_no)
                                    <div class="text-xs text-gray-400">Ref: {{ $item->journal->reference_no }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-gray-700">
                                {{ $item->debit > 0 ? number_format($item->debit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-3 text-right font-mono text-gray-700">
                                {{ $item->credit > 0 ? number_format($item->credit, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-3 text-right font-bold text-gray-900">
                                {{ number_format($runningBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Tidak ada transaksi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 font-bold border-t text-gray-700">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right">Total Mutasi</td>
                        <td class="px-6 py-3 text-right text-blue-700">{{ number_format($totalDebit, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right text-blue-700">{{ number_format($totalCredit, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right">{{ number_format($runningBalance, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>