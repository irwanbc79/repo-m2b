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
    public $filterRole = '';
    public $filterRisk = '';
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
    public function updatingFilterRole() { $this->resetPage(); }
    public function updatingFilterRisk() { $this->resetPage(); }

    public function getStats()
    {
        $todayHighRisk = ActivityLog::whereDate('created_at', today())
            ->where(function($q) {
                $q->whereIn('action', [
                    'DELETE', 'DELETE_JOURNAL', 'DELETE_USER', 'DELETE_COA', 'DELETE_COST', 
                    'VOID', 'CANCEL', 'CANCEL_INVOICE', 'UPDATE_ROLE', 'UPDATE_BANK_DETAILS', 
                    'UPDATE_BALANCE', 'LOGIN_BLOCKED', 'LOGIN_FAILED', 'VERIFY_PAYMENT'
                ])->orWhereIn('module', ['Cashier', 'JobCost', 'VendorBill', 'Payroll']);
            })->count();

        return [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'users_active' => ActivityLog::distinct('user_name')->count('user_name'),
            'creates' => ActivityLog::where('action', 'like', '%CREATE%')->count(),
            'updates' => ActivityLog::where('action', 'like', '%UPDATE%')->count(),
            'deletes' => ActivityLog::where('action', 'like', '%DELETE%')->count(),
            'today_high_risk' => $todayHighRisk,
        ];
    }

    public function getFilterOptions()
    {
        return [
            'users' => ActivityLog::distinct()->pluck('user_name')->filter()->sort()->values(),
            'modules' => ActivityLog::distinct()->pluck('module')->filter()->sort()->values(),
            'actions' => ActivityLog::distinct()->pluck('action')->filter()->sort()->values(),
            'roles' => ActivityLog::distinct()->pluck('role')->filter()->sort()->values(),
        ];
    }

    public function getTopUsers()
    {
        return ActivityLog::select('user_name', 'role', \DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('user_name', 'role')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    public function getTopModules()
    {
        return ActivityLog::select('module', \DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('module')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
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
                $q->where('user_name', 'like', '%' . $this->search . '%')
                  ->orWhere('module', 'like', '%' . $this->search . '%')
                  ->orWhere('action', 'like', '%' . $this->search . '%')
                  ->orWhere('target_ref', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterUser) $query->where('user_name', $this->filterUser);
        if ($this->filterModule) $query->where('module', $this->filterModule);
        if ($this->filterAction) $query->where('action', $this->filterAction);
        if ($this->filterRole) $query->where('role', $this->filterRole);
        if ($this->filterDateFrom) $query->whereDate('created_at', '>=', $this->filterDateFrom);
        if ($this->filterDateTo) $query->whereDate('created_at', '<=', $this->filterDateTo);

        if ($this->filterRisk) {
            $highRiskActions = [
                'DELETE', 'DELETE_JOURNAL', 'DELETE_USER', 'DELETE_COA', 'DELETE_COST', 
                'VOID', 'CANCEL', 'CANCEL_INVOICE', 'UPDATE_ROLE', 'UPDATE_BANK_DETAILS', 
                'UPDATE_BALANCE', 'LOGIN_BLOCKED', 'LOGIN_FAILED', 'VERIFY_PAYMENT'
            ];
            $highRiskModules = ['Cashier', 'JobCost', 'VendorBill', 'Payroll'];

            if ($this->filterRisk === 'high') {
                $query->where(function($q) use ($highRiskActions, $highRiskModules) {
                    $q->whereIn('action', $highRiskActions)
                      ->orWhereIn('module', $highRiskModules);
                });
            } elseif ($this->filterRisk === 'medium') {
                $query->where(function($q) use ($highRiskActions, $highRiskModules) {
                    $q->whereNotIn('action', $highRiskActions)
                      ->whereNotIn('module', $highRiskModules)
                      ->where(function($sub) {
                          $sub->where('action', 'like', '%CREATE%')
                              ->orWhere('action', 'like', '%UPDATE%')
                              ->orWhere('action', 'like', '%EDIT%')
                              ->orWhere('action', 'STATUS_CHANGE')
                              ->orWhere('action', 'CONVERT_TO_SHIPMENT');
                      });
                });
            } elseif ($this->filterRisk === 'low') {
                $query->where(function($q) use ($highRiskActions, $highRiskModules) {
                    $q->whereNotIn('action', $highRiskActions)
                      ->whereNotIn('module', $highRiskModules)
                      ->where(function($sub) {
                          $sub->whereIn('action', ['LOGIN', 'LOGIN_GOOGLE', 'LOGOUT', 'SEND_EMAIL', 'AUTO STATUS', 'SYNC_COA_BALANCES', 'VIEW', 'DOWNLOAD'])
                              ->orWhere(function($inner) {
                                  $inner->where('action', 'not like', '%CREATE%')
                                        ->where('action', 'not like', '%UPDATE%')
                                        ->where('action', 'not like', '%EDIT%');
                              });
                      });
                });
            }
        }

        return $query->latest();
    }

    public function render()
    {
        $user = Auth::user();
        $canView = $user && (
            $user->isAdminLevel() ||
            $user->hasRole(['super_admin', 'director', 'admin', 'manager', 'auditor']) ||
            $user->hasPermission('audit_log.view') ||
            in_array($user->role, ['super_admin', 'director', 'admin', 'manager', 'auditor'])
        );

        if (!$canView) abort(403);

        $query = $this->getFilteredQuery();
        $logs = $query->paginate($this->perPage);
        $stats = $this->getStats();
        $filterOptions = $this->getFilterOptions();
        $topUsers = $this->getTopUsers();
        $topModules = $this->getTopModules();

        return view('livewire.admin.audit-log-manager', compact('logs', 'stats', 'filterOptions', 'topUsers', 'topModules'))->layout('layouts.admin');
    }

    public function clearFilters()
    {
        $this->reset(['filterUser', 'filterModule', 'filterAction', 'filterRole', 'filterRisk', 'filterDateFrom', 'filterDateTo']);
    }
}
