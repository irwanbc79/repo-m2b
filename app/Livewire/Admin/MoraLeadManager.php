<?php

namespace App\Livewire\Admin;

use App\Models\MoraLeadNotification;
use Livewire\Component;
use Livewire\WithPagination;

class MoraLeadManager extends Component
{
    use WithPagination;

    public string $filter  = 'all';
    public string $search  = '';

    public int $totalNew;
    public int $totalHot;
    public int $totalUnread;

    protected $queryString = ['filter', 'search'];

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $this->totalNew    = MoraLeadNotification::whereNull('read_at')->count();
        $this->totalHot    = MoraLeadNotification::where('score', 'hot')->whereNull('read_at')->count();
        $this->totalUnread = MoraLeadNotification::whereNull('read_at')->count();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function markRead(int $id): void
    {
        MoraLeadNotification::find($id)?->markRead();
        $this->refreshStats();
    }

    public function markAllRead(): void
    {
        MoraLeadNotification::whereNull('read_at')->update(['read_at' => now()]);
        $this->refreshStats();
    }

    public function render()
    {
        $query = MoraLeadNotification::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('company', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            }))
            ->when($this->filter === 'unread', fn($q) => $q->whereNull('read_at'))
            ->when($this->filter === 'hot', fn($q) => $q->where('score', 'hot'))
            ->when($this->filter === 'warm', fn($q) => $q->where('score', 'warm'))
            ->latest();

        return view('livewire.admin.mora-lead-manager', [
            'leads' => $query->paginate(15),
        ])->layout('layouts.admin', ['title' => 'MORA Leads — Sales Dashboard']);
    }
}
