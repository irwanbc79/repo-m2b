<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use NumberFormatter;

use App\Models\{
    Account,
    Invoice,
    CashTransaction
};

class CashierManager extends Component
{
    use WithFileUploads;

    /**
     * =========================
     * FORM STATE
     * =========================
     */
    public string $mode = 'in'; // in | out
    public string $transaction_date;
    public float $amount = 0;

    public ?int $cash_account_id = null;
    public ?int $counter_account_id = null;
    public ?int $invoice_id = null;

    public string $description = '';
    public $proof;

    // Untuk cetak (tidak disimpan ke DB)
    public array $signatories = [];

    /**
     * =========================
     * VALIDATION
     * =========================
     */
    protected function rules(): array
    {
        return [
            'transaction_date'   => 'required|date',
            'amount'             => 'required|numeric|min:1',
            'cash_account_id'    => 'required|exists:accounts,id',
            'counter_account_id' => 'required|exists:accounts,id',
            'invoice_id'         => 'nullable|exists:invoices,id',
            'proof'              => 'nullable|file|max:2048',
        ];
    }

    /**
     * =========================
     * LIFECYCLE
     * =========================
     */
    public function mount(): void
    {
        $this->transaction_date = now()->toDateString();
        $this->mode = 'in';
    }

    /**
     * =========================
     * COMPUTED PROPERTIES
     * =========================
     */

    /** List akun kas & bank */
    public function getCashAccountsProperty()
    {
        return Account::whereIn('type', ['cash', 'bank'])
            ->orderBy('code')
            ->get();
    }

    /** Invoice yang belum dikaitkan ke kas */
    public function getInvoicesProperty()
    {
        return Invoice::where('status', 'paid')
            ->where('linked_to_cash', 0)
            ->orderBy('invoice_date')
            ->get();
    }

    /** Riwayat kas (INI YANG HILANG SEBELUMNYA) */
    public function getCashHistoriesProperty()
    {
        return CashTransaction::with([
                'account',
                'counterAccount',
            ])
            ->orderBy('transaction_date', 'desc')
            ->limit(100)
            ->get();
    }

    /** Terbilang */
    public function getAmountTerbilangProperty(): string
    {
        if ($this->amount <= 0) {
            return 'Nol Rupiah';
        }

        $fmt = new NumberFormatter('id', NumberFormatter::SPELLOUT);
        return ucwords($fmt->format($this->amount)) . ' Rupiah';
    }

    /**
     * =========================
     * ACTIONS
     * =========================
     */
    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {

            $proofPath = null;
            if ($this->proof) {
                $proofPath = $this->proof->store('cashier/proofs', 'public');
            }

            CashTransaction::create([
                'transaction_date'   => $this->transaction_date,
                'type'               => $this->mode,
                'amount'             => $this->amount,
                'account_id'         => $this->cash_account_id,
                'counter_account_id' => $this->counter_account_id,
                'invoice_id'         => $this->invoice_id,
                'description'        => $this->description,
                'proof_path'         => $proofPath,
                'created_by'         => auth()->id(),
            ]);

            if ($this->invoice_id) {
                Invoice::where('id', $this->invoice_id)
                    ->update(['linked_to_cash' => 1]);
            }
        });

        $this->resetForm();

        session()->flash('success', 'Transaksi kas berhasil dicatat.');
    }

    protected function resetForm(): void
    {
        $this->mode = 'in';
        $this->amount = 0;
        $this->cash_account_id = null;
        $this->counter_account_id = null;
        $this->invoice_id = null;
        $this->description = '';
        $this->proof = null;
        $this->signatories = [];
    }

    /**
     * =========================
     * RENDER
     * =========================
     */
    public function render()
    {
        return view('livewire.admin.cashier-manager', [
            'cashAccounts' => $this->cashAccounts,
            'invoices'     => $this->invoices,
        ]);
    }
}
