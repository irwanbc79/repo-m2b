<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;

class QuotationList extends Component
{
    use WithPagination;

    public $filterStatus = '';

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

        session()->flash('success', 'Penawaran ' . $quotation->quotation_number . ' berhasil disetujui.');
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
