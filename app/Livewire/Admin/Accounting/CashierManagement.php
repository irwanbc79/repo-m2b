<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;

class CashierManagement extends Component
{
    public function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user || $user->hasRole(['auditor', 'konsultan_pajak'])) {
            return false;
        }

        return $user->isAdminLevel()
            || $user->hasRole(['super_admin', 'director', 'admin', 'manager', 'finance', 'staff_accounting', 'accounting', 'cashier'])
            || $user->hasPermission('cashier.view');
    }

    public function mount(): void
    {
        abort_unless($this->canAccess(), 403, 'Anda tidak memiliki akses ke manajemen kasir.');
    }

    public function render()
    {
        return view('livewire.admin.accounting.cashier-management')
            ->layout('layouts.admin', [
                'title' => 'Manajemen Kasir'
            ]);
    }
}
