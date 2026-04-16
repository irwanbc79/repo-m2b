<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QuotationList extends Component
{
    use WithPagination, WithFileUploads;

    public $filterStatus = '';

    // Upload state
    public $uploadQuotationId = null;
    public $signedDocument = null;

    public function updatingFilterStatus() { $this->resetPage(); }

    public function approve(int $id)
    {
        $quotation = $this->findOwnQuotation($id);
        if (!$quotation || $quotation->approval_status !== 'pending') return;

        $quotation->update([
            'approval_status' => 'approved',
            'status'          => 'accepted',
            'approved_at'     => now(),
            'approved_by'     => Auth::user()->name,
            'approval_ip'     => request()->ip(),
        ]);

        session()->flash('success', 'Penawaran ' . $quotation->quotation_number . ' berhasil disetujui. Silakan upload dokumen yang sudah ditandatangani.');
    }

    public function reject(int $id)
    {
        $quotation = $this->findOwnQuotation($id);
        if (!$quotation || $quotation->approval_status !== 'pending') return;

        $quotation->update([
            'approval_status' => 'rejected',
            'status'          => 'rejected',
            'approved_at'     => now(),
            'approved_by'     => Auth::user()->name,
            'approval_ip'     => request()->ip(),
        ]);

        session()->flash('info', 'Penawaran ' . $quotation->quotation_number . ' telah ditolak.');
    }

    public function openUpload(int $id)
    {
        $quotation = $this->findOwnQuotation($id);
        if (!$quotation || $quotation->approval_status !== 'approved') return;

        $this->uploadQuotationId = $id;
        $this->signedDocument = null;
    }

    public function cancelUpload()
    {
        $this->uploadQuotationId = null;
        $this->signedDocument = null;
    }

    public function uploadSignedDocument()
    {
        $this->validate([
            'signedDocument' => 'required|file|mimes:pdf|max:5120', // 5 MB max
        ], [
            'signedDocument.required' => 'Pilih file PDF terlebih dahulu.',
            'signedDocument.mimes'    => 'File harus berformat PDF.',
            'signedDocument.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $quotation = $this->findOwnQuotation($this->uploadQuotationId);
        if (!$quotation || $quotation->approval_status !== 'approved') {
            $this->cancelUpload();
            return;
        }

        // Delete old file if exists
        if ($quotation->signed_document_path) {
            Storage::disk('public')->delete($quotation->signed_document_path);
        }

        $path = $this->signedDocument->storeAs(
            'signed-quotations',
            'QT-' . $quotation->id . '-' . now()->format('YmdHis') . '.pdf',
            'public'
        );

        $quotation->update([
            'signed_document_path' => $path,
            'signed_document_at'   => now(),
        ]);

        $this->cancelUpload();
        session()->flash('success', 'Dokumen berhasil diupload. Tim M2B akan segera memverifikasi.');
    }

    private function findOwnQuotation(int $id): ?Quotation
    {
        $customer = Auth::user()->customer;
        if (!$customer) return null;

        return Quotation::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();
    }

    public function render()
    {
        $customer = Auth::user()->customer;

        $quotations = Quotation::where('customer_id', $customer?->id)
            ->when($this->filterStatus, function ($q) {
                if ($this->filterStatus === 'expired') {
                    $q->where('valid_until', '<', now())->where('status', '!=', 'accepted');
                } else {
                    $q->where('status', $this->filterStatus);
                }
            })
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.customer.quotation-list', compact('quotations'))
            ->layout('layouts.customer', ['header' => 'Penawaran Saya']);
    }
}
