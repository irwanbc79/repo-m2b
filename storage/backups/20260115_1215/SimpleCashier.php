<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\CashierService;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SimpleCashier extends Component
{
    use WithFileUploads;
    
    // Form properties
    public $transaction_date;
    public $transaction_type = 'cash_in';
    public $counterpart_type = 'customer';
    public $counterpart_id;
    public $counterpart_name;
    public $shipment_id;
    public $cost_category = 'shipment';
    public $amount;
    public $currency = 'IDR';
    public $exchange_rate = 1;
    public $description;
    public $attachment;
    
    // Related data
    public $invoice_id;
    public $vendor_bill_id;
    
    // Selections (for dropdowns)
    public $customers = [];
    public $vendors = [];
    public $shipments = [];
    public $invoices = [];
    public $vendorBills = [];
    
    // Preview data (read-only)
    public $preview = null;
    
    // UI state
    public $showPreview = false;
    public $isSubmitting = false;
    
    // Recent transactions
    public $recentTransactions = [];
    
    // Search & Filter properties
    public $searchTerm = '';
    public $filterType = 'all';
    public $filterStatus = 'all';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $showFilters = false;
    
    // Edit mode
    public $editingId = null;
    public $showDeleteConfirm = false;
    public $deleteId = null;
    public $perPage = 25;
    public $totalRecords = 0;
    public $currentPage = 1;
    
    protected $cashierService;
    
    public function boot(CashierService $cashierService)
    {
        $this->cashierService = $cashierService;
    }
    
    public function mount()
    {
        $this->transaction_date = now()->format('Y-m-d');
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
        $this->loadInitialData();
        $this->loadRecentTransactions();
    }
    
    private function loadInitialData()
    {
        $this->customers = Customer::with('user')
            ->orderBy('company_name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->company_name,
                'code' => $c->customer_code,
            ])
            ->toArray();
            
        $this->vendors = Vendor::orderBy('name')
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'code' => $v->code ?? '-',
            ])
            ->toArray();
    }
    
    private function loadRecentTransactions()
    {
        $query = CashTransaction::with(['customer', 'vendor', 'shipment', 'journal', 'creator']);
        
        if (!empty($this->searchTerm)) {
            $search = $this->searchTerm;
            $query->where(function($q) use ($search) {
                $q->whereHas('customer', function($q) use ($search) {
                    $q->where('company_name', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('vendor', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('shipment', function($q) use ($search) {
                    $q->where('awb_number', 'LIKE', "%{$search}%");
                })
                ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }
        
        if ($this->filterStatus !== 'all') {
            $query->whereHas('journal', function($q) {
                if ($this->filterStatus === 'posted') {
                    $q->where('status', 'posted');
                } else {
                    $q->where('status', 'draft');
                }
            });
        }
        
        if (!empty($this->filterDateFrom)) {
            $query->whereDate('transaction_date', '>=', $this->filterDateFrom);
        }
        if (!empty($this->filterDateTo)) {
            $query->whereDate('transaction_date', '<=', $this->filterDateTo);
        // Count total records
        $this->totalRecords = $query->count();
        
        // Get paginated data
        }
        
        $this->recentTransactions = $query->latest()
            ->skip(($this->currentPage - 1) * $this->perPage)->take($this->perPage)
            ->get()
            ->toArray();
    }
    
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }
    
    public function applyFilters()
    {
        $this->loadRecentTransactions();
        session()->flash('success', 'Filter diterapkan!');
    }
    
    public function clearFilters()
    {
        $this->searchTerm = '';
        $this->filterType = 'all';
        $this->filterStatus = 'all';
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
        $this->loadRecentTransactions();
        session()->flash('success', 'Filter direset!');
    }
    
    public function exportExcel()
    {
        $transactions = CashTransaction::with(['customer', 'vendor', 'shipment', 'journal', 'creator'])
            ->when(!empty($this->searchTerm), function($q) {
                $search = $this->searchTerm;
                $q->where(function($q) use ($search) {
                    $q->whereHas('customer', fn($q) => $q->where('company_name', 'LIKE', "%{$search}%"))
                      ->orWhereHas('vendor', fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
                      ->orWhereHas('shipment', fn($q) => $q->where('awb_number', 'LIKE', "%{$search}%"));
                });
            })
            ->when($this->filterType !== 'all', fn($q) => $q->where('type', $this->filterType))
            ->when(!empty($this->filterDateFrom), fn($q) => $q->whereDate('transaction_date', '>=', $this->filterDateFrom))
            ->when(!empty($this->filterDateTo), fn($q) => $q->whereDate('transaction_date', '<=', $this->filterDateTo))
            ->latest()
            ->get();
        
        $filename = 'cash_transactions_' . now()->format('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'Tanggal', 'Nomor Jurnal', 'Tipe', 'Customer', 'Vendor', 
                'Shipment AWB', 'Jumlah', 'Mata Uang', 'Keterangan', 
                'Status', 'Dibuat Oleh', 'Dibuat Pada'
            ]);
            
            foreach ($transactions as $trx) {
                fputcsv($file, [
                    $trx->transaction_date,
                    $trx->journal->journal_number ?? '',
                    $trx->type === 'in' ? 'Terima' : 'Bayar',
                    $trx->customer->company_name ?? '',
                    $trx->vendor->name ?? '',
                    $trx->shipment->awb_number ?? '',
                    $trx->amount,
                    $trx->currency ?? 'IDR',
                    $trx->description ?? '',
                    ($trx->journal->status ?? 'draft') === 'posted' ? 'Posted' : 'Draft',
                    $trx->creator->name ?? '',
                    $trx->created_at
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    public function exportPdf()
    {
        $transactions = CashTransaction::with(['customer', 'vendor', 'shipment', 'journal', 'creator'])
            ->when(!empty($this->searchTerm), function($q) {
                $search = $this->searchTerm;
                $q->where(function($q) use ($search) {
                    $q->whereHas('customer', fn($q) => $q->where('company_name', 'LIKE', "%{$search}%"))
                      ->orWhereHas('vendor', fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
                      ->orWhereHas('shipment', fn($q) => $q->where('awb_number', 'LIKE', "%{$search}%"));
                });
            })
            ->when($this->filterType !== 'all', fn($q) => $q->where('type', $this->filterType))
            ->when(!empty($this->filterDateFrom), fn($q) => $q->whereDate('transaction_date', '>=', $this->filterDateFrom))
            ->when(!empty($this->filterDateTo), fn($q) => $q->whereDate('transaction_date', '<=', $this->filterDateTo))
            ->latest()
            ->get();
        
        $totalIn = $transactions->where('type', 'in')->sum('amount');
        $totalOut = $transactions->where('type', 'out')->sum('amount');
        $netCash = $totalIn - $totalOut;
        
        $pdf = Pdf::loadView('livewire.admin.export-transactions-pdf', [
            'transactions' => $transactions,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'netCash' => $netCash,
            'filters' => [
                'dateFrom' => $this->filterDateFrom,
                'dateTo' => $this->filterDateTo,
                'type' => $this->filterType,
                'status' => $this->filterStatus,
            ]
        ]);
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->stream();
        }, 'cash_transactions_' . now()->format('YmdHis') . '.pdf');
    }
    
    public function editTransaction($id)
    {
        \Log::info("🔵 EditTransaction", ["id" => $id, "user" => auth()->id()]);
        $transaction = CashTransaction::with(['journal'])->findOrFail($id);
        
        if (($transaction->journal->status ?? 'draft') !== 'draft') {
            session()->flash('error', 'Hanya transaksi Draft yang dapat diedit!');
            return;
        }
        
        $this->editingId = $id;
        $this->transaction_date = $transaction->transaction_date;
        $this->transaction_type = $transaction->type === 'in' ? 'cash_in' : 'cash_out';
        $this->counterpart_id = $transaction->customer_id ?? $transaction->vendor_id;
        $this->counterpart_type = $transaction->customer_id ? 'customer' : 'vendor';
        $this->shipment_id = $transaction->shipment_id;
        $this->amount = $transaction->amount;
        $this->currency = $transaction->currency ?? 'IDR';
        $this->description = $transaction->description;
        
        $this->updatePreview();
        $this->dispatch('scroll-to-form');
        
        session()->flash('info', 'Mode Edit - Ubah data dan klik Simpan untuk update');
    }
    
    public function confirmDelete($id)
    {
        \Log::info("🟠 ConfirmDelete", ["id" => $id, "user" => auth()->id()]);
        $transaction = CashTransaction::with(['journal'])->findOrFail($id);
        
        if (($transaction->journal->status ?? 'draft') !== 'draft') {
            session()->flash('error', 'Hanya transaksi Draft yang dapat dihapus!');
            return;
        }
        
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }
    
    public function deleteTransaction()
    {
        \Log::info("🔴 DeleteTransaction", ["deleteId" => $this->deleteId]);
        try {
            DB::beginTransaction();
            
            $transaction = CashTransaction::with(['journal'])->findOrFail($this->deleteId);
            
            if (($transaction->journal->status ?? 'draft') !== 'draft') {
                throw new \Exception('Hanya transaksi Draft yang dapat dihapus!');
            }
            
            $journalId = $transaction->journal_id;
            $transaction->delete();
            
            if ($journalId) {
                \App\Models\Journal::where('id', $journalId)->delete();
            }
            
            DB::commit();
            
            $this->showDeleteConfirm = false;
            $this->deleteId = null;
            $this->loadRecentTransactions();
            
            session()->flash('success', 'Transaksi berhasil dihapus!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
    
    public function cancelDelete()
    {
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
    }
    
    public function updatedTransactionType()
    {
        $this->counterpart_type = $this->transaction_type === 'cash_in' ? 'customer' : 'vendor';
        $this->counterpart_id = null;
        $this->counterpart_name = null;
        $this->shipment_id = null;
        $this->invoice_id = null;
        $this->vendor_bill_id = null;
        $this->shipments = [];
        $this->invoices = [];
        $this->vendorBills = [];
        $this->preview = null;
        $this->showPreview = false;
    }
    
    public function updatedCounterpartType()
    {
        $this->counterpart_id = null;
        $this->counterpart_name = null;
        $this->shipment_id = null;
        $this->invoice_id = null;
        $this->vendor_bill_id = null;
        $this->shipments = [];
        $this->invoices = [];
        $this->vendorBills = [];
        $this->preview = null;
        $this->showPreview = false;
    }
    
    public function updatedCounterpartId($value)
    {
        if (!$value) {
            $this->shipment_id = null;
            $this->shipments = [];
            return;
        }
        
        $this->counterpart_name = $this->counterpart_type === 'customer' 
            ? (Customer::find($value)->company_name ?? '')
            : (Vendor::find($value)->name ?? '');
        
        if ($this->counterpart_type === 'customer') {
            $this->shipments = Shipment::where('customer_id', $value)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'awb_number' => $s->awb_number,
                    'origin' => $s->origin ?? '',
                    'destination' => $s->destination ?? '',
                'status' => $s->status,
                ])
                ->toArray();
        } else {
            // Vendor: show ALL shipments (vendor can pay costs for any shipment)
            $this->shipments = Shipment::orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'awb_number' => $s->awb_number,
                    'origin' => $s->origin ?? '',
                    'destination' => $s->destination ?? '',
                    'status' => $s->status,
                ])
                ->toArray();
        }
    }
    
    public function updatedShipmentId($value)
    {
        if (!$value) return;
        
        $shipment = Shipment::find($value);
        if ($shipment && !$this->description) {
            $this->description = "Payment for shipment {$shipment->awb_number}";
        }
    }
    
    public function updatedAmount()
    {
        if ($this->amount && $this->counterpart_id) {
            $this->updatePreview();
        }
    }
    
    public function updatePreview()
    {
        $this->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:cash_in,cash_out',
            'counterpart_type' => 'required|in:customer,vendor',
            'counterpart_id' => 'required|exists:' . ($this->counterpart_type === 'customer' ? 'customers' : 'vendors') . ',id',
            'amount' => 'required|numeric|min:0',
        ]);
        
        $data = [
            'transaction_date' => $this->transaction_date,
            'type' => $this->transaction_type === 'cash_in' ? 'in' : 'out',
            'counterpart_type' => $this->counterpart_type,
            'counterpart_id' => $this->counterpart_id,
            'customer_id' => $this->counterpart_type === 'customer' ? $this->counterpart_id : null,
            'vendor_id' => $this->counterpart_type === 'vendor' ? $this->counterpart_id : null,
            'shipment_id' => $this->shipment_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
        ];
        
        try {
            $this->preview = $this->cashierService->getAccountPairingPreview($data);
            $this->showPreview = true;
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal membuat preview: ' . $e->getMessage());
            $this->showPreview = false;
        }
    }
    
    public function save()
    {
        $this->isSubmitting = true;
        
        $this->validate([
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:cash_in,cash_out',
            'counterpart_type' => 'required|in:customer,vendor',
            'counterpart_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            $data = [
                'transaction_date' => $this->transaction_date,
                'type' => $this->transaction_type === 'cash_in' ? 'in' : 'out',
                'counterpart_type' => $this->counterpart_type,
                'counterpart_id' => $this->counterpart_id,
                'customer_id' => $this->counterpart_type === 'customer' ? $this->counterpart_id : null,
                'vendor_id' => $this->counterpart_type === 'vendor' ? $this->counterpart_id : null,
                'shipment_id' => $this->shipment_id,
                'invoice_id' => $this->invoice_id,
                'vendor_bill_id' => $this->vendor_bill_id,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'exchange_rate' => $this->exchange_rate,
                'description' => $this->description,
                'cost_category' => $this->cost_category,
            ];
            
            if ($this->editingId) {
                $cashTransaction = $this->cashierService->updateTransaction($this->editingId, $data, $this->attachment);
                $message = 'Transaksi berhasil diupdate!';
            } else {
                $cashTransaction = $this->cashierService->processPayment($data, $this->attachment);
                $message = 'Transaksi berhasil disimpan dan telah di-posting ke accounting!';
            }
            
            DB::commit();
            
            $this->resetForm();
            $this->loadRecentTransactions();
            
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }
    
    public function resetForm()
    {
        $this->editingId = null;
        $this->transaction_date = now()->format('Y-m-d');
        $this->transaction_type = 'cash_in';
        $this->counterpart_type = 'customer';
        $this->counterpart_id = null;
        $this->counterpart_name = null;
        $this->shipment_id = null;
        $this->invoice_id = null;
        $this->vendor_bill_id = null;
        $this->amount = null;
        $this->currency = 'IDR';
        $this->exchange_rate = 1;
        $this->description = null;
        $this->attachment = null;
        $this->shipments = [];
        $this->invoices = [];
        $this->vendorBills = [];
        $this->preview = null;
        $this->showPreview = false;
    }
    

    public function updatedPerPage()
    {
        $this->recentTransactions = [];
        $this->currentPage = 1;
        $this->loadRecentTransactions();
    }

    public function nextPage()
    {
        $totalPages = ceil($this->totalRecords / $this->perPage);
        if ($this->currentPage < $totalPages) {
            $this->currentPage++;
            $this->loadRecentTransactions();
        }
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadRecentTransactions();
        }
    }

    public function goToPage($page)
    {
        $totalPages = ceil($this->totalRecords / $this->perPage);
        if ($page >= 1 && $page <= $totalPages) {
            $this->currentPage = $page;
            $this->loadRecentTransactions();
        }
    }

    public function render()
    {
        return view('livewire.admin.simple-cashier')
            ->layout('layouts.admin');
    }
}
