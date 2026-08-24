<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use App\Models\ActivityLog;

class InvoiceList extends Component
{
    use WithPagination, WithFileUploads;
    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $filterStatus = 'all';
    
    // Upload Payment Proof
    public $showUploadModal = false;
    public $selectedInvoiceId = null;
    public $selectedInvoice = null;
    public $paymentProof = null;
    public $paymentDate = null;
    public $paymentNote = '';

    // Payment History Modal
    public $showPaymentHistoryModal = false;
    public $paymentHistoryInvoice = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function openPaymentHistory($invoiceId)
    {
        $user = Auth::user();
        if (!$user->customer) {
            return;
        }

        $this->paymentHistoryInvoice = Invoice::with(['shipment', 'payments'])
            ->where('id', $invoiceId)
            ->where('customer_id', $user->customer->id)
            ->first();

        if ($this->paymentHistoryInvoice) {
            $this->showPaymentHistoryModal = true;
        }
    }

    public function closePaymentHistory()
    {
        $this->showPaymentHistoryModal = false;
        $this->paymentHistoryInvoice = null;
    }

    public function openUploadModal($invoiceId)
    {
        $this->selectedInvoiceId = $invoiceId;
        $this->selectedInvoice = Invoice::with(['shipment', 'payments'])->find($invoiceId);
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentProof = null;
        $this->paymentNote = '';
        $this->showUploadModal = true;
    }

    public function closeUploadModal()
    {
        $this->showUploadModal = false;
        $this->selectedInvoiceId = null;
        $this->selectedInvoice = null;
        $this->paymentProof = null;
        $this->paymentDate = null;
        $this->paymentNote = '';
    }

    public function uploadPaymentProof()
    {
        $this->validate([
            'paymentProof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'paymentDate' => 'required|date',
        ], [
            'paymentProof.required' => 'Bukti pembayaran wajib diupload',
            'paymentProof.mimes' => 'Format file harus JPG, PNG, atau PDF',
            'paymentProof.max' => 'Ukuran file maksimal 5MB',
            'paymentDate.required' => 'Tanggal pembayaran wajib diisi',
        ]);

        $invoice = Invoice::findOrFail($this->selectedInvoiceId);
        $user = Auth::user();
        $customerName = $user->customer->company_name ?? $user->name;

        // Upload file
        $filename = 'payment_' . str_replace(['/', '\\'], '-', $invoice->invoice_number) . '_' . time() . '.' . $this->paymentProof->getClientOriginalExtension();
        $path = $this->paymentProof->storeAs('payment_proofs', $filename, 'public');

        // Update invoice - simpan bukti bayar
        $invoice->update([
            'payment_proof' => $path,
            'payment_date' => $this->paymentDate,
            'payment_claimed' => true,
            'claimed_at' => now(),
            'notes' => trim(($invoice->notes ?? '') . "\n[Customer Upload] " . now()->format('d/m/Y H:i') . " oleh " . $customerName . ": " . $this->paymentNote),
        ]);

        // Catat Activity Log untuk notifikasi ke Admin
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'PAYMENT_PROOF_UPLOADED',
            'model_type' => 'Invoice',
            'model_id' => $invoice->id,
            'description' => "📤 BUKTI BAYAR DITERIMA: {$customerName} mengupload bukti pembayaran untuk Invoice #{$invoice->invoice_number} (Rp " . number_format($invoice->grand_total, 0, ',', '.') . "). Menunggu verifikasi admin.",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->closeUploadModal();
        session()->flash('success', 'Bukti pembayaran berhasil diupload! Tim finance kami akan memverifikasi dalam 1x24 jam.');
    }

    public function render()
    {
        $user = Auth::user();

        if (!$user->customer) {
            return view('livewire.customer.invoice-list', [
                'invoices' => Invoice::whereRaw('1=0')->paginate(25),
                'stats' => [
                    'total' => 0,
                    'paid' => 0,
                    'unpaid' => 0,
                    'total_unpaid_amount' => 0
                ]
            ])->layout('layouts.customer');
        }

        $customerId = $user->customer->id;

        // Query invoices
        $query = Invoice::with(['shipment', 'payments'])
            ->where('customer_id', $customerId);

        // Filter by status
        if ($this->filterStatus !== 'all') {
            if ($this->filterStatus === 'unpaid') {
                $query->whereIn('status', ['unpaid', 'partial']);
            } else {
                $query->where('status', $this->filterStatus);
            }
        }

        // Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('shipment', function($q) {
                      $q->where('awb_number', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $invoices = $query->latest()->paginate(25);

        // Stats
        $statsQuery = Invoice::where('customer_id', $customerId);
        $unpaidInvoices = (clone $statsQuery)->whereIn('status', ['unpaid', 'partial'])->get();
        $totalUnpaidAmount = $unpaidInvoices->sum(function($inv) {
            if ($inv->status === 'partial') {
                return (float) $inv->remaining_balance;
            }
            return (float) $inv->grand_total;
        });

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'paid' => (clone $statsQuery)->where('status', 'paid')->count(),
            'unpaid' => $unpaidInvoices->count(),
            'total_unpaid_amount' => $totalUnpaidAmount,
        ];

        return view('livewire.customer.invoice-list', [
            'invoices' => $invoices,
            'stats' => $stats
        ])->layout('layouts.customer');
    }

    // === FAKTUR PAJAK ===
    public $previewFakturPajakModal = false;
    public $previewFakturPajakPath = null;
    public $previewFakturPajakNumber = null;

    public function requestFakturPajak($invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::where('id', $invoiceId)
            ->where('customer_id', $user->customer->id)
            ->whereIn('status', ['paid', 'partial'])
            ->first();

        if (!$invoice) {
            session()->flash('error', 'Invoice tidak ditemukan atau belum ada pembayaran.');
            return;
        }

        if ($invoice->faktur_pajak_path) {
            session()->flash('error', 'Faktur pajak sudah tersedia.');
            return;
        }

        if ($invoice->faktur_pajak_requested) {
            session()->flash('error', 'Request faktur pajak sudah dikirim sebelumnya.');
            return;
        }

        $customerName = $user->customer->company_name ?? $user->name;

        // Update invoice
        $invoice->update([
            'faktur_pajak_requested' => true,
            'faktur_pajak_requested_at' => now(),
        ]);

        // Catat Activity Log untuk notifikasi ke Admin
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'FAKTUR_PAJAK_REQUESTED',
            'model_type' => 'Invoice',
            'model_id' => $invoice->id,
            'description' => "📋 REQUEST FAKTUR PAJAK: {$customerName} meminta faktur pajak untuk Invoice #{$invoice->invoice_number} (Rp " . number_format($invoice->grand_total, 0, ',', '.') . ").",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Kirim email notifikasi ke finance
        $this->sendFakturPajakRequestEmail($invoice, $customerName);

        session()->flash('success', 'Request faktur pajak berhasil dikirim! Tim finance akan memprosesnya.');
    }

    protected function sendFakturPajakRequestEmail($invoice, $customerName)
    {
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "REQUEST FAKTUR PAJAK BARU\n\n" .
                "Customer: {$customerName}\n" .
                "Invoice: {$invoice->invoice_number}\n" .
                "Total: Rp " . number_format($invoice->grand_total, 0, ',', '.') . "\n" .
                "Tanggal Request: " . now()->format('d/m/Y H:i') . "\n\n" .
                "Silakan login ke portal admin untuk upload faktur pajak.\n" .
                url('/admin/invoices'),
                function ($message) use ($invoice) {
                    $message->to(config('mail.finance_email', 'finance@m2b.co.id'))
                        ->subject("[M2B Portal] Request Faktur Pajak - {$invoice->invoice_number}");
                }
            );
        } catch (\Exception $e) {
            \Log::error('Gagal kirim email request faktur pajak: ' . $e->getMessage());
        }
    }

    public function openFakturPajakPreview($invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::where('id', $invoiceId)
            ->where('customer_id', $user->customer->id)
            ->first();

        if ($invoice && $invoice->faktur_pajak_path) {
            $this->previewFakturPajakPath = $invoice->faktur_pajak_path;
            $this->previewFakturPajakNumber = $invoice->faktur_pajak_number;
            $this->previewFakturPajakModal = true;
        }
    }

    public function closeFakturPajakPreview()
    {
        $this->previewFakturPajakModal = false;
        $this->previewFakturPajakPath = null;
        $this->previewFakturPajakNumber = null;
    }

    // === BUKTI POTONG PPH (e-Bupot) ===
    public $showBupotModal = false;
    public $bupotInvoiceId = null;
    public $bupotInvoice = null;
    public $bupotNumber = '';
    public $bupotAmount = '';
    public $bupotDate = '';
    public $bupotFile = null;

    public function openBupotModal($invoiceId)
    {
        $user = Auth::user();
        $invoice = Invoice::where('id', $invoiceId)
            ->where('customer_id', $user->customer->id)
            ->first();

        if (!$invoice) {
            session()->flash('error', 'Invoice tidak ditemukan.');
            return;
        }

        $this->bupotInvoiceId = $invoiceId;
        $this->bupotInvoice = $invoice;
        $this->bupotNumber = $invoice->bukti_potong_number ?? '';
        $this->bupotAmount = $invoice->bukti_potong_amount ? (string)$invoice->bukti_potong_amount : ($invoice->pph_amount ? (string)$invoice->pph_amount : '');
        $this->bupotDate = $invoice->bukti_potong_date ? $invoice->bukti_potong_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->bupotFile = null;
        $this->showBupotModal = true;
    }

    public function closeBupotModal()
    {
        $this->showBupotModal = false;
        $this->bupotInvoiceId = null;
        $this->bupotInvoice = null;
        $this->bupotNumber = '';
        $this->bupotAmount = '';
        $this->bupotDate = '';
        $this->bupotFile = null;
    }

    public function uploadBupotProof()
    {
        $invoice = Invoice::findOrFail($this->bupotInvoiceId);

        $rules = [
            'bupotNumber' => 'required|string|max:100',
            'bupotAmount' => 'nullable|numeric|min:0',
            'bupotDate' => 'nullable|date',
        ];

        if (!$invoice->bukti_potong_path || $this->bupotFile) {
            $rules['bupotFile'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:5120';
        }

        $this->validate($rules, [
            'bupotNumber.required' => 'Nomor Bukti Potong wajib diisi',
            'bupotFile.required' => 'File e-Bupot (PDF/Gambar) wajib diupload',
            'bupotFile.mimes' => 'Format file harus PDF, JPG, JPEG, atau PNG',
            'bupotFile.max' => 'Ukuran file maksimal 5MB',
        ]);

        $user = Auth::user();
        $customerName = $user->customer->company_name ?? $user->name;

        $updateData = [
            'bukti_potong_number' => $this->bupotNumber,
            'bukti_potong_amount' => $this->bupotAmount ? (float)$this->bupotAmount : 0,
            'bukti_potong_date' => $this->bupotDate ?: null,
        ];

        if ($this->bupotFile) {
            $filename = 'BUPOT_' . str_replace(['/', '\\'], '-', $invoice->invoice_number) . '_' . time() . '.' . $this->bupotFile->getClientOriginalExtension();
            $path = $this->bupotFile->storeAs('bukti-potong', $filename, 'public');
            $updateData['bukti_potong_path'] = $path;
            $updateData['bukti_potong_uploaded_at'] = now();
        }

        $invoice->update($updateData);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'BUKTI_POTONG_UPLOADED',
            'model_type' => 'Invoice',
            'model_id' => $invoice->id,
            'description' => "📑 BUKTI POTONG PPH DITERIMA: {$customerName} mengupload e-Bupot #{$this->bupotNumber} untuk Invoice #{$invoice->invoice_number} (Rp " . number_format((float)$this->bupotAmount, 0, ',', '.') . ").",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->closeBupotModal();
        session()->flash('success', 'Bukti Potong PPh (e-Bupot) berhasil diupload! Terima kasih telah membantu administrasi perpajakan kami.');
    }
}
