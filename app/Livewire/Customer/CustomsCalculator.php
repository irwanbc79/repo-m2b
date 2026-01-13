<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\TaxExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CustomsCalculator extends Component
{
    // =========================
    // INPUT
    // =========================
    public $nilai_barang = 0;
    public $mata_uang = 'USD';
    public $kurs = 0;
    public $is_auto_kurs = true;

    // =========================
    // TARIF (%)
    // =========================
    public $tarif_bm = 10;
    public $tarif_ppn = 11;
    public $tarif_ppnbm = 0;
    public $tarif_pph = 7.5;

    // =========================
    // HASIL
    // =========================
    public $nilai_pabean = 0;
    public $bayar_bm = 0;
    public $nilai_impor = 0;
    public $bayar_ppn = 0;
    public $bayar_ppnbm = 0;
    public $bayar_pph = 0;
    public $total_pungutan = 0;

    // =========================
    // FITUR TAMBAHAN
    // =========================
    public $calculationHistory = [];
    public $showHistory = false;
    public $selectedPreset = 'custom';
    public $showBreakdown = false;

    public $presets = [
        'elektronik' => ['name' => 'Elektronik (HP, Laptop)', 'bm' => 0, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 10],
        'pakaian'    => ['name' => 'Pakaian & Tekstil', 'bm' => 25, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
        'makanan'    => ['name' => 'Makanan & Minuman', 'bm' => 5,  'ppn' => 11, 'ppnbm' => 0, 'pph' => 2.5],
        'kosmetik'   => ['name' => 'Kosmetik & Skincare', 'bm' => 15, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
        'sepatu'     => ['name' => 'Sepatu & Tas', 'bm' => 30, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
        'custom'     => ['name' => 'Custom / Manual', 'bm' => 10, 'ppn' => 11, 'ppnbm' => 0, 'pph' => 7.5],
    ];

    public $currencies = [
        'USD' => ['name' => 'US Dollar', 'flag' => '🇺🇸'],
        'CNY' => ['name' => 'Chinese Yuan', 'flag' => '🇨🇳'],
        'SGD' => ['name' => 'Singapore Dollar', 'flag' => '🇸🇬'],
        'EUR' => ['name' => 'Euro', 'flag' => '🇪🇺'],
        'JPY' => ['name' => 'Japanese Yen', 'flag' => '🇯🇵'],
    ];

    public function mount()
    {
        $this->loadHistory();
        $this->syncWithTaxRate(); // Gunakan fungsi sinkronisasi yang lebih stabil
    }

    /**
     * SINKRONISASI KURS (MIRROR DARI ADMIN + FAILSAFE LATEST)
     * Ini kunci agar tidak kosong/blank lagi.
     */
    public function syncWithTaxRate()
    {
        $code = strtoupper($this->mata_uang);

        // 1. Ambil data kurs terbaru tanpa filter tanggal yang rumit
        $rate = TaxExchangeRate::where('currency_code', $code)
            ->orderByDesc('valid_until')
            ->first();

        if ($rate) {
            $this->kurs = (float) $rate->rate;
            $this->is_auto_kurs = true;
        } else {
            // Fallback manual jika database benar-benar kosong (Hanya untuk USD)
            $this->kurs = ($code === 'USD') ? 16200 : 0;
            $this->is_auto_kurs = false;
        }
        
        $this->hitung();
    }

    public function updated($property)
    {
        if ($property === 'mata_uang') {
            $this->syncWithTaxRate();
        } else {
            $this->hitung();
        }
    }

    public function hitung()
    {
        // Konversi paksa ke float agar tidak error
        $val = (float)$this->nilai_barang;
        $rate = (float)$this->kurs;
        
        // 1. Nilai Pabean
        $this->nilai_pabean = $val * $rate;

        // 2. Bea Masuk (Rounding bawah per ribuan standar BC)
        $bmRaw = $this->nilai_pabean * ($this->tarif_bm / 100);
        $this->bayar_bm = floor($bmRaw / 1000) * 1000;

        // 3. Nilai Impor (Dasar PDRI)
        $this->nilai_impor = $this->nilai_pabean + $this->bayar_bm;

        // 4. Pajak PDRI (Rounding bawah per ribuan)
        $this->bayar_ppn   = floor(($this->nilai_impor * ($this->tarif_ppn / 100)) / 1000) * 1000;
        $this->bayar_ppnbm = floor(($this->nilai_impor * ($this->tarif_ppnbm / 100)) / 1000) * 1000;
        $this->bayar_pph   = floor(($this->nilai_impor * ($this->tarif_pph / 100)) / 1000) * 1000;

        // 5. Total
        $this->total_pungutan = $this->bayar_bm + $this->bayar_ppn + $this->bayar_ppnbm + $this->bayar_pph;
    }

    public function applyPreset($key)
    {
        if (isset($this->presets[$key])) {
            $preset = $this->presets[$key];
            $this->tarif_bm = $preset['bm'];
            $this->tarif_ppn = $preset['ppn'];
            $this->tarif_ppnbm = $preset['ppnbm'];
            $this->tarif_pph = $preset['pph'];
            $this->selectedPreset = $key;
            $this->hitung();
        }
    }

    public function toggleHistory() { $this->showHistory = !$this->showHistory; }
    
    public function saveToHistory()
    {
        if ($this->total_pungutan <= 0) return;
        $entry = [
            'id' => uniqid(),
            'timestamp' => now()->toDateTimeString(),
            'nilai_barang' => $this->nilai_barang,
            'mata_uang' => $this->mata_uang,
            'kurs' => $this->kurs,
            'preset' => $this->presets[$this->selectedPreset]['name'] ?? 'Custom',
            'hasil' => ['total' => $this->total_pungutan]
        ];
        array_unshift($this->calculationHistory, $entry);
        $this->calculationHistory = array_slice($this->calculationHistory, 0, 10);
        session()->put("calculator_history_" . Auth::id(), $this->calculationHistory);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Simulasi disimpan.']);
    }

    public function resetCalculator()
    {
        $this->nilai_barang = 0;
        $this->selectedPreset = 'custom';
        $this->syncWithTaxRate();
    }

    private function loadHistory() { $this->calculationHistory = session()->get("calculator_history_" . Auth::id(), []); }

    public function render()
    {
        return view('livewire.customer.customs-calculator')->layout('layouts.customer');
    }
}