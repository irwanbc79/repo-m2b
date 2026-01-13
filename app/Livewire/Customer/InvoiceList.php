<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;

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

        // Upload file
        $filename = 'payment_' . $invoice->invoice_number . '_' . time() . '.' . $this->paymentProof->getClientOriginalExtension();
        $path = $this->paymentProof->storeAs('payment_proofs', $filename, 'public');

        // Update invoice
        $invoice->update([
            'payment_proof' => $path,
            'payment_date' => $this->paymentDate,
            'notes' => $invoice->notes . "\n[Customer Upload] " . now()->format('d/m/Y H:i') . ": " . $this->paymentNote,
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
}
