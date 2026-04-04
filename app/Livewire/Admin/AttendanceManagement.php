<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceManagement extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterDate   = '';
    public string $filterType   = '';
    public int|string $filterUser = '';

    protected $queryString = ['filterDate', 'filterUser'];

    public function mount(): void
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function updatingSearch(): void  { $this->resetPage(); }
    public function updatingFilterDate(): void  { $this->resetPage(); }
    public function updatingFilterType(): void  { $this->resetPage(); }
    public function updatingFilterUser(): void  { $this->resetPage(); }

    public function verify(int $id): void
    {
        $rec = Attendance::findOrFail($id);
        $rec->update(['verified_at' => $rec->verified_at ? null : now()]);
        $this->dispatch('notify', [
            'message' => $rec->verified_at ? 'Absensi diverifikasi.' : 'Verifikasi dibatalkan.',
            'type'    => 'success',
        ]);
    }

    public function verifyAll(): void
    {
        $query = Attendance::whereDate('created_at', $this->filterDate)->whereNull('verified_at');
        $count = $query->count();
        $query->update(['verified_at' => now()]);
        $this->dispatch('notify', [
            'message' => "$count absensi berhasil diverifikasi.",
            'type'    => 'success',
        ]);
    }

    public function render()
    {
        $query = Attendance::with(['user', 'location'])
            ->orderBy('created_at', 'desc');

        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterUser) {
            $query->where('user_id', $this->filterUser);
        }

        if ($this->search) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
        }

        $records = $query->paginate(25);

        $users = User::where('is_active', true)
            ->whereJsonDoesntContain('roles', 'customer')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Summary untuk tanggal yang dipilih
        $summary = [];
        if ($this->filterDate) {
            $summary = [
                'checkin'    => Attendance::whereDate('created_at', $this->filterDate)->where('type', 'checkin')->count(),
                'checkout'   => Attendance::whereDate('created_at', $this->filterDate)->where('type', 'checkout')->count(),
                'verified'   => Attendance::whereDate('created_at', $this->filterDate)->whereNotNull('verified_at')->count(),
                'unverified' => Attendance::whereDate('created_at', $this->filterDate)->whereNull('verified_at')->count(),
                'offline'    => Attendance::whereDate('created_at', $this->filterDate)->where('is_offline_sync', true)->count(),
            ];
        }

        return view('livewire.admin.attendance-management', compact('records', 'users', 'summary'))
            ->layout('layouts.admin', ['title' => 'Manajemen Absensi']);
    }
}
