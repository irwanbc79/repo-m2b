<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\TaxExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class KursPajakPage extends Component
{
    public array $rates = [];
    public string $period = '-';
    public string $lastUpdated = '-';
    public bool $isError = false;

    public function mount(): void
    {
        /**
         * Cache dikunci berdasarkan tanggal hari ini
         * agar tidak tercampur periode lama
         */
        $cacheKey = 'kurs_pajak_customer_active_' . now()->format('Y-m-d');

        try {
            $data = Cache::remember($cacheKey, now()->addHours(6), function () {
                return $this->loadFromDatabase();
            });

            $this->rates       = $data['rates'];
            $this->period      = $data['period'];
            $this->lastUpdated = $data['last_updated'];
            $this->isError     = false;

        } catch (\Throwable $e) {
            $this->rates       = [];
            $this->period      = '-';
            $this->lastUpdated = '-';
            $this->isError     = true;
        }
    }

    protected function loadFromDatabase(): array
    {
        $today = Carbon::today()->toDateString();

        /**
         * 🔒 AMBIL HANYA PERIODE AKTIF
         * Inilah kunci utama agar tidak double
         */
        $rows = TaxExchangeRate::whereDate('valid_from', '<=', $today)
            ->whereDate('valid_until', '>=', $today)
            ->orderByRaw("CASE WHEN currency_code = 'USD' THEN 0 ELSE 1 END")
            ->orderBy('currency_code', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'rates' => [],
                'period' => '-',
                'last_updated' => '-'
            ];
        }

        /**
         * Safety net tambahan:
         * Pastikan 1 mata uang = 1 baris
         */
        $rows = $rows->unique('currency_code')->values();

        $rates = [];

        foreach ($rows as $row) {
            $code = strtoupper($row->currency_code);

            // Mapping kode mata uang ke ISO country code (flag)
            $countryCode = match ($code) {
                'USD' => 'us', 'AUD' => 'au', 'CAD' => 'ca', 'DKK' => 'dk', 'HKD' => 'hk',
                'MYR' => 'my', 'NZD' => 'nz', 'NOK' => 'no', 'GBP' => 'gb', 'SGD' => 'sg',
                'SEK' => 'se', 'CHF' => 'ch', 'JPY' => 'jp', 'CNY' => 'cn', 'EUR' => 'eu',
                'KRW' => 'kr', 'SAR' => 'sa', 'THB' => 'th', 'BND' => 'bn', 'INR' => 'in',
                default => 'un',
            };

            // 🔧 BERSIHKAN DUPLIKASI KODE MATA UANG
            // Contoh: "Dolar Amerika Serikat (USD) USD" → "Dolar Amerika Serikat (USD)"
            $cleanName = $row->currency_name;
            
            // Hapus kode mata uang yang muncul di akhir (setelah kurung tutup)
            // Pattern: "(XXX) XXX" atau "(XXX)XXX" menjadi "(XXX)"
            $cleanName = preg_replace('/\(' . $code . '\)\s*' . $code . '\s*$/i', '(' . $code . ')', $cleanName);
            
            // Alternatif: jika formatnya "Nama XXX" tanpa kurung
            // Contoh: "Dolar Amerika Serikat USD" (jika ada yang seperti ini)
            $cleanName = preg_replace('/\s+' . $code . '\s*$/i', '', $cleanName);

            $rates[] = [
                'name'         => trim($cleanName),
                'code'         => $code,
                'value'        => number_format($row->rate, 2, ',', '.'),
                'country_code' => $countryCode,
            ];
        }

        $first = $rows->first();

        return [
            'rates' => $rates,
            'period' => sprintf(
                '%s - %s',
                Carbon::parse($first->valid_from)->format('d M Y'),
                Carbon::parse($first->valid_until)->format('d M Y')
            ),
            'last_updated' => $first->updated_at
                ? $first->updated_at->format('d M Y, H:i')
                : '-',
        ];
    }

    public function render()
    {
        return view('livewire.customer.kurs-pajak-page')
            ->layout('layouts.customer');
    }
}
