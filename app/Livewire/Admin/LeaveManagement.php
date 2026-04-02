<?php

namespace App\Livewire\Admin;

use App\Models\LeaveRequest;
use App\Services\FonnteService;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveManagement extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = 'pending';
    public string $filterType   = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }

    public function approve(int $id): void
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status !== 'pending') {
            $this->dispatch('notify', type: 'error', message: 'Pengajuan ini sudah diproses.');
            return;
        }

        $leave->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->sendWhatsAppNotification($leave);
        $this->dispatch('notify', type: 'success', message: 'Pengajuan disetujui.');
    }

    public function reject(int $id): void
    {
        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status !== 'pending') {
            $this->dispatch('notify', type: 'error', message: 'Pengajuan ini sudah diproses.');
            return;
        }

        $leave->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->sendWhatsAppNotification($leave);
        $this->dispatch('notify', type: 'error', message: 'Pengajuan ditolak.');
    }

    private function sendWhatsAppNotification(LeaveRequest $leave): void
    {
        try {
            $leave->load(['user', 'approver']);
            $employee = $leave->user;

            if (! $employee?->phone) {
                return;
            }

            $approverName = $leave->approver?->name ?? 'Admin';
            $typeLabel    = strtoupper($leave->type);

            $message = $leave->status === 'approved'
                ? "✅ *Disetujui*: Pengajuan {$typeLabel} kamu telah disetujui oleh {$approverName}."
                : "❌ *Ditolak*: Pengajuan {$typeLabel} kamu telah ditolak oleh {$approverName}.";

            app(FonnteService::class)->send($employee->phone, $message);
        } catch (\Throwable $e) {
            \Log::error('LeaveManagement WA notify error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = LeaveRequest::with(['user', 'approver'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
        }

        $leaves = $query->paginate(25);

        $counts = [
            'pending'  => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return view('livewire.admin.leave-management', compact('leaves', 'counts'))
            ->layout('layouts.admin', ['title' => 'Manajemen Cuti & Izin']);
    }
}
