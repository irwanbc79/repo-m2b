<?php

namespace App\Support;

use App\Models\TaxExchangeRate;
use Illuminate\Support\Facades\Cache;

class TaxExchangeRateHelper
{
    public static function getUsdRate(): ?float
    {
        return Cache::remember('admin_usd_rate', now()->addHours(6), function () {
            return TaxExchangeRate::where('currency_code', 'USD')
                ->orderByDesc('valid_from')
                ->value('rate');
        });
    }
}
