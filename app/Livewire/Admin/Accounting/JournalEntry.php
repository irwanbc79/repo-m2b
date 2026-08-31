<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Journal;
use App\Models\Account;
use App\Models\JournalItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalEntry extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    public $isModalOpen = false;
    public $isEditing = false;
    public $editingId = null;

    // Form Data
    public $transaction_date;
    public $description;
    public $reference_no;
    
    // Dynamic Items
    public $items = []; 
    public $totalDebit = 0;
    public $totalCredit = 0;

    public function mount()
    {
        abort_unless(Auth::user()->hasPermission('cashier.view'), 403);

        $this->transaction_date = date('Y-m-d');
        $this->resetItems();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function resetFilter()
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->sortField = 'transaction_date';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function render()
    {
        $query = Journal::with(['items.account', 'creator']);

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('journal_number', 'like', '%' . $s . '%')
                  ->orWhere('description', 'like', '%' . $s . '%')
                  ->orWhere('reference_no', 'like', '%' . $s . '%');
            });
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('transaction_date', '>=', $this->dateFrom);
        }
        if (!empty($this->dateTo)) {
            $query->whereDate('transaction_date', '<=', $this->dateTo);
        }

        // Urutkan BERDASARKAN TANGGAL TRANSAKSI secara konsisten
        $journals = $query->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('id', $this->sortDirection)
            ->paginate(25);

        $accounts = Account::orderBy('code')->get();

        return view('livewire.admin.accounting.journal-entry', [
            'journals' => $journals,
            'accounts' => $accounts
        ])->layout('layouts.admin');
    }

    // --- LOGIC FORM ---

    public function create()
    {
        $this->resetInput();
        $this->isEditing = false;
        $this->isModalOpen = true;
    }

    public function addItem()
    {
        $this->items[] = ['account_id' => '', 'debit' => 0, 'credit' => 0];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $this->totalDebit = 0;
        $this->totalCredit = 0;

        foreach ($this->items as $item) {
            $this->totalDebit += (float) ($item['debit'] ?? 0);
            $this->totalCredit += (float) ($item['credit'] ?? 0);
        }
    }

    public function updatedItems() { $this->calculateTotal(); }

    public function save()
    {
        abort_unless(Auth::user()->hasPermission('cashier.view'), 403);

        $this->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required',
        ], [
            'items.*.account_id.required' => 'Semua baris harus memiliki Akun yang dipilih.',
        ]);

        $this->calculateTotal();

        // Validasi Balance (Toleransi 1 rupiah)
        if (abs($this->totalDebit - $this->totalCredit) > 1) {
            $this->addError('balance', 'JURNAL TIDAK BALANCE! Selisih: Rp ' . number_format($this->totalDebit - $this->totalCredit));
            return;
        }

        if ($this->totalDebit == 0) {
            $this->addError('balance', 'Nominal transaksi tidak boleh nol.');
            return;
        }

        DB::transaction(function () {
            // 1. Handle Edit Mode (Rollback Saldo Lama Dulu)
            if ($this->isEditing) {
                $journal = Journal::find($this->editingId);
                
                foreach($journal->items as $oldItem) {
                    $acc = Account::find($oldItem->account_id);
                    if($acc) {
                        $isDebitNormal = in_array($acc->type, ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain']);
                        if ($isDebitNormal) {
                             $acc->current_balance -= $oldItem->debit;
                             $acc->current_balance += $oldItem->credit;
                        } else {
                             $acc->current_balance += $oldItem->debit;
                             $acc->current_balance -= $oldItem->credit;
                        }
                        $acc->save();
                    }
                }

                $journal->update([
                    'transaction_date' => $this->transaction_date,
                    'description' => $this->description,
                    'reference_no' => $this->reference_no,
                ]);
                $journal->items()->delete();
                $journalId = $journal->id;
            } else {
                // 2. Create New Journal dengan nomor urut aman anti-duplikat
                $prefix = 'JR-' . date('ym') . '-';
                $lastJournal = Journal::where('journal_number', 'like', $prefix . '%')
                    ->orderByDesc('journal_number')
                    ->value('journal_number');

                $sequence = 1;
                if ($lastJournal && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastJournal, $matches)) {
                    $sequence = (int) $matches[1] + 1;
                }

                $journalNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                while (Journal::where('journal_number', $journalNumber)->exists()) {
                    $sequence++;
                    $journalNumber = $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                }

                $journal = Journal::create([
                    'journal_number' => $journalNumber,
                    'transaction_date' => $this->transaction_date,
                    'description' => $this->description,
                    'reference_no' => $this->reference_no,
                    'created_by' => Auth::id(),
                    'status' => 'posted'
                ]);
                $journalId = $journal->id;
            }

            // 3. Simpan Detail Baru & Update Saldo Akun
            foreach ($this->items as $item) {
                JournalItem::create([
                    'journal_id' => $journalId,
                    'account_id' => $item['account_id'],
                    'debit' => (float) $item['debit'],
                    'credit' => (float) $item['credit'],
                ]);
                
                $acc = Account::find($item['account_id']);
                if($acc) {
                    $isDebitNormal = in_array($acc->type, ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain']);
                    
                    if ($isDebitNormal) {
                        $acc->current_balance += $item['debit'];
                        $acc->current_balance -= $item['credit'];
                    } else {
                        $acc->current_balance -= $item['debit'];
                        $acc->current_balance += $item['credit'];
                    }
                    $acc->save();
                }
            }
        });

        \App\Models\ActivityLog::record('Accounting', $this->isEditing ? 'UPDATE_JOURNAL' : 'CREATE_JOURNAL', $this->reference_no ?: ($this->isEditing ? 'JR-' . $this->editingId : 'New Journal'), "Jurnal umum: {$this->description} (Total: Rp " . number_format($this->totalDebit, 0, ',', '.') . ")");

        session()->flash('message', 'Jurnal berhasil disimpan & Saldo diperbarui!');
        $this->closeModal();
    }

    /**
     * FIX DELETE: Mendukung pengecekan status kasir dan rollback saldo.
     */
    public function delete($id)
    {
        abort_unless(Auth::user()->hasPermission('cashier.view'), 403);

        try {
            DB::transaction(function () use ($id) {
                $journal = Journal::findOrFail($id);

                // === ROLE-BASED PERMISSION CHECK ===
                $user = Auth::user();
                $isAccountingOrAdmin = $user->hasRole(['admin', 'super_admin', 'director', 'manager', 'finance', 'staff_accounting'])
                    || $user->hasPermission('cashier.*')
                    || $user->hasPermission('cashier.journal');

                // Jika bukan admin / finance / accounting, hanya bisa menghapus jurnal yang dibuat sendiri
                if (!$isAccountingOrAdmin && $journal->created_by !== $user->id) {
                    throw new \Exception("Anda hanya bisa menghapus jurnal yang Anda buat sendiri.");
                }

                // 1. CEK RELASI KE KASIR & VALIDASI STATUS 'DRAFT'
                if (method_exists($journal, 'cashTransactions') && $journal->cashTransactions()->exists()) {
                    $linkedTransaction = $journal->cashTransactions()->first();

                    // VALIDASI: Hanya status 'Draft' di kasir yang boleh dihapus
                    if (isset($linkedTransaction->status) && strtolower($linkedTransaction->status) !== 'draft') {
                        throw new \Exception("Gagal Hapus: Jurnal ini terhubung dengan transaksi Kasir berstatus '" . strtoupper($linkedTransaction->status) . "'. Hanya transaksi 'Draft' yang boleh dihapus!");
                    }

                    // Hapus transaksi kasirnya dulu agar Foreign Key tidak bentrok
                    $linkedTransaction->delete();
                }

                // 2. ROLLBACK SALDO AKUN
                // Sebelum jurnal dihapus, kita harus kembalikan saldo akun ke posisi awal
                foreach ($journal->items as $item) {
                    $acc = Account::find($item->account_id);
                    if ($acc) {
                        $isDebitNormal = in_array($acc->type, ['kas_bank', 'piutang', 'persediaan', 'aset_lancar_lain', 'aset_tetap', 'beban_pokok', 'beban_operasional', 'beban_lain']);
                        
                        if ($isDebitNormal) {
                            $acc->current_balance -= $item->debit;
                            $acc->current_balance += $item->credit;
                        } else {
                            $acc->current_balance += $item->debit;
                            $acc->current_balance -= $item->credit;
                        }
                        $acc->save();
                    }
                }

                // Log sebelum hapus
                \App\Models\ActivityLog::record('Accounting', 'DELETE_JOURNAL', $journal->journal_number, "Hapus jurnal {$journal->journal_number}: {$journal->description} (Rp " . number_format($journal->items->sum('debit'), 0, ',', '.') . ")");

                // 3. HAPUS DATA JURNAL
                $journal->items()->delete(); 
                $journal->delete();
            });

            session()->flash('message', 'Jurnal Entry dan keterkaitan data berhasil dihapus. Saldo akun telah disesuaikan.');

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == "23000") {
                session()->flash('error', 'Gagal Hapus: Data digunakan oleh modul lain yang tidak terdeteksi.');
            } else {
                session()->flash('error', 'Database Error: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeModal() { $this->isModalOpen = false; }
    
    private function resetInput()
    {
        $this->transaction_date = date('Y-m-d');
        $this->description = '';
        $this->reference_no = '';
        $this->resetItems();
    }

    private function resetItems()
    {
        $this->items = [
            ['account_id' => '', 'debit' => 0, 'credit' => 0],
            ['account_id' => '', 'debit' => 0, 'credit' => 0]
        ];
        $this->calculateTotal();
    }
}