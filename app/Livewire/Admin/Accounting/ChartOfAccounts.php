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

    public function updatingSearch() { $this->resetPage(); }

    public function getStats()
    {
        return [
            'total_accounts' => Account::count(),
            'kas_bank' => Account::where('type', 'kas_bank')->sum('current_balance'),
            'piutang' => Account::where('type', 'piutang')->sum('current_balance'),
            'hutang' => Account::whereIn('type', ['hutang_lancar', 'hutang_jangka_panjang'])->sum('current_balance'),
            'pendapatan' => Account::where('type', 'pendapatan')->sum('current_balance'),
            'beban' => Account::whereIn('type', ['beban_operasional', 'beban_pokok', 'beban_lain'])->sum('current_balance'),
            'modal' => Account::where('type', 'modal')->sum('current_balance'),
        ];
    }

    public function render()
    {
        $accounts = Account::query()
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

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'opening_balance' => $this->opening_balance,
            // Jika edit, current balance dihitung ulang nanti (sementara samakan logic dasar)
            'current_balance' => $this->opening_balance 
        ];

        if ($this->isEditing) {
            $acc = Account::find($this->editingId);
            // Update logic saldo berjalan (current_balance) bisa lebih kompleks nanti
            // Untuk sekarang kita update data master saja
            $acc->update($data);
            session()->flash('message', 'Akun berhasil diperbarui.');
        } else {
            Account::create($data);
            session()->flash('message', 'Akun baru berhasil dibuat.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        // Cegah hapus jika sudah ada transaksi (Nanti kita tambah validasi jurnal)
        $acc = Account::find($id);
        if($acc) {
            $acc->delete();
            session()->flash('message', 'Akun dihapus.');
        }
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