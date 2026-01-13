<?php

namespace App\Livewire\Admin\Accounting;

use Livewire\Component;

class CashierManagement extends Component
{
    public function render()
    {
        return view('livewire.admin.accounting.cashier-management')
            ->layout('layouts.admin', [
                'title' => 'Manajemen Kasir'
            ]);
    }
}
