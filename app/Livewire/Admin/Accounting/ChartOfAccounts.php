<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Account;

class ChartOfAccounts extends Component
{
    use WithPagination;

    public $search = '';
    public $type_filter = '';
    
    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null;

    // Form Data
    public $code, $name, $type, $opening_balance = 0;

    // Daftar Tipe Akun (Standar Indonesia)
    public $accountTypes = [
        'kas_bank' => 'Kas & Bank',
        'piutang' => 'Piutang Usaha',
        'persediaan' => 'Persediaan',
        'aset_lancar_lain' => 'Aset Lancar Lainnya',
        'aset_tetap' => 'Aset Tetap',
        'hutang_lancar' => 'Hutang Lancar',
        'hutang_jangka_panjang' => 'Hutang Jangka Panjang',
        'modal' => 'Ekuitas / Modal',
        'pendapatan' => 'Pendapatan',
        'beban_pokok' => 'Beban Pokok Penjualan (HPP)',
        'beban_operasional' => 'Beban Operasional',
        'beban_lain' => 'Beban Lain-lain',
    ];

    public function mount()
    {
        abort_unless(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'), 403);
    }

    public function updatingSearch() { $this->resetPage(); }

    public function getStats()
    {
        $allAccounts = Account::query()
            ->select('accounts.id', 'accounts.type', 'accounts.opening_balance')
            ->selectRaw('(SELECT COALESCE(SUM(debit), 0) FROM journal_items WHERE journal_items.account_id = accounts.id) as total_debit')
            ->selectRaw('(SELECT COALESCE(SUM(credit), 0) FROM journal_items WHERE journal_items.account_id = accounts.id) as total_credit')
            ->get();

        $calcSum = function ($types) use ($allAccounts) {
            if (!is_array($types)) {
                $types = [$types];
            }
            return $allAccounts->whereIn('type', $types)->sum(fn($a) => $a->calculated_balance);
        };

        return [
            'total_accounts' => $allAccounts->count(),
            'kas_bank' => $calcSum('kas_bank'),
            'piutang' => $calcSum('piutang'),
            'hutang' => $calcSum(['hutang_lancar', 'hutang_jangka_panjang']),
            'pendapatan' => $calcSum('pendapatan'),
            'beban' => $calcSum(['beban_operasional', 'beban_pokok', 'beban_lain']),
            'modal' => $calcSum('modal'),
        ];
    }

    public function syncBalances()
    {
        abort_unless(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'), 403);

        $accounts = Account::all();
        foreach ($accounts as $acc) {
            $acc->recalculateBalance();
        }

        session()->flash('message', 'Semua saldo akun di Bagan Akun (COA) berhasil disinkronkan dengan Buku Besar (General Ledger).');
    }

    public function render()
    {
        $accounts = Account::query()
            ->select('accounts.*')
            ->selectRaw('(SELECT COALESCE(SUM(debit), 0) FROM journal_items WHERE journal_items.account_id = accounts.id) as total_debit')
            ->selectRaw('(SELECT COALESCE(SUM(credit), 0) FROM journal_items WHERE journal_items.account_id = accounts.id) as total_credit')
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('code', 'like', '%'.$this->search.'%');
            })
            ->when($this->type_filter, function($q) {
                $q->where('type', $this->type_filter);
            })
            ->orderBy('code')
            ->paginate(15);

        $stats = $this->getStats();

        return view('livewire.admin.accounting.chart-of-accounts', [
            'accounts' => $accounts,
            'stats' => $stats
        ])->layout('layouts.admin');
    }

    public function create()
    {
        $this->resetInput();
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $acc = Account::find($id);
        if ($acc) {
            $this->editingId = $id;
            $this->code = $acc->code;
            $this->name = $acc->name;
            $this->type = $acc->type;
            $this->opening_balance = $acc->opening_balance;
            
            $this->isEditing = true;
            $this->isModalOpen = true;
        }
    }

    public function save()
    {
        abort_unless(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required',
            'opening_balance' => 'numeric',
        ];

        // Validasi unik untuk Kode Akun
        if ($this->isEditing) {
            $rules['code'] = 'required|unique:accounts,code,' . $this->editingId;
        } else {
            $rules['code'] = 'required|unique:accounts,code';
        }

        $this->validate($rules);

        if ($this->isEditing) {
            $acc = Account::find($this->editingId);

            $acc->update([
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'opening_balance' => $this->opening_balance,
            ]);

            // Selalu rekalkulasi saldo berjalan berbasis opening_balance + akumulasi jurnal
            $acc->recalculateBalance();

            session()->flash('message', 'Akun berhasil diperbarui dan saldo disinkronkan.');
        } else {
            $acc = Account::create([
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'opening_balance' => $this->opening_balance,
                'current_balance' => $this->opening_balance,
            ]);

            $acc->recalculateBalance();

            session()->flash('message', 'Akun baru berhasil dibuat.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        abort_unless(auth()->user()->hasPermission('cashier.view') || auth()->user()->hasPermission('accounting.view'), 403);

        $acc = Account::find($id);
        if (!$acc) {
            return;
        }

        if ($acc->journalItems()->exists()) {
            session()->flash('error', 'Akun tidak bisa dihapus karena sudah memiliki riwayat jurnal. Biarkan akun ini tetap ada agar laporan (Ledger, Trial Balance, Neraca) tidak kehilangan data historis.');
            return;
        }

        $acc->delete();
        session()->flash('message', 'Akun dihapus.');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->code = ''; $this->name = ''; $this->type = ''; $this->opening_balance = 0;
        $this->editingId = null;
    }
}