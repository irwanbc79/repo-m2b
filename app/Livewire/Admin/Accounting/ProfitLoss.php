<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use App\Models\Account;
use App\Models\JournalItem;

class ProfitLoss extends Component
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
        // Helper function untuk hitung saldo per akun dalam periode tertentu
        $getBalance = function ($accountId, $type) {
            $query = JournalItem::where('account_id', $accountId)
                ->whereHas('journal', function ($q) {
                    $q->whereBetween('transaction_date', [$this->start_date, $this->end_date]);
                });

            $debit = $query->sum('debit');
            $credit = $query->sum('credit');

            // Jika Pendapatan: Kredit menambah, Debit mengurangi
            // Jika Beban: Debit menambah, Kredit mengurangi
            if ($type == 'pendapatan') {
                return $credit - $debit;
            } else {
                return $debit - $credit;
            }
        };

        // 1. PENDAPATAN
        $revenues = Account::where('type', 'pendapatan')->get()->map(function ($acc) use ($getBalance) {
            $acc->net_movement = $getBalance($acc->id, 'pendapatan');
            return $acc;
        })->filter(fn($acc) => $acc->net_movement != 0); // Sembunyikan yang 0

        // 2. BEBAN POKOK (HPP)
        $cogs = Account::where('type', 'beban_pokok')->get()->map(function ($acc) use ($getBalance) {
            $acc->net_movement = $getBalance($acc->id, 'beban');
            return $acc;
        })->filter(fn($acc) => $acc->net_movement != 0);

        // 3. BEBAN OPERASIONAL
        $expenses = Account::whereIn('type', ['beban_operasional', 'beban_lain'])->get()->map(function ($acc) use ($getBalance) {
            $acc->net_movement = $getBalance($acc->id, 'beban');
            return $acc;
        })->filter(fn($acc) => $acc->net_movement != 0);

        // HITUNG TOTAL
        $totalRevenue = $revenues->sum('net_movement');
        $totalCOGS = $cogs->sum('net_movement');
        $grossProfit = $totalRevenue - $totalCOGS;
        $totalExpense = $expenses->sum('net_movement');
        $netProfit = $grossProfit - $totalExpense;

        return view('livewire.admin.accounting.profit-loss', [
            'revenues' => $revenues,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalCOGS' => $totalCOGS,
            'grossProfit' => $grossProfit,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
        ])->layout('layouts.admin');
    }
}