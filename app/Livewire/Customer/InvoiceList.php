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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function openUploadModal($invoiceId)
    {
        $this->selectedInvoiceId = $invoiceId;
        $this->selectedInvoice = Invoice::with('shipment')->find($invoiceId);
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

        // Update invoice - status tetap unpaid sampai admin verifikasi
        $invoice->update([
            'payment_proof' => $path,
            'payment_date' => $this->paymentDate,
            'payment_claimed' => true,
            'claimed_at' => now(),
            'notes' => $invoice->notes . "\n[Customer Upload] " . now()->format('d/m/Y H:i') . " oleh " . $customerName . ": " . $this->paymentNote,
        ]);

        // BARU: Catat Activity Log untuk notifikasi ke Admin
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
                'invoices' => Invoice::whereRaw('1=0')->paginate(10),
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
        $query = Invoice::with(['shipment'])
            ->where('customer_id', $customerId);

        // Filter by status
        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
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

        $invoices = $query->latest()->paginate(10);

        // Stats
        $statsQuery = Invoice::where('customer_id', $customerId);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'paid' => (clone $statsQuery)->where('status', 'paid')->count(),
            'unpaid' => (clone $statsQuery)->where('status', 'unpaid')->count(),
            'total_unpaid_amount' => (clone $statsQuery)->where('status', 'unpaid')->sum('grand_total'),
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
            ->where('status', 'paid')
            ->first();

        if (!$invoice) {
            session()->flash('error', 'Invoice tidak ditemukan atau belum lunas.');
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
                function ($message) use ($invoice, $customerName) {
                    $message->to('finance@m2b.co.id')
                        ->subject("🧾 Request Faktur Pajak - {$invoice->invoice_number} - {$customerName}");
                }
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send faktur pajak request email: ' . $e->getMessage());
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
}
