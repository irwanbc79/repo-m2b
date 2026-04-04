<?php

namespace App\Livewire\Admin;

use App\Models\ClientVisit;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class VisitManagement extends Component
{
    use WithPagination;

    public string $search      = '';
    public string $filterMonth = '';
    public int|string $filterUser = '';
    public ?int $previewId = null;

    protected $queryString = ['filterMonth', 'filterUser'];

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    public function updatingSearch(): void    { $this->resetPage(); }
    public function updatingFilterMonth(): void { $this->resetPage(); }
    public function updatingFilterUser(): void  { $this->resetPage(); }

    public function preview(int $id): void
    {
        $this->previewId = $this->previewId === $id ? null : $id;
    }

    public function render()
    {
        $query = ClientVisit::with('user')->orderBy('visited_at', 'desc');

        if ($this->filterMonth) {
            [$year, $month] = explode('-', $this->filterMonth);
            $query->whereYear('visited_at', $year)->whereMonth('visited_at', $month);
        }

        if ($this->filterUser) {
            $query->where('user_id', $this->filterUser);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('client_name', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%"));
            });
        }

        $visits = $query->paginate(25);

        $users = User::where('is_active', true)
            ->whereJsonDoesntContain('roles', 'customer')
            ->orderBy('name')
            ->get(['id', 'name']);

        $summary = [
            'total'    => $query->toBase()->getCountForPagination(),
            'withPhoto'=> ClientVisit::when($this->filterMonth, function ($q) {
                [$year, $month] = explode('-', $this->filterMonth);
                $q->whereYear('visited_at', $year)->whereMonth('visited_at', $month);
            })->whereNotNull('photo_path')->count(),
        ];

        return view('livewire.admin.visit-management', compact('visits', 'users', 'summary'))
            ->layout('layouts.admin', ['title' => 'Kunjungan Karyawan']);
    }
}
