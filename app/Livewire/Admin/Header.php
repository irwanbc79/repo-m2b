<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\TaxExchangeRate;
use Illuminate\Support\Facades\Cache;

class Header extends Component
{
    public $usdRate = 0;
    public $title;

    public function mount($title = 'Admin Dashboard')
    {
        $this->title = $title;

        // Ambil kurs USD terbaru dari DB (cache ringan khusus header)
        $this->usdRate = Cache::remember(
            'admin_usd_rate',
            now()->addHours(6),
            function () {
                return TaxExchangeRate::where('currency_code', 'USD')
                    ->orderByDesc('valid_from')
                    ->value('rate') ?? 0;
            }
        );
    }

    public function render()
    {
        return view('livewire.admin.header');
    }
}
