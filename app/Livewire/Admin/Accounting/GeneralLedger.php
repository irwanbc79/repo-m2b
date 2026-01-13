<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use App\Models\Account;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;

class GeneralLedger extends Component
{
    public $account_id;
    public $start_date;
    public $end_date;

    public function mount()
    {
        // Default tanggal: Awal bulan ini s/d Hari ini
        $this->start_date = date('Y-m-01');
        $this->end_date = date('Y-m-d');
        
        // Default akun: Kas Besar (jika ada)
        $firstAccount = Account::orderBy('code')->first();
        $this->account_id = $firstAccount ? $firstAccount->id : null;
    }

    public function render()
    {
        $accounts = Account::orderBy('code')->get();
        $ledgerItems = [];
        $openingBalance = 0;
        $selectedAccount = null;
        $totalDebit = 0;
        $totalCredit = 0;

        if ($this->account_id) {
            $selectedAccount = Account::find($this->account_id);

            // 1. HITUNG SALDO AWAL (Transaksi sebelum start_date)
            // Logic: Saldo Awal Master + (Total Debit Sebelum tgl - Total Kredit Sebelum tgl)
            
            $prevDebit = JournalItem::where('account_id', $this->account_id)
                ->whereHas('journal', function($q) {
                    $q->where('transaction_date', '<', $this->start_date);
                })->sum('debit');

            $prevCredit = JournalItem::where('account_id', $this->account_id)
                ->whereHas('journal', function($q) {
                    $q->where('transaction_date', '<', $this->start_date);
                })->sum('credit');

            // Tentukan posisi saldo normal (Debit/Kredit)
            $isDebitNormal = in_array($selectedAccount->type, ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain']);

            if ($isDebitNormal) {
                $openingBalance = $selectedAccount->opening_balance + ($prevDebit - $prevCredit);
            } else {
                $openingBalance = $selectedAccount->opening_balance + ($prevCredit - $prevDebit);
            }

            // 2. AMBIL TRANSAKSI PERIODE INI
            $ledgerItems = JournalItem::with('journal')
                ->where('account_id', $this->account_id)
                ->whereHas('journal', function($q) {
                    $q->whereBetween('transaction_date', [$this->start_date, $this->end_date]);
                })
                ->get()
                ->sortBy(function($item) {
                    return $item->journal->transaction_date . $item->id;
                });

            $totalDebit = $ledgerItems->sum('debit');
            $totalCredit = $ledgerItems->sum('credit');
        }

        return view('livewire.admin.accounting.general-ledger', [
            'accounts' => $accounts,
            'ledgerItems' => $ledgerItems,
            'openingBalance' => $openingBalance,
            'selectedAccount' => $selectedAccount,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit
        ])->layout('layouts.admin');
    }
}