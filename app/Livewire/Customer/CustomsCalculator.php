<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\TaxExchangeRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CustomsCalculator extends Component
{
    // =========================
    // INPUT DASAR & MATA UANG
    // =========================
    public $nilai_barang = 0;
    public $mata_uang = 'USD';
    public $kurs = 0;
    public $is_auto_kurs = true;

    // =========================
    // METODE CIF (DIRECT VS BREAKDOWN)
    // =========================
    public $cifMethod = 'direct'; // 'direct' atau 'breakdown'
    public $nilai_fob = 0;
    public $nilai_freight = 0;
    public $insurance_type = 'manual'; // 'manual' atau 'pmk_dn'
    public $nilai_insurance = 0;

    // =========================
    // REGULASI TARIF, STATUS & FASILITAS
    // =========================
    public $tarif_bm = 10;
    public $tarif_ppn = 11;
    public $tarif_ppnbm = 0;
    public $tarif_pph = 2.5; // Standar importir ber-API
    public $status_importir = 'api'; // 'api' (2.5%), 'non_api' (7.5%), 'no_npwp' (15.0%)
    public $skema_fasilitas = 'mfn'; // 'mfn', 'form_e' (0%), 'form_d' (0%), 'form_ak' (0%), 'kite' (0%), 'custom'
    public $has_npwp = true; // Kompatibilitas mundur

    // =========================
    // HASIL PUNGUTAN RESMI NEGARA
    // =========================
    public $nilai_pabean = 0;
    public $bayar_bm = 0;
    public $nilai_impor = 0;
    public $bayar_ppn = 0;
    public $bayar_ppnbm = 0;
    public $bayar_pph = 0;
    public $total_pungutan = 0;

    // =========================
    // PROPORSI VISUAL PUNGUTAN
    // =========================
    public $proporsi_bm = 0;
    public $proporsi_ppn = 0;
    public $proporsi_ppnbm = 0;
    public $proporsi_pph = 0;

    // =========================
    // UI & STATE
    // =========================
    public $calculationHistory = [];
    public $showHistory = false;
    public $selectedPreset = 'custom';
    public $showBreakdown = false;

    public $presets = [
        'mesin' => [
            'name' => 'Mesin & Alat Industri',
            'icon' => '⚙️',
            'bm' => 0,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'plastik_kimia' => [
            'name' => 'Bahan Baku Plastik & Kimia',
            'icon' => '🧪',
            'bm' => 5,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'elektronik' => [
            'name' => 'Elektronik & IT (HP, Laptop)',
            'icon' => '💻',
            'bm' => 0,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'pakaian' => [
            'name' => 'Pakaian & Tekstil Jadi',
            'icon' => '👔',
            'bm' => 20,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'makanan' => [
            'name' => 'Makanan & Minuman Olahan',
            'icon' => '🍱',
            'bm' => 5,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'otomotif' => [
            'name' => 'Sparepart & Aksesoris Otomotif',
            'icon' => '🛞',
            'bm' => 10,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'kosmetik' => [
            'name' => 'Kosmetik & Skincare',
            'icon' => '💄',
            'bm' => 15,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'besi_baja' => [
            'name' => 'Besi, Baja & Logam',
            'icon' => '🏗️',
            'bm' => 10,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
        'custom' => [
            'name' => 'Custom / Manual Tarif',
            'icon' => '✏️',
            'bm' => 10,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
        ],
    ];

    public $currencies = [
        'USD' => ['name' => 'US Dollar', 'flag' => '🇺🇸'],
        'CNY' => ['name' => 'Chinese Yuan', 'flag' => '🇨🇳'],
        'SGD' => ['name' => 'Singapore Dollar', 'flag' => '🇸🇬'],
        'EUR' => ['name' => 'Euro', 'flag' => '🇪🇺'],
        'JPY' => ['name' => 'Japanese Yen', 'flag' => '🇯🇵'],
        'GBP' => ['name' => 'British Pound', 'flag' => '🇬🇧'],
        'AUD' => ['name' => 'Australian Dollar', 'flag' => '🇦🇺'],
        'HKD' => ['name' => 'Hong Kong Dollar', 'flag' => '🇭🇰'],
        'MYR' => ['name' => 'Malaysian Ringgit', 'flag' => '🇲🇾'],
    ];

    public function mount()
    {
        $this->loadHistory();
        $this->syncWithTaxRate();
    }

    /**
     * SINKRONISASI KURS PAJAK KEMENKEU (CACHE 1 JAM)
     */
    public function syncWithTaxRate()
    {
        $code = strtoupper($this->mata_uang);
        $cacheKey = 'customer_tax_rate_' . $code . '_' . now()->format('Y-m-d');

        $rate = Cache::remember($cacheKey, now()->addHour(), function () use ($code) {
            return TaxExchangeRate::where('currency_code', $code)
                ->orderByDesc('valid_until')
                ->first();
        });

        if ($rate) {
            $this->kurs = (float) $rate->rate;
            $this->is_auto_kurs = true;
        } else {
            $this->kurs = ($code === 'USD') ? 16200 : 0;
            $this->is_auto_kurs = false;
        }
        
        $this->hitung();
    }

    public function updated($property)
    {
        if ($property === 'mata_uang') {
            $this->syncWithTaxRate();
            return;
        }

        if (in_array($property, ['cifMethod', 'nilai_fob', 'nilai_freight', 'insurance_type', 'nilai_insurance'])) {
            $this->recalculateBreakdownCif();
        }

        if ($property === 'status_importir') {
            $this->applyStatusImportir();
        }

        if ($property === 'skema_fasilitas') {
            $this->applySkemaFasilitas();
        }

        if ($property === 'has_npwp') {
            $this->status_importir = $this->has_npwp ? 'api' : 'no_npwp';
            $this->applyStatusImportir();
        }

        $this->hitung();
    }

    /**
     * KALKULASI FORMULA CIF INCOTERMS
     */
    public function recalculateBreakdownCif()
    {
        if ($this->cifMethod === 'breakdown') {
            $fob = max(0, (float) $this->nilai_fob);
            $freight = max(0, (float) $this->nilai_freight);

            if ($this->insurance_type === 'pmk_dn') {
                $this->nilai_insurance = round(($fob + $freight) * 0.005, 2);
            }

            $insurance = max(0, (float) $this->nilai_insurance);
            $this->nilai_barang = round($fob + $freight + $insurance, 2);
        }
    }

    public function applyStatusImportir()
    {
        switch ($this->status_importir) {
            case 'api':
                $this->tarif_pph = 2.5;
                $this->has_npwp = true;
                break;
            case 'non_api':
                $this->tarif_pph = 7.5;
                $this->has_npwp = true;
                break;
            case 'no_npwp':
                $this->tarif_pph = 15.0;
                $this->has_npwp = false;
                break;
        }
    }

    public function applySkemaFasilitas()
    {
        if (in_array($this->skema_fasilitas, ['form_e', 'form_d', 'form_ak', 'kite'])) {
            $this->tarif_bm = 0;
        } elseif ($this->skema_fasilitas === 'mfn' && isset($this->presets[$this->selectedPreset])) {
            $this->tarif_bm = $this->presets[$this->selectedPreset]['bm'];
        }
    }

    public function hitung()
    {
        $val = max(0, (float) $this->nilai_barang);
        $rate = max(0, (float) $this->kurs);
        
        // 1. Nilai Pabean (CIF × Kurs)
        $this->nilai_pabean = $val * $rate;

        // 2. Bea Masuk (Pembulatan ke ribuan terdekat standar DJBC)
        $bmRaw = $this->nilai_pabean * ($this->tarif_bm / 100);
        $this->bayar_bm = floor($bmRaw / 1000) * 1000;

        // 3. Nilai Impor (CIF + BM)
        $this->nilai_impor = $this->nilai_pabean + $this->bayar_bm;

        // 4. Pajak Dalam Rangka Impor (PDRI)
        $this->bayar_ppn   = floor(($this->nilai_impor * ($this->tarif_ppn / 100)) / 1000) * 1000;
        $this->bayar_ppnbm = floor(($this->nilai_impor * ($this->tarif_ppnbm / 100)) / 1000) * 1000;
        $this->bayar_pph   = floor(($this->nilai_impor * ($this->tarif_pph / 100)) / 1000) * 1000;

        // 5. Total Estimasi Setoran Billing DJBC
        $this->total_pungutan = $this->bayar_bm + $this->bayar_ppn + $this->bayar_ppnbm + $this->bayar_pph;

        // 6. Proporsi Visual Bar
        if ($this->total_pungutan > 0) {
            $this->proporsi_bm    = round(($this->bayar_bm / $this->total_pungutan) * 100, 1);
            $this->proporsi_ppn   = round(($this->bayar_ppn / $this->total_pungutan) * 100, 1);
            $this->proporsi_ppnbm = round(($this->bayar_ppnbm / $this->total_pungutan) * 100, 1);
            $this->proporsi_pph   = round(($this->bayar_pph / $this->total_pungutan) * 100, 1);
        } else {
            $this->proporsi_bm = $this->proporsi_ppn = $this->proporsi_ppnbm = $this->proporsi_pph = 0;
        }
    }

    public function applyPreset($key)
    {
        if (isset($this->presets[$key])) {
            $preset = $this->presets[$key];
            $this->selectedPreset = $key;
            
            // Periksa apakah sedang skema fasilitas 0%
            if (in_array($this->skema_fasilitas, ['form_e', 'form_d', 'form_ak', 'kite'])) {
                $this->tarif_bm = 0;
            } else {
                $this->tarif_bm = $preset['bm'];
            }

            $this->tarif_ppn = $preset['ppn'];
            $this->tarif_ppnbm = $preset['ppnbm'];
            
            $this->applyStatusImportir();
            $this->hitung();
        }
    }

    public function convertToBooking()
    {
        return redirect()->route('customer.shipments.create', [
            'declared_value' => $this->nilai_barang,
            'currency' => $this->mata_uang,
        ]);
    }

    public function downloadPdf()
    {
        $this->hitung();

        $data = [
            'tanggal' => now()->translatedFormat('d F Y, H:i'),
            'mata_uang' => $this->mata_uang,
            'kurs' => $this->kurs,
            'cifMethod' => $this->cifMethod,
            'nilai_fob' => $this->nilai_fob,
            'nilai_freight' => $this->nilai_freight,
            'insurance_type' => $this->insurance_type,
            'nilai_insurance' => $this->nilai_insurance,
            'nilai_barang' => $this->nilai_barang,
            'nilai_pabean' => $this->nilai_pabean,
            'status_importir' => $this->status_importir,
            'skema_fasilitas' => $this->skema_fasilitas,
            'tarif_bm' => $this->tarif_bm,
            'bayar_bm' => $this->bayar_bm,
            'nilai_impor' => $this->nilai_impor,
            'tarif_ppn' => $this->tarif_ppn,
            'bayar_ppn' => $this->bayar_ppn,
            'tarif_ppnbm' => $this->tarif_ppnbm,
            'bayar_ppnbm' => $this->bayar_ppnbm,
            'tarif_pph' => $this->tarif_pph,
            'bayar_pph' => $this->bayar_pph,
            'total_pungutan' => $this->total_pungutan,
            'selectedPreset' => $this->presets[$this->selectedPreset]['name'] ?? 'Manual',
            'customer' => Auth::user()->customer ?? null,
            'user' => Auth::user(),
        ];

        $pdf = Pdf::loadView('exports.customer-customs-calculation-pdf', $data)
            ->setPaper('a4', 'portrait');

        $filename = 'Estimasi_Pabean_M2B_' . $this->mata_uang . '_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function copyToClipboard()
    {
        $text = "*SIMULASI PUNGUTAN IMPOR - PORTAL M2B*\n";
        $text .= "PT. MORA MULTI BERKAH\n";
        $text .= "Tanggal: " . now()->format('d/m/Y H:i') . "\n";
        $text .= "----------------------------------------\n";
        $text .= "Komoditas: " . ($this->presets[$this->selectedPreset]['name'] ?? 'Umum') . "\n";
        $text .= "Nilai CIF: " . $this->mata_uang . " " . number_format($this->nilai_barang, 2, ',', '.') . "\n";
        $text .= "Kurs Pajak Kemenkeu: Rp " . number_format($this->kurs, 2, ',', '.') . "\n";
        $text .= "Nilai Pabean: Rp " . number_format($this->nilai_pabean, 0, ',', '.') . "\n";
        $text .= "Status: " . strtoupper($this->status_importir) . " | Fasilitas: " . strtoupper($this->skema_fasilitas) . "\n";
        $text .= "----------------------------------------\n";
        $text .= "RINCIAN TAGIHAN NEGARA:\n";
        $text .= "• Bea Masuk ({$this->tarif_bm}%): Rp " . number_format($this->bayar_bm, 0, ',', '.') . "\n";
        $text .= "• PPN Impor ({$this->tarif_ppn}%): Rp " . number_format($this->bayar_ppn, 0, ',', '.') . "\n";
        if ($this->tarif_ppnbm > 0) {
            $text .= "• PPnBM ({$this->tarif_ppnbm}%): Rp " . number_format($this->bayar_ppnbm, 0, ',', '.') . "\n";
        }
        $text .= "• PPh Pasal 22 ({$this->tarif_pph}%): Rp " . number_format($this->bayar_pph, 0, ',', '.') . "\n";
        $text .= "----------------------------------------\n";
        $text .= "*TOTAL BILLING: Rp " . number_format($this->total_pungutan, 0, ',', '.') . "*\n\n";
        $text .= "Hubungi M2B untuk penanganan customs clearance & booking kargo Anda.";

        $this->dispatch('copy-text', text: $text);
    }

    public function saveToHistory()
    {
        if ($this->total_pungutan <= 0 && $this->nilai_barang <= 0) return;
        
        $entry = [
            'id' => uniqid(),
            'timestamp' => now()->toDateTimeString(),
            'nilai_barang' => $this->nilai_barang,
            'mata_uang' => $this->mata_uang,
            'kurs' => $this->kurs,
            'cif_method' => $this->cifMethod,
            'preset' => $this->presets[$this->selectedPreset]['name'] ?? 'Custom',
            'tarif' => [
                'bm' => $this->tarif_bm,
                'ppn' => $this->tarif_ppn,
                'pph' => $this->tarif_pph,
            ],
            'hasil' => ['total' => $this->total_pungutan]
        ];

        array_unshift($this->calculationHistory, $entry);
        $this->calculationHistory = array_slice($this->calculationHistory, 0, 10);
        session()->put("customer_calculator_history_" . Auth::id(), $this->calculationHistory);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Simulasi berhasil disimpan ke riwayat.']);
    }

    public function loadFromHistory($id)
    {
        foreach ($this->calculationHistory as $item) {
            if ($item['id'] === $id) {
                $this->mata_uang = $item['mata_uang'] ?? 'USD';
                $this->kurs = $item['kurs'] ?? $this->kurs;
                $this->nilai_barang = $item['nilai_barang'] ?? 0;
                $this->cifMethod = $item['cif_method'] ?? 'direct';
                $this->tarif_bm = $item['tarif']['bm'] ?? 10;
                $this->tarif_ppn = $item['tarif']['ppn'] ?? 11;
                $this->tarif_pph = $item['tarif']['pph'] ?? 2.5;
                $this->hitung();
                $this->showHistory = false;
                $this->dispatch('notify', ['type' => 'success', 'message' => 'Riwayat berhasil dimuat ke kalkulator.']);
                break;
            }
        }
    }

    public function deleteFromHistory($id)
    {
        $this->calculationHistory = array_values(array_filter($this->calculationHistory, fn($item) => $item['id'] !== $id));
        session()->put("customer_calculator_history_" . Auth::id(), $this->calculationHistory);
    }

    public function clearHistory()
    {
        $this->calculationHistory = [];
        session()->forget("customer_calculator_history_" . Auth::id());
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Semua riwayat berhasil dihapus.']);
    }

    public function resetCalculator()
    {
        $this->nilai_barang = 0;
        $this->nilai_fob = 0;
        $this->nilai_freight = 0;
        $this->nilai_insurance = 0;
        $this->cifMethod = 'direct';
        $this->selectedPreset = 'custom';
        $this->skema_fasilitas = 'mfn';
        $this->status_importir = 'api';
        $this->syncWithTaxRate();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Kalkulator berhasil di-reset.']);
    }

    public function toggleHistory() { $this->showHistory = !$this->showHistory; }
    public function toggleBreakdown() { $this->showBreakdown = !$this->showBreakdown; }

    private function loadHistory()
    {
        $this->calculationHistory = session()->get("customer_calculator_history_" . Auth::id(), []);
    }

    public function render()
    {
        return view('livewire.customer.customs-calculator')->layout('layouts.customer');
    }
}