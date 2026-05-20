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
use Illuminate\Support\Facades\Storage;
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
    public $filterCounterpartType = 'all';
    public $filterCostCategory = 'all';
    public $filterCurrency = 'all';
    public $filterAmountMin = '';
    public $filterAmountMax = '';
    public $filterNoJournal = false;
    public $filterNoBukti = false;

    // Summary stats (recomputed on every filter load)
    public $summaryTotalIn = 0;
    public $summaryTotalOut = 0;
    public $summaryCount = 0;
    public $summaryNoBukti = 0;
    
    // Edit mode
    public $editingId = null;
    public $showDeleteConfirm = false;
    public $deleteId = null;
    public $perPage = 25;
    public $totalRecords = 0;
    public $currentPage = 1;

    // Job Costing reminder
    public $outstandingJcAlert = null;
    public $showReconcilePanel = false;
    public $reconcileData = [];

    // Proof / bukti transaksi
    public $showProofModal = false;
    public $proofTransactionId = null;
    public $proofUrl = null;
    public $proofFilename = null;
    public $proofIsImage = false;
    public $proofIsPdf = false;

    public $showUploadProofModal = false;
    public $uploadProofId = null;
    public $proofAttachment;
    
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
    
    private function buildTransactionQuery()
    {
        $query = CashTransaction::query();

        if (!empty($this->searchTerm)) {
            $search = $this->searchTerm;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($q) => $q->where('company_name', 'LIKE', "%{$search}%"))
                  ->orWhereHas('vendor', fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
                  ->orWhereHas('shipment', fn($q) => $q->where('awb_number', 'LIKE', "%{$search}%"))
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterNoJournal) {
            $query->whereNull('journal_id');
        } elseif ($this->filterStatus !== 'all') {
            $query->whereHas('journal', function ($q) {
                $q->where('status', $this->filterStatus === 'posted' ? 'posted' : 'draft');
            });
        }

        if (!empty($this->filterDateFrom)) {
            $query->whereDate('transaction_date', '>=', $this->filterDateFrom);
        }
        if (!empty($this->filterDateTo)) {
            $query->whereDate('transaction_date', '<=', $this->filterDateTo);
        }

        if ($this->filterCounterpartType !== 'all') {
            $query->where('counterpart_type', $this->filterCounterpartType);
        }
        if ($this->filterCostCategory !== 'all') {
            $query->where('cost_category', $this->filterCostCategory);
        }
        if ($this->filterCurrency !== 'all') {
            $query->where('currency', $this->filterCurrency);
        }
        if (!empty($this->filterAmountMin)) {
            $query->where('amount', '>=', (float) $this->filterAmountMin);
        }
        if (!empty($this->filterAmountMax)) {
            $query->where('amount', '<=', (float) $this->filterAmountMax);
        }

        if ($this->filterNoBukti) {
            $query->whereNull('proof_file');
        }

        return $query;
    }

    private function loadRecentTransactions()
    {
        $base = $this->buildTransactionQuery();

        $this->totalRecords  = (clone $base)->count();
        $this->summaryCount  = $this->totalRecords;
        $this->summaryTotalIn  = (clone $base)->where('type', 'in')->sum('amount');
        $this->summaryTotalOut = (clone $base)->where('type', 'out')->sum('amount');

        $this->summaryNoBukti = (clone $base)->whereNull('proof_file')->count();

        $this->recentTransactions = (clone $base)
            ->with(['customer', 'vendor', 'shipment', 'journal', 'creator'])
            ->latest('transaction_date')
            ->skip(($this->currentPage - 1) * $this->perPage)
            ->take($this->perPage)
            ->get()
            ->toArray();
    }
    
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function applyFilters()
    {
        $this->currentPage = 1;
        $this->loadRecentTransactions();
    }

    public function updatedSearchTerm()
    {
        $this->currentPage = 1;
        $this->loadRecentTransactions();
    }

    public function updated($name)
    {
        if (str_starts_with($name, 'filter')) {
            $this->currentPage = 1;
            $this->loadRecentTransactions();
        }
    }

    public function clearFilters()
    {
        $this->searchTerm = '';
        $this->filterType = 'all';
        $this->filterStatus = 'all';
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
        $this->filterCounterpartType = 'all';
        $this->filterCostCategory = 'all';
        $this->filterCurrency = 'all';
        $this->filterAmountMin = '';
        $this->filterAmountMax = '';
        $this->filterNoJournal = false;
        $this->filterNoBukti = false;
        $this->currentPage = 1;
        $this->loadRecentTransactions();
    }
    
    public function exportExcel()
    {
        $transactions = $this->buildTransactionQuery()
            ->with(['customer', 'vendor', 'shipment', 'journal', 'creator'])
            ->latest('transaction_date')
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
        $transactions = $this->buildTransactionQuery()
            ->with(['customer', 'vendor', 'shipment', 'journal', 'creator'])
            ->latest('transaction_date')
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
        
        if (!in_array(($transaction->journal->status ?? 'draft'), ['draft', 'pending', 'posted'])) {
            session()->flash('error', 'Hanya transaksi Draft yang dapat diedit!');
            return;
        }
        
        $this->editingId = $id;
        $this->transaction_date = $transaction->transaction_date->format('Y-m-d');
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
    
    public function removeTransaction($id)
    {
        return $this->confirmDelete($id);
    }
    
    public function confirmDelete($id)
    {
        \Log::info("🟠 ConfirmDelete", ["id" => $id, "user" => auth()->id()]);
        $transaction = CashTransaction::with(['journal'])->findOrFail($id);
        
        if (!in_array(($transaction->journal->status ?? 'draft'), ['draft', 'pending', 'posted'])) {
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
            
            if (!in_array(($transaction->journal->status ?? 'draft'), ['draft', 'pending', 'posted'])) {
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

    public function executeDelete()
    {
        $this->deleteTransaction();
    }

    // ── BUKTI TRANSAKSI ─────────────────────────────────────────────────────

    public function openProofModal($id)
    {
        $trx = CashTransaction::find($id);
        if (!$trx || !$trx->proof_file) return;

        $ext = strtolower(pathinfo($trx->proof_file, PATHINFO_EXTENSION));

        $this->proofTransactionId = $id;
        $this->proofUrl           = Storage::disk('public')->url($trx->proof_file);
        $this->proofFilename      = basename($trx->proof_file);
        $this->proofIsImage       = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $this->proofIsPdf         = $ext === 'pdf';
        $this->showProofModal     = true;
    }

    public function closeProofModal()
    {
        $this->showProofModal     = false;
        $this->proofTransactionId = null;
        $this->proofUrl           = null;
        $this->proofFilename      = null;
    }

    public function openUploadProofModal($id)
    {
        $this->uploadProofId         = $id;
        $this->proofAttachment       = null;
        $this->showUploadProofModal  = true;
        $this->showProofModal        = false;
    }

    public function closeUploadProofModal()
    {
        $this->showUploadProofModal = false;
        $this->uploadProofId        = null;
        $this->proofAttachment      = null;
    }

    public function saveProof()
    {
        $this->validate([
            'proofAttachment' => 'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf',
        ], [
            'proofAttachment.required' => 'Pilih file bukti transaksi.',
            'proofAttachment.max'      => 'Ukuran file maksimal 5MB.',
            'proofAttachment.mimes'    => 'Format file: JPG, PNG, GIF, WEBP, atau PDF.',
        ]);

        $trx = CashTransaction::find($this->uploadProofId);
        if (!$trx) return;

        if ($trx->proof_file && Storage::disk('public')->exists($trx->proof_file)) {
            Storage::disk('public')->delete($trx->proof_file);
        }

        $path = $this->proofAttachment->store('cash-transactions', 'public');
        $trx->update(['proof_file' => $path]);

        $this->closeUploadProofModal();
        $this->loadRecentTransactions();
        session()->flash('success', 'Bukti transaksi berhasil disimpan.');
    }

    // ────────────────────────────────────────────────────────────────────────

    public function updatedTransactionType()
    {
        $this->outstandingJcAlert = null;
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
            $this->shipments = Shipment::with('customer')
                ->where('customer_id', $value)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'awb_number' => $s->awb_number,
                    'origin' => $s->origin ?? '',
                    'destination' => $s->destination ?? '',
                    'status' => $s->status,
                    'customer_name' => $s->customer->company_name ?? '-',
                ])
                ->toArray();
        } else {
            // Vendor: show ALL shipments (vendor can pay costs for any shipment)
            $this->shipments = Shipment::with('customer')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'awb_number' => $s->awb_number,
                    'origin' => $s->origin ?? '',
                    'destination' => $s->destination ?? '',
                    'status' => $s->status,
                    'customer_name' => $s->customer->company_name ?? '-',
                ])
                ->toArray();
        }
    }
    
    public function updatedShipmentId($value)
    {
        $this->outstandingJcAlert = null;
        if (!$value) return;

        $shipment = Shipment::find($value);
        if ($shipment && !$this->description) {
            $this->description = "Payment for shipment {$shipment->awb_number}";
        }

        if ($this->transaction_type === 'cash_out') {
            $this->loadOutstandingJcAlert((int) $value);
        }
    }

    private function loadOutstandingJcAlert(int $shipmentId): void
    {
        $items = DB::table('job_costs')
            ->leftJoin('vendors', 'job_costs.vendor_id', '=', 'vendors.id')
            ->where('job_costs.shipment_id', $shipmentId)
            ->where('job_costs.status', 'unpaid')
            ->select(
                'job_costs.id',
                'job_costs.description',
                'job_costs.amount',
                DB::raw("COALESCE(vendors.name, '(vendor tidak dicatat)') as vendor_name")
            )
            ->get();

        if ($items->isEmpty()) {
            $this->outstandingJcAlert = null;
            return;
        }

        $this->outstandingJcAlert = [
            'count' => $items->count(),
            'total' => $items->sum('amount'),
            'items' => $items->map(fn($i) => [
                'description' => $i->description,
                'amount'      => $i->amount,
                'vendor_name' => $i->vendor_name,
            ])->toArray(),
        ];
    }

    public function toggleReconcilePanel(): void
    {
        $this->showReconcilePanel = !$this->showReconcilePanel;
        if ($this->showReconcilePanel) {
            $this->loadReconcilePanel();
        }
    }

    public function refreshReconcilePanel(): void
    {
        $this->loadReconcilePanel();
    }

    private function loadReconcilePanel(): void
    {
        $shipments = DB::table('shipments as s')
            ->join('customers as c', 's.customer_id', '=', 'c.id')
            ->join('job_costs as jc', 'jc.shipment_id', '=', 's.id')
            ->where('jc.status', 'unpaid')
            ->select(
                's.id', 's.awb_number', 's.bl_number', 's.service_type',
                'c.company_name',
                DB::raw('SUM(jc.amount) as jc_outstanding'),
                DB::raw('COUNT(jc.id) as jc_count')
            )
            ->groupBy('s.id', 's.awb_number', 's.bl_number', 's.service_type', 'c.company_name')
            ->orderByDesc('jc_outstanding')
            ->get();

        $this->reconcileData = $shipments->map(function ($ship) {
            $ctTotal = DB::table('cash_transactions')
                ->where('shipment_id', $ship->id)
                ->where('type', 'out')
                ->sum('amount');

            $gap    = max(0, (float) $ship->jc_outstanding - (float) $ctTotal);
            $status = $gap <= 0 ? 'ok' : ((float) $ctTotal == 0 ? 'none' : 'partial');

            return [
                'ship_id'        => $ship->id,
                'awb'            => $ship->awb_number ?? $ship->bl_number ?? "ID#{$ship->id}",
                'customer'       => $ship->company_name,
                'service_type'   => strtoupper($ship->service_type ?? '-'),
                'jc_outstanding' => (float) $ship->jc_outstanding,
                'jc_count'       => (int) $ship->jc_count,
                'ct_total'       => (float) $ctTotal,
                'gap'            => $gap,
                'status'         => $status,
            ];
        })->toArray();
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
        $this->outstandingJcAlert = null;
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
