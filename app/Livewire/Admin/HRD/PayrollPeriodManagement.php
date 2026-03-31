<?php

namespace App\Livewire\Admin\HRD;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\Auth;

class PayrollPeriodManagement extends Component
{
    use WithPagination;

    public $perPage = 25;

    // Create modal
    public $isModalOpen = false;
    public $bulan = '';
    public $tahun = '';

    // Delete confirm
    public $showDeleteConfirm = false;
    public $deleteId = null;

    protected function rules(): array
    {
        return [
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2099',
        ];
    }

    // --- Access Control ---
    public function canCreate(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'finance']);
    }

    public function canDelete(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin']);
    }

    public function canApprove(): bool
    {
        return Auth::user()->hasRole(['admin', 'super_admin', 'director']);
    }

    // --- Actions ---
    public function openCreate(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->bulan = now()->month;
        $this->tahun = now()->year;
        $this->isModalOpen = true;
    }

    public function save(): void
    {
        abort_unless($this->canCreate(), 403);
        $this->validate();

        $exists = PayrollPeriod::where('bulan', $this->bulan)->where('tahun', $this->tahun)->exists();
        if ($exists) {
            $this->addError('bulan', 'Periode ini sudah ada.');
            return;
        }

        PayrollPeriod::create([
            'bulan'  => $this->bulan,
            'tahun'  => $this->tahun,
            'status' => 'draft',
        ]);

        $this->isModalOpen = false;
        session()->flash('success', 'Periode penggajian berhasil dibuat.');
    }

    public function approve(int $id): void
    {
        abort_unless($this->canApprove(), 403);
        $period = PayrollPeriod::findOrFail($id);

        if ($period->status !== 'draft') {
            session()->flash('error', 'Hanya periode berstatus Draft yang dapat di-approve.');
            return;
        }

        $period->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', 'Periode berhasil di-approve.');
    }

    public function markPaid(int $id): void
    {
        abort_unless($this->canApprove(), 403);
        $period = PayrollPeriod::findOrFail($id);

        if ($period->status !== 'approved') {
            session()->flash('error', 'Hanya periode berstatus Approved yang dapat ditandai Paid.');
            return;
        }

        $period->update(['status' => 'paid']);
        session()->flash('success', 'Periode ditandai sebagai Paid.');
    }

    public function confirmDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);
        $this->deleteId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        abort_unless($this->canDelete(), 403);
        PayrollPeriod::findOrFail($this->deleteId)->delete();
        $this->showDeleteConfirm = false;
        $this->deleteId = null;
        session()->flash('success', 'Periode berhasil dihapus.');
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->showDeleteConfirm = false;
    }

    public function render()
    {
        $periods = PayrollPeriod::with('approvedBy')
            ->withCount('payrollSlips')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->paginate($this->perPage);

        return view('livewire.admin.hrd.payroll-period-management', [
            'periods' => $periods,
        ])->layout('layouts.admin');
    }
}
