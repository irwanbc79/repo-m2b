<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use App\Models\Account;
use App\Models\JournalItem;

class TrialBalance extends Component
{
    public $start_date;
    public $end_date;

    public function mount()
    {
        $this->start_date = date('Y-m-01'); // Awal bulan ini
        $this->end_date = date('Y-m-d');   // Hari ini
    }

    public function render()
    {
        $accounts = Account::orderBy('code')->get();
        $data = [];
        
        $totalOpening = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $totalEnding = 0;

        foreach ($accounts as $acc) {
            // 1. Hitung Mutasi SEBELUM tanggal mulai (Untuk Saldo Awal)
            $prevDebit = JournalItem::where('account_id', $acc->id)
                ->whereHas('journal', function($q) {
                    $q->where('transaction_date', '<', $this->start_date);
                })->sum('debit');

            $prevCredit = JournalItem::where('account_id', $acc->id)
                ->whereHas('journal', function($q) {
                    $q->where('transaction_date', '<', $this->start_date);
                })->sum('credit');

            // Tentukan Saldo Normal (Debit/Kredit)
            $isDebitNormal = in_array($acc->type, ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain']);

            if ($isDebitNormal) {
                $opening = $acc->opening_balance + ($prevDebit - $prevCredit);
            } else {
                $opening = $acc->opening_balance + ($prevCredit - $prevDebit);
            }

            // 2. Hitung Mutasi PERIODE INI
            $currDebit = JournalItem::where('account_id', $acc->id)
                ->whereHas('journal', function($q) {
                    $q->whereBetween('transaction_date', [$this->start_date, $this->end_date]);
                })->sum('debit');

            $currCredit = JournalItem::where('account_id', $acc->id)
                ->whereHas('journal', function($q) {
                    $q->whereBetween('transaction_date', [$this->start_date, $this->end_date]);
                })->sum('credit');

            // 3. Hitung Saldo Akhir
            if ($isDebitNormal) {
                $ending = $opening + $currDebit - $currCredit;
            } else {
                $ending = $opening + $currCredit - $currDebit;
            }

            // Hanya masukkan ke list jika ada saldo atau mutasi (biar tabel tidak kepanjangan)
            if ($opening != 0 || $currDebit != 0 || $currCredit != 0) {
                $data[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'type' => $acc->type,
                    'opening' => $opening,
                    'debit' => $currDebit,
                    'credit' => $currCredit,
                    'ending' => $ending,
                    'is_debit_normal' => $isDebitNormal
                ];

                // Hitung Grand Total (Hanya Debit & Kredit Mutasi yang wajib balance)
                $totalDebit += $currDebit;
                $totalCredit += $currCredit;
            }
        }

        return view('livewire.admin.accounting.trial-balance', [
            'reportData' => $data,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit
        ])->layout('layouts.admin');
    }
}