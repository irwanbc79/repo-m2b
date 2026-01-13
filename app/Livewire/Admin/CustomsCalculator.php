<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\TaxExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CustomsCalculator extends Component
{
    // Properti Input
    public $nilai_barang = 0;
    public $nilai_barang_formatted = '';
    public $mata_uang = 'USD';
    public $kurs = 0; 
    public $is_auto_kurs = true;
    
    // Properti Tarif (%)
    public $tarif_bm = 10;
    public $tarif_ppn = 11;
    public $tarif_ppnbm = 0;
    public $tarif_pph = 7.5;

    // Properti Hasil
    public $nilai_pabean = 0;
    public $bayar_bm = 0;
    public $nilai_impor = 0;
    public $bayar_ppn = 0;
    public $bayar_ppnbm = 0;
    public $bayar_pph = 0;
    public $total_pungutan = 0;

    // Fitur Baru: History & Preset
    public $calculationHistory = [];
    public $showHistory = false;
    public $selectedPreset = '';
    public $showBreakdown = false;
    
    // Preset Tarif berdasarkan kategori barang
    public $presets = [
        'elektronik' => ['name' => 'Elektronik (HP, Laptop)', 'bm' => 0, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 10],
        'pakaian' => ['name' => 'Pakaian & Tekstil', 'bm' => 25, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
        'makanan' => ['name' => 'Makanan & Minuman', 'bm' => 5, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 2.5],
        'kosmetik' => ['name' => 'Kosmetik & Skincare', 'bm' => 15, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
        'sepatu' => ['name' => 'Sepatu & Tas', 'bm' => 30, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
        'mobil' => ['name' => 'Kendaraan Bermotor', 'bm' => 50, 'ppn' => 11, 'ppnbm' => 30, 'pph' => 7.5],
        'jam_mewah' => ['name' => 'Jam Tangan Mewah', 'bm' => 10, 'ppn' => 11, 'ppnbm' => 20, 'pph' => 7.5],
        'buku' => ['name' => 'Buku & Media Cetak', 'bm' => 0, 'ppn' => 0, 'ppnbm' => 0, 'pph' => 0],
        'obat' => ['name' => 'Obat-obatan', 'bm' => 0, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 2.5],
        'custom' => ['name' => 'Custom / Manual', 'bm' => 10, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
    ];

    // Multi Currency Support
    public $currencies = [
        'USD' => ['name' => 'US Dollar', 'flag' => '🇺🇸', 'symbol' => '$'],
        'CNY' => ['name' => 'Chinese Yuan', 'flag' => '🇨🇳', 'symbol' => '¥'],
        'SGD' => ['name' => 'Singapore Dollar', 'flag' => '🇸🇬', 'symbol' => 'S$'],
        'EUR' => ['name' => 'Euro', 'flag' => '🇪🇺', 'symbol' => '€'],
        'JPY' => ['name' => 'Japanese Yen', 'flag' => '🇯🇵', 'symbol' => '¥'],
        'MYR' => ['name' => 'Malaysian Ringgit', 'flag' => '🇲🇾', 'symbol' => 'RM'],
        'AUD' => ['name' => 'Australian Dollar', 'flag' => '🇦🇺', 'symbol' => 'A$'],
        'GBP' => ['name' => 'British Pound', 'flag' => '🇬🇧', 'symbol' => '£'],
    ];

    public function mount()
    {
        $this->loadHistory();
        $this->syncWithTaxRate();
        $this->hitung();
    }

    public function syncWithTaxRate()
{
    $today = Carbon::today()->toDateString();
    $cacheKey = "admin_active_tax_rate_{$this->mata_uang}_{$today}";

    $rate = Cache::remember($cacheKey, now()->addHours(6), function () use ($today) {
        return TaxExchangeRate::where('currency_code', $this->mata_uang)
            ->whereDate('valid_from', '<=', $today)
            ->whereDate('valid_until', '>=', $today)
            ->orderByDesc('valid_from')
            ->first();
    });

    if ($rate) {
        $this->kurs = (float) $rate->rate;
        $this->is_auto_kurs = true;
    } else {
        // fallback aman (kalau DB kosong / data belum masuk)
        $fallback = [
            'USD' => 16000, 'CNY' => 2200, 'SGD' => 12000,
            'EUR' => 17500, 'JPY' => 105,
            'MYR' => 3400,  'AUD' => 10500, 'GBP' => 20000,
        ];

        $this->kurs = $fallback[$this->mata_uang] ?? 16000;
        $this->is_auto_kurs = false;
    }
}

    public function updatedNilaiBarangFormatted($value)
    {
        // Remove formatting dan convert ke number
        $cleanValue = preg_replace('/[^0-9.]/', '', $value);
        $this->nilai_barang = (float) $cleanValue;
        $this->hitung();
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'mata_uang') {
            $this->syncWithTaxRate();
        }
        
        if ($propertyName !== 'nilai_barang_formatted') {
            $this->hitung();
        }
    }

    public function applyPreset($presetKey)
    {
        if (isset($this->presets[$presetKey])) {
            $preset = $this->presets[$presetKey];
            $this->selectedPreset = $presetKey;
            $this->tarif_bm = $preset['bm'];
            $this->tarif_ppn = $preset['ppn'];
            $this->tarif_ppnbm = $preset['ppnbm'];
            $this->tarif_pph = $preset['pph'];
            $this->hitung();
        }
    }

    public function hitung()
    {
        $val = (float)($this->nilai_barang ?? 0);
        $rate = (float)($this->kurs ?? 0);
        $tBM = (float)($this->tarif_bm ?? 0);
        $tPPN = (float)($this->tarif_ppn ?? 0);
        $tPPnBM = (float)($this->tarif_ppnbm ?? 0);
        $tPPh = (float)($this->tarif_pph ?? 0);

        $this->nilai_pabean = $val * $rate;
        
        $bm_raw = $this->nilai_pabean * ($tBM / 100);
        $this->bayar_bm = floor($bm_raw / 1000) * 1000;

        $this->nilai_impor = $this->nilai_pabean + $this->bayar_bm;

        $this->bayar_ppn = floor(($this->nilai_impor * ($tPPN / 100)) / 1000) * 1000;
        $this->bayar_ppnbm = floor(($this->nilai_impor * ($tPPnBM / 100)) / 1000) * 1000;
        $this->bayar_pph = floor(($this->nilai_impor * ($tPPh / 100)) / 1000) * 1000;

        $this->total_pungutan = $this->bayar_bm + $this->bayar_ppn + $this->bayar_ppnbm + $this->bayar_pph;
    }

    public function saveToHistory()
    {
        if ($this->nilai_barang <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Masukkan nilai barang terlebih dahulu!');
            return;
        }

        $history = [
            'id' => uniqid(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'mata_uang' => $this->mata_uang,
            'nilai_barang' => $this->nilai_barang,
            'kurs' => $this->kurs,
            'nilai_pabean' => $this->nilai_pabean,
            'tarif' => [
                'bm' => $this->tarif_bm,
                'ppn' => $this->tarif_ppn,
                'ppnbm' => $this->tarif_ppnbm,
                'pph' => $this->tarif_pph,
            ],
            'hasil' => [
                'bm' => $this->bayar_bm,
                'ppn' => $this->bayar_ppn,
                'ppnbm' => $this->bayar_ppnbm,
                'pph' => $this->bayar_pph,
                'total' => $this->total_pungutan,
            ],
            'preset' => $this->selectedPreset ? $this->presets[$this->selectedPreset]['name'] : 'Manual',
        ];

        array_unshift($this->calculationHistory, $history);
        $this->calculationHistory = array_slice($this->calculationHistory, 0, 20); // Keep last 20
        
        $this->saveHistory();
        $this->dispatch('notify', type: 'success', message: 'Kalkulasi berhasil disimpan!');
    }

    public function loadFromHistory($historyId)
    {
        $item = collect($this->calculationHistory)->firstWhere('id', $historyId);
        if ($item) {
            $this->mata_uang = $item['mata_uang'];
            $this->nilai_barang = $item['nilai_barang'];
            $this->kurs = $item['kurs'];
            $this->tarif_bm = $item['tarif']['bm'];
            $this->tarif_ppn = $item['tarif']['ppn'];
            $this->tarif_ppnbm = $item['tarif']['ppnbm'];
            $this->tarif_pph = $item['tarif']['pph'];
            $this->hitung();
            $this->showHistory = false;
        }
    }

    public function deleteFromHistory($historyId)
    {
        $this->calculationHistory = array_values(array_filter(
            $this->calculationHistory, 
            fn($item) => $item['id'] !== $historyId
        ));
        $this->saveHistory();
    }

    public function clearHistory()
    {
        $this->calculationHistory = [];
        $this->saveHistory();
    }

    private function saveHistory()
    {
        $userId = Auth::id();
        session()->put("calculator_history_{$userId}", $this->calculationHistory);
    }

    private function loadHistory()
    {
        $userId = Auth::id();
        $this->calculationHistory = session()->get("calculator_history_{$userId}", []);
    }

    public function toggleHistory()
    {
        $this->showHistory = !$this->showHistory;
    }

    public function toggleBreakdown()
    {
        $this->showBreakdown = !$this->showBreakdown;
    }

    public function copyToClipboard()
    {
        $text = "=== SIMULASI PUNGUTAN IMPOR ===\n";
        $text .= "Tanggal: " . now()->format('d/m/Y H:i') . "\n\n";
        $text .= "NILAI BARANG:\n";
        $text .= "{$this->mata_uang} " . number_format($this->nilai_barang, 2) . "\n";
        $text .= "Kurs: Rp " . number_format($this->kurs, 2) . "\n";
        $text .= "Nilai Pabean: Rp " . number_format($this->nilai_pabean, 0) . "\n\n";
        $text .= "TARIF:\n";
        $text .= "BM: {$this->tarif_bm}% | PPN: {$this->tarif_ppn}% | PPnBM: {$this->tarif_ppnbm}% | PPh: {$this->tarif_pph}%\n\n";
        $text .= "HASIL PUNGUTAN:\n";
        $text .= "Bea Masuk: Rp " . number_format($this->bayar_bm, 0) . "\n";
        $text .= "PPN Impor: Rp " . number_format($this->bayar_ppn, 0) . "\n";
        $text .= "PPnBM: Rp " . number_format($this->bayar_ppnbm, 0) . "\n";
        $text .= "PPh Impor: Rp " . number_format($this->bayar_pph, 0) . "\n";
        $text .= "================================\n";
        $text .= "TOTAL: Rp " . number_format($this->total_pungutan, 0) . "\n";

        $this->dispatch('copyText', text: $text);
    }

    public function resetCalculator()
    {
        $this->nilai_barang = 0;
        $this->nilai_barang_formatted = '';
        $this->selectedPreset = '';
        $this->syncWithTaxRate();
        $this->hitung();
    }

    public function render()
    {
        return view('livewire.admin.customs-calculator')->layout('layouts.admin');
    }
}
