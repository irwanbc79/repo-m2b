<?php

namespace App\Livewire\Admin;

use App\Models\ExpenseClaim;
use App\Services\FonnteService;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseManagement extends Component
{
    use WithPagination;

    public string $search          = '';
    public string $filterStatus    = 'pending';
    public string $filterCategory  = '';

    public function updatingSearch(): void          { $this->resetPage(); }
    public function updatingFilterStatus(): void    { $this->resetPage(); }
    public function updatingFilterCategory(): void  { $this->resetPage(); }

    public function approve(int $id): void
    {
        $expense = ExpenseClaim::findOrFail($id);

        if ($expense->status !== 'pending') {
            $this->dispatch('notify', type: 'error', message: 'Klaim ini sudah diproses.');
            return;
        }

        $expense->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
        ]);

        $this->sendWhatsAppNotification($expense);
        $this->dispatch('notify', type: 'success', message: 'Klaim disetujui.');
    }

    public function reject(int $id): void
    {
        $expense = ExpenseClaim::findOrFail($id);

        if ($expense->status !== 'pending') {
            $this->dispatch('notify', type: 'error', message: 'Klaim ini sudah diproses.');
            return;
        }

        $expense->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        $this->sendWhatsAppNotification($expense);
        $this->dispatch('notify', type: 'error', message: 'Klaim ditolak.');
    }

    private function sendWhatsAppNotification(ExpenseClaim $expense): void
    {
        try {
            $expense->load(['user', 'approver']);
            $employee = $expense->user;

            if (! $employee?->phone) {
                return;
            }

            $approverName = $expense->approver?->name ?? 'Admin';
            $amount       = 'Rp ' . number_format($expense->amount, 0, ',', '.');
            $category     = ucfirst($expense->category);

            $message = $expense->status === 'approved'
                ? "✅ *Disetujui*: Klaim {$category} ({$amount}) kamu telah disetujui oleh {$approverName}."
                : "❌ *Ditolak*: Klaim {$category} ({$amount}) kamu telah ditolak oleh {$approverName}.";

            app(FonnteService::class)->send($employee->phone, $message);
        } catch (\Throwable $e) {
            \Log::error('ExpenseManagement WA notify error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = ExpenseClaim::with(['user', 'approver'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        if ($this->search) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
        }

        $expenses = $query->paginate(25);

        $counts = [
            'pending'  => ExpenseClaim::where('status', 'pending')->count(),
            'approved' => ExpenseClaim::where('status', 'approved')->count(),
            'rejected' => ExpenseClaim::where('status', 'rejected')->count(),
        ];

        $totalPending = ExpenseClaim::where('status', 'pending')->sum('amount');

        return view('livewire.admin.expense-management', compact('expenses', 'counts', 'totalPending'))
            ->layout('layouts.admin', ['title' => 'Manajemen Klaim Biaya']);
    }
}
