<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use App\Models\Account;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB; // <--- INI YANG TADI KURANG

class BalanceSheet extends Component
{
    public $end_date;

    public function mount()
    {
        $this->end_date = date('Y-m-d'); // Default Hari Ini
    }

    public function render()
    {
        // Helper function: Hitung saldo akhir akun per tanggal
        $getBalance = function ($accountId, $type) {
            $query = JournalItem::where('account_id', $accountId)
                ->whereHas('journal', function ($q) {
                    $q->where('transaction_date', '<=', $this->end_date);
                });

            $debit = $query->sum('debit');
            $credit = $query->sum('credit');
            $acc = Account::find($accountId);
            
            // Rumus Saldo Normal
            // Aset & Beban bertambah di Debit
            // Kewajiban, Modal, Pendapatan bertambah di Kredit
            if (in_array($type, ['aset', 'beban'])) {
                return $acc->opening_balance + ($debit - $credit);
            } else {
                return $acc->opening_balance + ($credit - $debit);
            }
        };

        // 1. ASET
        $assets = Account::whereIn('type', ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap'])->get()
            ->map(function ($acc) use ($getBalance) {
                $acc->balance = $getBalance($acc->id, 'aset');
                return $acc;
            })->filter(fn($acc) => $acc->balance != 0);

        // 2. KEWAJIBAN
        $liabilities = Account::whereIn('type', ['hutang_lancar', 'hutang_jangka_panjang'])->get()
            ->map(function ($acc) use ($getBalance) {
                $acc->balance = $getBalance($acc->id, 'kewajiban');
                return $acc;
            })->filter(fn($acc) => $acc->balance != 0);

        // 3. MODAL
        $equity = Account::where('type', 'modal')->get()
            ->map(function ($acc) use ($getBalance) {
                $acc->balance = $getBalance($acc->id, 'modal');
                return $acc;
            })->filter(fn($acc) => $acc->balance != 0);

        // --- HITUNG LABA TAHUN BERJALAN (Current Earnings) ---
        // Rumus: Total Pendapatan - Total Beban
        
        $totalRevenue = JournalItem::whereHas('account', fn($q) => $q->where('type', 'pendapatan'))
            ->whereHas('journal', fn($q) => $q->where('transaction_date', '<=', $this->end_date))
            ->sum(DB::raw('credit - debit'));
            
        $totalExpense = JournalItem::whereHas('account', fn($q) => $q->whereIn('type', ['beban_pokok', 'beban_operasional', 'beban_lain']))
            ->whereHas('journal', fn($q) => $q->where('transaction_date', '<=', $this->end_date))
            ->sum(DB::raw('debit - credit'));

        $currentEarnings = $totalRevenue - $totalExpense;

        // TOTALS
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $currentEarnings;

        return view('livewire.admin.accounting.balance-sheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'currentEarnings' => $currentEarnings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
        ])->layout('layouts.admin');
    }
}