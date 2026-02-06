<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Shipment;
use App\Models\JobCost;
use App\Models\Vendor;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\VendorRating;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Cache;

class JobCostingManager extends Component
{
    use WithPagination, WithFileUploads;

    // =========================================
    // PROPERTIES
    // =========================================
    
    // Search & Filter
    public $search = '';
    public $filterLane = '';
    public $filterMargin = '';
    public $filterStatus = '';
    public $filterType = '';
    
    // Modal States
    public $isModalOpen = false;
    public $showPreviewModal = false;
    public $showDeleteConfirm = false;
    
    // Selected Data
    public $selectedShipment = null;
    public $shipmentIdForCost = null;
    public $costToDelete = null;

    // Form Inputs - Job Cost
    public $vendor_id;
    public $description;
    public $amount;
    public $payment_proof;
    public $status = 'unpaid';
    public $date_paid;
    
    // Form Inputs - Accounting
    public $coa_id;              // Akun Biaya (DEBIT)
    public $credit_account_id;   // Akun Kas/Bank (KREDIT)
    
    // Preview Properties
    public $previewUrl = null;
    public $previewType = null; // 'image' atau 'pdf'
    public $previewCostId = null;
    
    // Edit Mode
    public $editMode = false;
    public $editingCostId = null;
    
    // Vendor Rating
    public $showRatingModal = false;
    public $ratingVendorId = null;
    public $ratingVendorName = "";
    public $ratingJobCostId = null;
    public $vendorRating = 5;
    public $ratingNotes = "";

    // =========================================
    // VALIDATION RULES
    // =========================================
    
    protected function rules()
    {
        return [
            'vendor_id' => 'nullable|exists:vendors,id',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'status' => 'required|in:unpaid',
            'date_paid' => 'nullable|date',
            'coa_id' => 'nullable|exists:accounts,id',
            'credit_account_id' => 'nullable|exists:accounts,id',
        ];
    }

    protected $validationAttributes = [
        'vendor_id' => 'vendor',
        'description' => 'deskripsi',
        'amount' => 'jumlah',
        'payment_proof' => 'bukti bayar',
        'status' => 'status',
        'coa_id' => 'akun biaya',
        'credit_account_id' => 'akun kas/bank',
    ];

    // =========================================
    // LIFECYCLE HOOKS
    // =========================================
    
    protected $listeners = [
        'refreshComponent' => '$refresh',
        'costDeleted' => 'handleCostDeleted',
    ];

    public function mount()
    {
        // Set default credit account ke Kas Kecil (1102)
        $kasKecil = Account::where('code', '1102')->first();
        $this->credit_account_id = $kasKecil ? $kasKecil->id : null;

        // Set default coa_id ke Biaya Operasional (5101)
        $biayaOperasional = Account::where("code", "5101")->first();
        $this->coa_id = $biayaOperasional ? $biayaOperasional->id : null;
    }

    // Reset pagination when search/filter changes
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterLane() { $this->resetPage(); }
    public function updatingFilterMargin() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }

    // =========================================
    // STATISTICS CALCULATION
    // =========================================
    
    public function getStats()
    {
        return Cache::remember('jobcost_stats', 300, function() {
            try {
                // Eager load relationships untuk performa
                $shipments = Shipment::with(['invoices', 'jobCosts'])->active()->limit(500)->get();
            
            $totalRevenue = 0;
            $totalCost = 0;
            $margins = [];
            
            foreach ($shipments as $s) {
                $revenue = $s->invoices->sum('grand_total');
                $cost = $s->jobCosts->sum('amount');
                
                $totalRevenue += $revenue;
                $totalCost += $cost;
                
                // Hitung margin hanya jika ada revenue
                if ($revenue > 0) {
                    $marginPercent = (($revenue - $cost) / $revenue) * 100;
                    $margins[] = $marginPercent;
                }
            }
            
            // Count shipments dengan margin rendah (< 10%)
            $lowMarginCount = $shipments->filter(function($s) {
                $rev = $s->invoices->sum('grand_total');
                $cost = $s->jobCosts->sum('amount');
                
                if ($rev <= 0) return false;
                
                $margin = (($rev - $cost) / $rev) * 100;
                return $margin < 10;
            })->count();
            
            return [
                'total_shipments' => $shipments->count(),
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'total_profit' => $totalRevenue - $totalCost,
                'avg_margin' => count($margins) > 0 ? array_sum($margins) / count($margins) : 0,
                'low_margin' => $lowMarginCount,
                'shipments_with_costs' => $shipments->filter(fn($s) => $s->jobCosts->count() > 0)->count(),
                'unpaid_costs' => JobCost::where('status', 'unpaid')->sum('amount'),
                ];
            } catch (Exception $e) {
            Log::error('Error calculating stats: ' . $e->getMessage());
            
            return [
                'total_shipments' => 0,
                'total_revenue' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'avg_margin' => 0,
                'low_margin' => 0,
                'shipments_with_costs' => 0,
                'unpaid_costs' => 0,
                ];
            }
        });
    }

    // =========================================
    // RENDER METHOD
    // =========================================
    
    public function render()
    {
        try {
            // Base query dengan eager loading
            $query = Shipment::with(['customer', 'invoices', 'jobCosts.vendor', 'jobCosts.account'])
                ->active(); // Exclude cancelled shipments
            
            // Search by AWB, BL, or Customer Name
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('awb_number', 'like', "%{$this->search}%")
                      ->orWhere('bl_number', 'like', "%{$this->search}%")
                      ->orWhereHas('customer', fn($c) => 
                          $c->where('company_name', 'like', "%{$this->search}%")
                            ->orWhere('company_name', 'like', "%{$this->search}%")
                      );
                });
            }
            
            // Filter by Lane Status
            if ($this->filterLane) {
                $query->where('lane_status', $this->filterLane);
            }
            
            // Filter by Status
            if ($this->filterStatus) {
                $query->where('status', $this->filterStatus);
            }
            
            // Filter by Shipment Type
            if ($this->filterType) {
                $query->where('shipment_type', $this->filterType);
            }
            
            // Filter by Margin
            if ($this->filterMargin === 'low') {
                // Get shipments with margin < 10%
                $query->whereHas('invoices')
                    ->whereHas('jobCosts');
            } elseif ($this->filterMargin === 'high') {
                // Get shipments with margin >= 20%
                $query->whereHas('invoices');
            } elseif ($this->filterMargin === 'no_costs') {
                // Shipments without job costs
                $query->doesntHave('jobCosts');
            }
            
            $shipments = $query->orderBy('created_at', 'desc')->paginate(10);
            
            // Filter by margin percentage (setelah pagination untuk performa)
            if ($this->filterMargin === 'low' || $this->filterMargin === 'high') {
                $shipments->getCollection()->transform(function($shipment) {
                    $revenue = $shipment->invoices->sum('grand_total');
                    $cost = $shipment->jobCosts->sum('amount');
                    
                    if ($revenue > 0) {
                        $margin = (($revenue - $cost) / $revenue) * 100;
                        $shipment->margin_percent = $margin;
                        
                        if ($this->filterMargin === 'low' && $margin >= 10) {
                            return null;
                        }
                        if ($this->filterMargin === 'high' && $margin < 20) {
                            return null;
                        }
                    } else {
                        return null;
                    }
                    
                    return $shipment;
                })->filter();
            }
            
            // Get vendors
            $vendors = Vendor::whereNull('deleted_at')
                ->orderBy('name')
                ->get();
            
            // Get stats
            $stats = $this->getStats();
            
            // Get COA accounts
            $expenseAccounts = Account::where(function($q) {
                $q->where('type', 'beban_operasional')
                  ->orWhere('type', 'beban_pokok')
                  ->orWhere('type', 'beban_lain')
                  ->orWhere('code', 'like', '5%')
                  ->orWhere('code', 'like', '6%');
            })->orderBy('code')->get();
                
            $cashAccounts = Account::where(function($q) {
                $q->where('type', 'kas_bank')
                  ->orWhere('code', 'like', '11%');
            })->orderBy('code')->get();
            
            return view('livewire.admin.job-costing-manager', compact(
                'shipments', 
                'vendors', 
                'stats',
                'expenseAccounts',
                'cashAccounts'
            ))->layout('layouts.admin');
            
        } catch (Exception $e) {
            Log::error('Error in JobCostingManager render: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
            
            return view('livewire.admin.job-costing-manager', [
                'shipments' => collect(),
                'vendors' => collect(),
                'stats' => $this->getStats(),
                'expenseAccounts' => collect(),
                'cashAccounts' => collect(),
            ])->layout('layouts.admin');
        }
    }

    // =========================================
    // MODAL MANAGEMENT
    // =========================================
    
    public function manageCosts($shipmentId)
    {
        try {
            $this->shipmentIdForCost = $shipmentId;
            $this->selectedShipment = Shipment::with([
                'jobCosts.vendor', 
                'jobCosts.account', 
                'invoices',
                'customer'
            ])->findOrFail($shipmentId);
            
            $this->resetInput();
            $this->isModalOpen = true;
        } catch (Exception $e) {
            Log::error('Error opening cost management modal: ' . $e->getMessage());
            session()->flash('error', 'Shipment tidak ditemukan.');
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->selectedShipment = null;
        $this->resetInput();
        $this->closePreview();
        $this->editMode = false;
        $this->editingCostId = null;
    }

    private function resetInput()
    {
        $this->vendor_id = '';
        $this->description = '';
        $this->amount = '';
        $this->payment_proof = null;
        $this->status = 'unpaid';
        $this->date_paid = null;
        // Reset ke default Biaya Operasional (5101)
        $biayaOperasional = Account::where("code", "5101")->first();
        $this->coa_id = $biayaOperasional ? $biayaOperasional->id : null;
        
        // Reset ke default Kas Kecil (1102)
        $kasKecil = Account::where("code", "1102")->first();
        $this->credit_account_id = $kasKecil ? $kasKecil->id : null;
        
        $this->editMode = false;
        $this->editingCostId = null;
        
        $this->resetValidation();
    }

    // =========================================
    // PREVIEW BUKTI BAYAR
    // =========================================
    
    public function previewProof($costId)
    {
        try {
            $cost = JobCost::findOrFail($costId);
            
            if (!$cost->proof_file) {
                session()->flash('error', 'Bukti bayar tidak ditemukan.');
                return;
            }
            
            // Check if file exists
            if (!Storage::disk('public')->exists($cost->proof_file)) {
                session()->flash('error', 'File bukti bayar tidak ditemukan di server.');
                return;
            }
            
            $extension = strtolower(pathinfo($cost->proof_file, PATHINFO_EXTENSION));
            
            $this->previewCostId = $costId;
            $this->previewUrl = Storage::url($cost->proof_file);
            $this->previewType = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? 'image' : 'pdf';
            $this->showPreviewModal = true;
        } catch (Exception $e) {
            Log::error('Error previewing proof: ' . $e->getMessage());
            session()->flash('error', 'Gagal menampilkan bukti bayar.');
        }
    }
    
    public function closePreview()
    {
        $this->showPreviewModal = false;
        $this->previewUrl = null;
        $this->previewType = null;
        $this->previewCostId = null;
    }

    public function downloadProof($costId)
    {
        try {
            $cost = JobCost::findOrFail($costId);
            
            if (!$cost->proof_file || !Storage::disk('public')->exists($cost->proof_file)) {
                session()->flash('error', 'File tidak ditemukan.');
                return;
            }
            
            return Storage::disk('public')->download($cost->proof_file);
        } catch (Exception $e) {
            Log::error('Error downloading proof: ' . $e->getMessage());
            session()->flash('error', 'Gagal mengunduh file.');
        }
    }

    // =========================================
    // SAVE/UPDATE JOB COST
    // =========================================
    
    public function saveCost()
    {
        // Convert empty vendor_id to null before validation
        if (empty($this->vendor_id)) {
            $this->vendor_id = null;
        }

        $this->validate();

        try {
            DB::transaction(function () {
                $proofPath = null;
                
                // Upload bukti bayar jika ada
                if ($this->payment_proof) {
                    $extension = $this->payment_proof->getClientOriginalExtension();
                    $fileName = 'cost_' . time() . '_' . uniqid() . '.' . $extension;
                    $proofPath = $this->payment_proof->storeAs('job-costs', $fileName, 'public');
                }

                $data = [
                    'shipment_id' => $this->shipmentIdForCost,
                    'vendor_id' => $this->vendor_id ?: null,
                    'description' => $this->description,
                    'amount' => $this->amount,
                    'status' => $this->status,
                    'coa_id' => $this->coa_id ?: null,
                    'date_paid' => $this->status === 'paid' ? ($this->date_paid ?: now()) : null,
                    'created_by' => auth()->id(),
                ];
                
                // Tambahkan proof_path jika ada file baru
                if ($proofPath) {
                    $data['proof_file'] = $proofPath;
                }
                
                if ($this->editMode && $this->editingCostId) {
                    // UPDATE existing cost
                    $jobCost = JobCost::findOrFail($this->editingCostId);
                    
                    // Delete old proof file if new one uploaded
                    if ($proofPath && $jobCost->proof_file) {
                        Storage::disk('public')->delete($jobCost->proof_file);
                    }
                    
                    $jobCost->update($data);
                    $message = 'Biaya berhasil diperbarui.';
                } else {
                    // CREATE new cost
                    $jobCost = JobCost::create($data);
                    $message = 'Biaya berhasil ditambahkan.';
                }

                // Buat journal entry otomatis jika status PAID dan ada COA
                // DISABLED: Pembayaran harus lewat Simple Cashier
                //                 if ($this->status === 'paid' && $this->coa_id && $this->credit_account_id) {
                // DISABLED: Pembayaran harus lewat Simple Cashier
                //                     $this->createJournalEntry($jobCost);
                // DISABLED: Pembayaran harus lewat Simple Cashier
                //                 }
                
                session()->flash('message', $message);
            });

            // Refresh data
            $this->selectedShipment = Shipment::with([
                'jobCosts.vendor', 
                'jobCosts.account', 
                'invoices'
            ])->find($this->shipmentIdForCost);
            
            $this->resetInput();
            
        } catch (Exception $e) {
            Log::error('Error saving job cost: ' . $e->getMessage());
            session()->flash('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    // =========================================
    // EDIT JOB COST
    // =========================================
    
    public function editCost($costId)
    {
        try {
            $cost = JobCost::findOrFail($costId);
            
            $this->editMode = true;
            $this->editingCostId = $costId;
            $this->vendor_id = $cost->vendor_id;
            $this->description = $cost->description;
            $this->amount = $cost->amount;
            $this->status = $cost->status;
            $this->date_paid = $cost->date_paid ? $cost->date_paid->format('Y-m-d') : null;
            $this->coa_id = $cost->coa_id;
            
            // Don't load existing file into payment_proof (it's for new uploads only)
            // But we can show preview if needed
            
        } catch (Exception $e) {
            Log::error('Error loading cost for edit: ' . $e->getMessage());
            session()->flash('error', 'Gagal memuat data biaya.');
        }
    }

    public function cancelEdit()
    {
        $this->resetInput();
    }

    // =========================================
    // CREATE JOURNAL ENTRY
    // =========================================
    
    private function createJournalEntry(JobCost $jobCost)
    {
        try {
            $debitAccount = Account::find($this->coa_id);
            $creditAccount = Account::find($this->credit_account_id);
            
            if (!$debitAccount || !$creditAccount) {
                Log::warning('Cannot create journal: Account not found', [
                    'debit_account_id' => $this->coa_id,
                    'credit_account_id' => $this->credit_account_id
                ]);
                return;
            }
            
            // Generate nomor jurnal
            $date = now()->format('Ymd');
            $count = Journal::whereDate('created_at', today())->count() + 1;
            $journalNumber = "JV-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            // Buat journal header
            $journal = Journal::create([
                'journal_number' => $journalNumber,
                'transaction_date' => $jobCost->date_paid ?: now(),
                'description' => "Payment: {$jobCost->description}",
                'reference_no' => "JC-{$jobCost->id}",
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);
            
            // Debit: Akun Biaya
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'note' => $jobCost->description,
                'debit' => $jobCost->amount,
                'credit' => 0,
            ]);
            
            // Credit: Akun Kas/Bank
            JournalItem::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccount->id,
                'note' => 'Payment to ' . ($jobCost->vendor->name ?? 'Vendor'),
                'debit' => 0,
                'credit' => $jobCost->amount,
            ]);
            
            // Update job cost dengan journal_id
            $jobCost->update(['journal_id' => $journal->id]);
            
            Log::info('Auto Journal Created', [
                'journal_id' => $journal->id,
                'journal_number' => $journalNumber,
                'job_cost_id' => $jobCost->id,
                'amount' => $jobCost->amount
            ]);
            
        } catch (Exception $e) {
            Log::error('Error creating journal entry: ' . $e->getMessage());
            // Don't throw exception, just log it
        }
    }

    // =========================================
    // DELETE JOB COST
    // =========================================
    
    public function confirmDelete($costId)
    {
        $this->costToDelete = $costId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete()
    {
        $this->costToDelete = null;
        $this->showDeleteConfirm = false;
    }

    public function deleteCost($costId = null)
    {
        
        // Support direct call with ID or via confirmDelete
        if ($costId) {
            $this->costToDelete = $costId;
        }
        if (!$this->costToDelete) {
            return;
        }

        try {
            DB::transaction(function () {
                $cost = JobCost::findOrFail($this->costToDelete);
                
                // Hapus journal entry jika ada
                if ($cost->journal_id) {
                    $journal = Journal::find($cost->journal_id);
                    if ($journal) {
                        $journal->items()->delete();
                        $journal->delete();
                    }
                }
                
                // Hapus file bukti jika ada
                if ($cost->proof_file && Storage::disk('public')->exists($cost->proof_file)) {
                    Storage::disk('public')->delete($cost->proof_file);
                }
                
                $cost->delete();
                
                session()->flash('message', 'Biaya berhasil dihapus.');
            });

            // Refresh data
            $this->selectedShipment = Shipment::with([
                'jobCosts.vendor', 
                'jobCosts.account', 
                'invoices'
            ])->find($this->shipmentIdForCost);
            
            $this->cancelDelete();
            
        } catch (Exception $e) {
            Log::error('Error deleting job cost: ' . $e->getMessage());
            session()->flash('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function handleCostDeleted()
    {
        $this->selectedShipment = Shipment::with([
            'jobCosts.vendor', 
            'jobCosts.account', 
            'invoices'
        ])->find($this->shipmentIdForCost);
    }

    // =========================================
    // TOGGLE STATUS (PAID/UNPAID)
    // =========================================
    
    public function toggleStatus($costId)
    {
        try {
            $cost = JobCost::findOrFail($costId);
            
            DB::transaction(function () use ($cost) {
                $newStatus = $cost->status === 'paid' ? 'unpaid' : 'paid';
                
                $updateData = [
                    'status' => $newStatus,
                    'date_paid' => $newStatus === 'paid' ? now() : null,
                ];
                
                $cost->update($updateData);
                
                // Jika berubah ke PAID dan belum ada journal, buat journal otomatis
                if ($newStatus === 'paid' && !$cost->journal_id && $cost->coa_id) {
                    // Set credit account for journal creation
                    if (!$this->credit_account_id) {
                        $kasKecil = Account::where('code', '1102')->first();
                        $this->credit_account_id = $kasKecil ? $kasKecil->id : null;
                    }
                    
                    $this->coa_id = $cost->coa_id;
                    $this->createJournalEntry($cost);
                    
                    $message = 'Status berhasil diubah ke PAID. Journal entry dibuat otomatis.';
                } else {
                    $message = 'Status berhasil diubah ke ' . strtoupper($newStatus) . '.';
                }
                
                session()->flash('message', $message);
            });

            $this->selectedShipment = Shipment::with([
                'jobCosts.vendor', 
                'jobCosts.account', 
                'invoices'
            ])->find($this->shipmentIdForCost);
            
        } catch (Exception $e) {
            Log::error('Error toggling status: ' . $e->getMessage());
            session()->flash('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    // =========================================
    // EXPORT FUNCTIONS (Optional)
    // =========================================
    
    public function exportCosts($shipmentId)
    {
        // TODO: Implement export to Excel/PDF
        session()->flash('info', 'Fitur export akan segera tersedia.');
    }

    // =========================================
    // HELPER METHODS
    // =========================================
    
    public function calculateMargin($shipment)
    {
        $revenue = $shipment->invoices->sum('grand_total');
        $cost = $shipment->jobCosts->sum('amount');
        
        if ($revenue == 0) {
            return 0;
        }
        
        return (($revenue - $cost) / $revenue) * 100;
    }

    public function formatCurrency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    // =========================================
    // VENDOR RATING METHODS
    // =========================================
    
    public function openRatingModal($vendorId, $jobCostId = null)
    {
        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            session()->flash('error', 'Vendor tidak ditemukan');
            return;
        }
        
        $this->ratingVendorId = $vendorId;
        $this->ratingVendorName = $vendor->name;
        $this->ratingJobCostId = $jobCostId;
        $this->vendorRating = 5;
        $this->ratingNotes = "";
        $this->showRatingModal = true;
    }
    
    public function closeRatingModal()
    {
        $this->showRatingModal = false;
        $this->ratingVendorId = null;
        $this->ratingVendorName = "";
        $this->ratingJobCostId = null;
        $this->vendorRating = 5;
        $this->ratingNotes = "";
    }
    
    public function submitRating()
    {
        if (!$this->ratingVendorId) {
            session()->flash('error', 'Vendor tidak valid');
            return;
        }
        
        try {
            // Simpan rating
            VendorRating::create([
                'vendor_id' => $this->ratingVendorId,
                'shipment_id' => $this->shipmentIdForCost,
                'job_cost_id' => $this->ratingJobCostId,
                'rating' => $this->vendorRating,
                'criteria' => 'overall',
                'notes' => $this->ratingNotes,
                'rated_by' => auth()->id(),
            ]);
            
            // Recalculate vendor score
            $vendor = Vendor::find($this->ratingVendorId);
            if ($vendor) {
                $vendor->calculateScore();
            }
            
            session()->flash('message', 'Rating vendor berhasil disimpan! ⭐');
            $this->closeRatingModal();
            
        } catch (Exception $e) {
            Log::error('Error saving vendor rating: ' . $e->getMessage());
            session()->flash('error', 'Gagal menyimpan rating: ' . $e->getMessage());
        }
    }
    
    public function skipRating()
    {
        $this->closeRatingModal();
        session()->flash('message', 'Rating dilewati.');
    }
}