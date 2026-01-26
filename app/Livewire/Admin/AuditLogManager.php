<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AuditLogManager extends Component
{
    use WithPagination;
    
    public $search = '';
    public $filterUser = '';
    public $filterModule = '';
    public $filterAction = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $perPage = 20;

    // Modal detail
    public $showDetailModal = false;
    public $selectedLog = null;

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterUser() { $this->resetPage(); }
    public function updatingFilterModule() { $this->resetPage(); }
    public function updatingFilterAction() { $this->resetPage(); }

    public function getStats()
    {
        return [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'users_active' => ActivityLog::distinct('user_name')->count('user_name'),
            'creates' => ActivityLog::where('action', 'CREATE')->count(),
            'updates' => ActivityLog::where('action', 'like', '%UPDATE%')->count(),
            'deletes' => ActivityLog::where('action', 'DELETE')->count(),
        ];
    }

    public function getFilterOptions()
    {
        return [
            'users' => ActivityLog::distinct()->pluck('user_name')->filter()->sort()->values(),
            'modules' => ActivityLog::distinct()->pluck('module')->filter()->sort()->values(),
            'actions' => ActivityLog::distinct()->pluck('action')->filter()->sort()->values(),
        ];
    }

    /**
     * Show detail modal with comparison
     */
    public function viewDetail($logId)
    {
        $this->selectedLog = ActivityLog::find($logId);
        $this->showDetailModal = true;
    }

    /**
     * Close detail modal
     */
    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    /**
     * Export to Excel
     */
    public function exportExcel()
    {
        $query = $this->getFilteredQuery();
        $logs = $query->get();

        $filename = 'audit-logs-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Waktu', 'User', 'Role', 'Module', 'Action', 'Ref No', 'Description', 'IP Address']);
            
            // Data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name,
                    $log->role,
                    $log->module,
                    $log->action,
                    $log->target_ref,
                    $log->description,
                    $log->ip_address,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get filtered query for export
     */
    private function getFilteredQuery()
    {
        $query = ActivityLog::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('description', 'like', '%'.$this->search.'%')
                  ->orWhere('target_ref', 'like', '%'.$this->search.'%')
                  ->orWhere('user_name', 'like', '%'.$this->search.'%')
                  ->orWhere('module', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterUser) $query->where('user_name', $this->filterUser);
        if ($this->filterModule) $query->where('module', $this->filterModule);
        if ($this->filterAction) $query->where('action', $this->filterAction);
        if ($this->filterDateFrom) $query->whereDate('created_at', '>=', $this->filterDateFrom);
        if ($this->filterDateTo) $query->whereDate('created_at', '<=', $this->filterDateTo);

        return $query->latest();
    }

    public function render()
    {
        if (!in_array(Auth::user()->role, ['super_admin', 'director', 'admin', 'manager'])) abort(403);

        $query = $this->getFilteredQuery();
        $logs = $query->paginate($this->perPage);
        $stats = $this->getStats();
        $filterOptions = $this->getFilterOptions();

        return view('livewire.admin.audit-log-manager', compact('logs', 'stats', 'filterOptions'))->layout('layouts.admin');
    }

    public function clearFilters()
    {
        $this->reset(['filterUser', 'filterModule', 'filterAction', 'filterDateFrom', 'filterDateTo']);
    }
}
