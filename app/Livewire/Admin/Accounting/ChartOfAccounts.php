<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Account;
use App\Models\JournalItem;

class ChartOfAccounts extends Component
{
    use WithPagination;

    public $search = '';
    public $type_filter = '';
    
    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null;

    // State Modal Lihat Jurnal (Account Ledger Inspection)
    public $isLedgerModalOpen = false;
    public $ledgerAccountId = null;
    public $ledgerStartDate = '';
    public $ledgerEndDate = '';
    public $ledgerPeriodPreset = 'all'; // 'all', 'this_month', 'this_year', 'custom'
    public $ledgerSearch = '';

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

    public function canAccess(): bool
    {
        $user = auth()->user();
        return $user && (
            $user->isAdminLevel() ||
            $user->hasRole(['super_admin', 'director', 'admin', 'manager', 'staff_accounting', 'finance', 'auditor', 'konsultan_pajak']) ||
            $user->hasPermission('accounting.view') ||
            $user->hasPermission('accounting.input')
        );
    }

    public function canManage(): bool
    {
        $user = auth()->user();
        return $user && (
            $user->isAdminLevel() ||
            $user->hasRole(['super_admin', 'director', 'admin', 'staff_accounting', 'finance']) ||
            $user->hasPermission('accounting.input')
        );
    }

    public function mount()
    {
        abort_unless($this->canAccess(), 403, 'Anda tidak memiliki akses ke bagan akun.');
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

    /**
     * Aktifkan / nonaktifkan akun. Akun nonaktif hilang dari daftar pilihan
     * saat input jurnal, tapi tetap muncul di semua laporan.
     */
    public function toggleAktif($id)
    {
        abort_unless($this->canManage(), 403, 'Anda tidak memiliki hak untuk mengubah status akun.');

        $acc = Account::find($id);
        if (! $acc) {
            return;
        }

        $acc->update(['is_active' => ! $acc->is_active]);

        \App\Models\ActivityLog::record(
            'Accounting',
            $acc->is_active ? 'ACTIVATE_COA' : 'DEACTIVATE_COA',
            $acc->code,
            ($acc->is_active ? 'Aktifkan' : 'Nonaktifkan') . " akun {$acc->code} - {$acc->name}"
        );

        session()->flash('message', $acc->is_active
            ? "Akun {$acc->code} diaktifkan kembali."
            : "Akun {$acc->code} dinonaktifkan. Riwayat jurnalnya tetap utuh dan masih muncul di laporan.");
    }

    public function syncBalances()
    {
        abort_unless($this->canManage(), 403, 'Anda tidak memiliki hak untuk sinkronisasi saldo akun.');

        $accounts = Account::all();
        foreach ($accounts as $acc) {
            $acc->recalculateBalance();
        }

        \App\Models\ActivityLog::record('Accounting', 'SYNC_COA_BALANCES', 'COA-ALL', "Sinkronisasi saldo seluruh bagan akun dengan buku besar.");

        session()->flash('message', 'Semua saldo akun di Bagan Akun (COA) berhasil disinkronkan dengan Buku Besar (General Ledger).');
    }

    public function openLedgerModal($accountId, $preset = 'all')
    {
        $this->ledgerAccountId = $accountId;
        $this->setLedgerPreset($preset);
        $this->ledgerSearch = '';
        $this->isLedgerModalOpen = true;
    }

    public function closeLedgerModal()
    {
        $this->isLedgerModalOpen = false;
        $this->ledgerAccountId = null;
        $this->ledgerSearch = '';
    }

    public function setLedgerPreset($preset)
    {
        $this->ledgerPeriodPreset = $preset;

        if ($preset === 'this_month') {
            $this->ledgerStartDate = date('Y-m-01');
            $this->ledgerEndDate = date('Y-m-d');
        } elseif ($preset === 'this_year') {
            $this->ledgerStartDate = date('Y-01-01');
            $this->ledgerEndDate = date('Y-m-d');
        } elseif ($preset === 'all') {
            $this->ledgerStartDate = '';
            $this->ledgerEndDate = '';
        }
    }

    public function getLedgerData()
    {
        if (! $this->ledgerAccountId) {
            return null;
        }

        $account = Account::find($this->ledgerAccountId);
        if (! $account) {
            return null;
        }

        $isDebitNormal = $account->isDebitNormal();
        $openingBalance = 0;

        // 1. Hitung Saldo Awal jika start_date ditentukan
        if (! empty($this->ledgerStartDate)) {
            $prevDebit = (float) JournalItem::where('account_id', $account->id)
                ->whereHas('journal', function ($q) {
                    $q->where('transaction_date', '<', $this->ledgerStartDate);
                })->sum('debit');

            $prevCredit = (float) JournalItem::where('account_id', $account->id)
                ->whereHas('journal', function ($q) {
                    $q->where('transaction_date', '<', $this->ledgerStartDate);
                })->sum('credit');

            $openingBalance = (float) $account->opening_balance + ($isDebitNormal ? ($prevDebit - $prevCredit) : ($prevCredit - $prevDebit));
        } else {
            $openingBalance = (float) ($account->opening_balance ?? 0);
        }

        // 2. Ambil mutasi transaksi periode ini
        $query = JournalItem::with(['journal.creator'])
            ->where('account_id', $account->id)
            ->whereHas('journal', function ($q) {
                if (! empty($this->ledgerStartDate) && ! empty($this->ledgerEndDate)) {
                    $q->whereBetween('transaction_date', [$this->ledgerStartDate, $this->ledgerEndDate]);
                } elseif (! empty($this->ledgerStartDate)) {
                    $q->where('transaction_date', '>=', $this->ledgerStartDate);
                } elseif (! empty($this->ledgerEndDate)) {
                    $q->where('transaction_date', '<=', $this->ledgerEndDate);
                }
            });

        if (! empty($this->ledgerSearch)) {
            $searchTerm = '%' . trim($this->ledgerSearch) . '%';
            $query->where(function ($sub) use ($searchTerm) {
                $sub->where('description', 'like', $searchTerm)
                    ->orWhere('note', 'like', $searchTerm)
                    ->orWhereHas('journal', function ($j) use ($searchTerm) {
                        $j->where('journal_number', 'like', $searchTerm)
                          ->orWhere('reference_no', 'like', $searchTerm)
                          ->orWhere('description', 'like', $searchTerm);
                    });
            });
        }

        $rawItems = $query->get()->sortBy(function ($item) {
            $date = $item->journal?->transaction_date ? $item->journal->transaction_date->format('Y-m-d') : '0000-00-00';
            return $date . '_' . str_pad((string) $item->journal_id, 8, '0', STR_PAD_LEFT) . '_' . str_pad((string) $item->id, 8, '0', STR_PAD_LEFT);
        });

        $runningBalance = $openingBalance;
        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $hasNegativeBalance = ($runningBalance < 0);

        foreach ($rawItems as $item) {
            $debit = (float) ($item->debit ?? 0);
            $credit = (float) ($item->credit ?? 0);
            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($isDebitNormal) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $isNegative = ($runningBalance < 0);
            if ($isNegative) {
                $hasNegativeBalance = true;
            }

            $description = $item->description ?: ($item->journal?->description ?? '-');

            $rows[] = [
                'id' => $item->id,
                'date' => $item->journal?->transaction_date ? $item->journal->transaction_date->format('d/m/Y') : '-',
                'raw_date' => $item->journal?->transaction_date ? $item->journal->transaction_date->format('Y-m-d') : null,
                'journal_id' => $item->journal_id,
                'journal_number' => $item->journal?->journal_number ?? ('#' . $item->journal_id),
                'reference_no' => $item->journal?->reference_no ?? '-',
                'description' => $description,
                'note' => $item->note ?? null,
                'creator_name' => $item->journal?->creator?->name ?? 'Sistem',
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'is_negative' => $isNegative,
            ];
        }

        return [
            'account' => $account,
            'isDebitNormal' => $isDebitNormal,
            'openingBalance' => $openingBalance,
            'rows' => $rows,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'closingBalance' => $runningBalance,
            'hasNegativeBalance' => $hasNegativeBalance,
            'count' => count($rows),
        ];
    }

    public function render()
    {
        abort_unless($this->canAccess(), 403, 'Anda tidak memiliki akses ke bagan akun.');

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
        $ledgerData = $this->isLedgerModalOpen ? $this->getLedgerData() : null;

        return view('livewire.admin.accounting.chart-of-accounts', [
            'accounts' => $accounts,
            'stats' => $stats,
            'ledgerData' => $ledgerData,
        ])->layout('layouts.admin');
    }

    public function create()
    {
        abort_unless($this->canManage(), 403, 'Anda tidak memiliki hak untuk menambah bagan akun.');
        $this->resetInput();
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        abort_unless($this->canManage(), 403, 'Anda tidak memiliki hak untuk mengubah bagan akun.');
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
        abort_unless($this->canManage(), 403, 'Anda tidak memiliki hak untuk menyimpan bagan akun.');

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

            \App\Models\ActivityLog::record('Accounting', 'UPDATE_COA', $this->code, "Perbarui akun {$this->code} - {$this->name} ({$this->type})");

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

            \App\Models\ActivityLog::record('Accounting', 'CREATE_COA', $this->code, "Tambah akun {$this->code} - {$this->name} ({$this->type})");

            session()->flash('message', 'Akun baru berhasil dibuat.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        abort_unless($this->canManage(), 403, 'Anda tidak memiliki hak untuk menghapus bagan akun.');

        $acc = Account::find($id);
        if (!$acc) {
            return;
        }

        if ($acc->journalItems()->exists()) {
            session()->flash('error', 'Akun tidak bisa dihapus karena sudah memiliki riwayat jurnal. Biarkan akun ini tetap ada agar laporan (Ledger, Trial Balance, Neraca) tidak kehilangan data historis.');
            return;
        }

        \App\Models\ActivityLog::record('Accounting', 'DELETE_COA', $acc->code, "Hapus akun {$acc->code} - {$acc->name}");
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