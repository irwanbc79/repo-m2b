<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\TaxExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomsCalculator extends Component
{
    // Mode Tampilan: 'operation' (PPJK / Operasional) vs 'tax' (Perpajakan / Tax Consultant)
    public $activeTab = 'operation';

    // Metode Input Nilai Barang: 'direct' (Langsung Nilai CIF) vs 'breakdown' (FOB + Freight + Insurance)
    public $cifMethod = 'direct';
    public $nilai_barang = 0;
    public $nilai_fob = 0;
    public $nilai_freight = 0;
    public $nilai_insurance = 0;
    public $insurance_type = 'manual'; // 'manual' atau 'pmk_dn' (0.5% dari FOB + Freight)

    // Mata Uang & Kurs Pajak (Kemenkeu)
    public $mata_uang = 'USD';
    public $kurs = 0;
    public $is_auto_kurs = true;

    // Status Importir & Skema Fasilitas
    public $status_importir = 'api'; // 'api' (2.5%), 'non_api' (7.5%), 'no_npwp' (15%)
    public $skema_fasilitas = 'mfn'; // 'mfn', 'form_e', 'form_d', 'form_ak', 'kite', 'custom'

    // Parameter Tarif (%)
    public $tarif_bm = 10;
    public $tarif_ppn = 11;
    public $tarif_ppnbm = 0;
    public $tarif_pph = 2.5;

    // Properti Hasil Kalkulasi (IDR)
    public $nilai_pabean = 0;
    public $bayar_bm = 0;
    public $nilai_impor = 0;
    public $bayar_ppn = 0;
    public $bayar_ppnbm = 0;
    public $bayar_pph = 0;
    public $total_pungutan = 0;

    // Hasil Analisis Perpajakan (Tax Analysis)
    public $beban_pokok = 0;         // Penambah Nilai Persediaan / HPP (Nilai Pabean + BM + PPnBM)
    public $kredit_pajak_ppn = 0;    // Pajak Masukan (PM) dapat dikreditkan di SPT PPN 1111
    public $kredit_pajak_pph = 0;    // Kredit Pajak PPh 22 dapat dikreditkan di SPT Tahunan 1771
    public $total_kredit_pajak = 0;  // Total yang kembali sebagai kredit pajak
    public $net_real_cost = 0;       // Beban riil kas yang tidak dapat dikreditkan (BM + PPnBM)

    // Proporsi Visual (%)
    public $proporsi_bm = 0;
    public $proporsi_ppn = 0;
    public $proporsi_pph = 0;
    public $proporsi_ppnbm = 0;

    // History & UI State
    public $calculationHistory = [];
    public $showHistory = false;
    public $selectedPreset = '';
    public $showBreakdown = false;

    // Preset Tarif Komoditas Terverifikasi
    public $presets = [
        'mesin' => [
            'name' => 'Mesin & Peralatan Industri',
            'icon' => '⚙️',
            'bm' => 0,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Mesin produksi & suku cadang modal'
        ],
        'plastik' => [
            'name' => 'Bahan Baku Plastik & Kimia',
            'icon' => '🧪',
            'bm' => 5,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Resin, polimer, zat kimia murni'
        ],
        'elektronik' => [
            'name' => 'Elektronik & IT (HP, Laptop)',
            'icon' => '💻',
            'bm' => 0,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Komputer, HP, komponen semi-konduktor'
        ],
        'tekstil' => [
            'name' => 'Pakaian & Tekstil Jadi',
            'icon' => '👔',
            'bm' => 20,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Garmen, kain jadi, aksesoris fashion'
        ],
        'makanan' => [
            'name' => 'Makanan & Minuman Olahan',
            'icon' => '🥫',
            'bm' => 5,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'F&B kemasan, bumbu olahan'
        ],
        'otomotif' => [
            'name' => 'Sparepart & Aksesoris Otomotif',
            'icon' => '🚗',
            'bm' => 10,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Komponen suku cadang mobil & motor'
        ],
        'kosmetik' => [
            'name' => 'Kosmetik & Skincare',
            'icon' => '💄',
            'bm' => 15,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Produk kecantikan & perawatan diri'
        ],
        'baja' => [
            'name' => 'Besi, Baja & Logam',
            'icon' => '🏗️',
            'bm' => 10,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Baja canai, pipa, profil konstruksi'
        ],
        'custom' => [
            'name' => 'Custom / Manual Tarif',
            'icon' => '✏️',
            'bm' => 10,
            'ppn' => 11,
            'ppnbm' => 0,
            'pph' => 2.5,
            'desc' => 'Atur tarif sesuai HS Code khusus'
        ],
    ];

    // Dukungan Multi Mata Uang
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
            $fallback = [
                'USD' => 16000, 'CNY' => 2200, 'SGD' => 12000,
                'EUR' => 17500, 'JPY' => 105,
                'MYR' => 3400,  'AUD' => 10500, 'GBP' => 20000,
            ];
            $this->kurs = $fallback[$this->mata_uang] ?? 16000;
            $this->is_auto_kurs = false;
        }
    }

    public function updatedMataUang()
    {
        $this->syncWithTaxRate();
        $this->hitung();
    }

    public function updatedCifMethod()
    {
        if ($this->cifMethod === 'breakdown') {
            if ($this->nilai_fob == 0 && $this->nilai_barang > 0) {
                $this->nilai_fob = $this->nilai_barang;
            }
        }
        $this->hitung();
    }

    public function updatedStatusImportir($value)
    {
        // Sesuaikan tarif PPh 22 sesuai status regulasi importir
        if ($value === 'api') {
            $this->tarif_pph = 2.5;
        } elseif ($value === 'non_api') {
            $this->tarif_pph = 7.5;
        } elseif ($value === 'no_npwp') {
            $this->tarif_pph = 15.0;
        }
        $this->hitung();
    }

    public function updatedSkemaFasilitas($value)
    {
        if (in_array($value, ['form_e', 'form_d', 'form_ak'])) {
            $this->tarif_bm = 0; // Tarif Preferensi FTA 0%
        } elseif ($value === 'kite') {
            $this->tarif_bm = 0;
            $this->tarif_ppn = 0;
            $this->tarif_pph = 0;
            $this->tarif_ppnbm = 0;
        } elseif ($value === 'mfn') {
            if ($this->selectedPreset && isset($this->presets[$this->selectedPreset])) {
                $this->tarif_bm = $this->presets[$this->selectedPreset]['bm'];
                $this->tarif_ppn = $this->presets[$this->selectedPreset]['ppn'];
                $this->updatedStatusImportir($this->status_importir);
            } else {
                $this->tarif_bm = 10;
                $this->tarif_ppn = 11;
            }
        }
        $this->hitung();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['nilai_fob', 'nilai_freight', 'nilai_insurance', 'insurance_type'])) {
            if ($this->cifMethod === 'breakdown') {
                if ($this->insurance_type === 'pmk_dn') {
                    // Formula standar PMK asuransi DN (0.5% dari FOB + Freight)
                    $base = ((float)$this->nilai_fob + (float)$this->nilai_freight);
                    $this->nilai_insurance = round($base * 0.005, 2);
                }
                $this->nilai_barang = (float)$this->nilai_fob + (float)$this->nilai_freight + (float)$this->nilai_insurance;
            }
        }

        $this->hitung();
    }

    public function applyPreset($presetKey)
    {
        if (isset($this->presets[$presetKey])) {
            $preset = $this->presets[$presetKey];
            $this->selectedPreset = $presetKey;
            $this->tarif_bm = $preset['bm'];
            $this->tarif_ppn = $preset['ppn'];
            $this->tarif_ppnbm = $preset['ppnbm'];
            $this->updatedStatusImportir($this->status_importir);

            // Re-apply skema jika sedang FTA
            if (in_array($this->skema_fasilitas, ['form_e', 'form_d', 'form_ak'])) {
                $this->tarif_bm = 0;
            }

            $this->hitung();
        }
    }

    public function hitung()
    {
        // 1. Tentukan Nilai Barang (CIF)
        if ($this->cifMethod === 'breakdown') {
            $val = (float)$this->nilai_fob + (float)$this->nilai_freight + (float)$this->nilai_insurance;
            $this->nilai_barang = $val;
        } else {
            $val = (float)($this->nilai_barang ?? 0);
        }

        $rate   = (float)($this->kurs ?? 0);
        $tBM    = (float)($this->tarif_bm ?? 0);
        $tPPN   = (float)($this->tarif_ppn ?? 0);
        $tPPnBM = (float)($this->tarif_ppnbm ?? 0);
        $tPPh   = (float)($this->tarif_pph ?? 0);

        // 2. Nilai Pabean (CIF dalam Rupiah, dibulatkan satuan rupiah penuh)
        $this->nilai_pabean = floor($val * $rate);

        // 3. Bea Masuk (Pembulatan ke bawah ribuan rupiah resmi DJBC)
        $bm_raw = $this->nilai_pabean * ($tBM / 100);
        $this->bayar_bm = floor($bm_raw / 1000) * 1000;

        // 4. Nilai Impor (Nilai Pabean + BM)
        $this->nilai_impor = $this->nilai_pabean + $this->bayar_bm;

        // 5. Pajak Dalam Rangka Impor (PDRI) - pembulatan ke bawah ribuan rupiah
        $this->bayar_ppn   = floor(($this->nilai_impor * ($tPPN / 100)) / 1000) * 1000;
        $this->bayar_ppnbm = floor(($this->nilai_impor * ($tPPnBM / 100)) / 1000) * 1000;
        $this->bayar_pph   = floor(($this->nilai_impor * ($tPPh / 100)) / 1000) * 1000;

        // 6. Total Billing Pungutan Pabean (SSPCP)
        $this->total_pungutan = $this->bayar_bm + $this->bayar_ppn + $this->bayar_ppnbm + $this->bayar_pph;

        // 7. Analisis Akuntansi & Perpajakan (Tax Analysis)
        $this->beban_pokok        = $this->nilai_pabean + $this->bayar_bm + $this->bayar_ppnbm;
        $this->kredit_pajak_ppn   = $this->bayar_ppn;
        $this->kredit_pajak_pph   = $this->bayar_pph;
        $this->total_kredit_pajak = $this->bayar_ppn + $this->bayar_pph;
        $this->net_real_cost      = $this->bayar_bm + $this->bayar_ppnbm;

        // 8. Proporsi Visual Pungutan
        if ($this->total_pungutan > 0) {
            $this->proporsi_bm    = round(($this->bayar_bm / $this->total_pungutan) * 100, 1);
            $this->proporsi_ppn   = round(($this->bayar_ppn / $this->total_pungutan) * 100, 1);
            $this->proporsi_pph   = round(($this->bayar_pph / $this->total_pungutan) * 100, 1);
            $this->proporsi_ppnbm = round(($this->bayar_ppnbm / $this->total_pungutan) * 100, 1);
        } else {
            $this->proporsi_bm = $this->proporsi_ppn = $this->proporsi_pph = $this->proporsi_ppnbm = 0;
        }
    }

    public function saveToHistory()
    {
        if ($this->nilai_barang <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Masukkan nilai barang terlebih dahulu!');
            return;
        }

        $history = [
            'id'              => uniqid(),
            'timestamp'       => now()->format('Y-m-d H:i:s'),
            'mata_uang'       => $this->mata_uang,
            'nilai_barang'    => $this->nilai_barang,
            'cif_method'      => $this->cifMethod,
            'fob'             => $this->nilai_fob,
            'freight'         => $this->nilai_freight,
            'insurance'       => $this->nilai_insurance,
            'kurs'            => $this->kurs,
            'status_importir' => $this->status_importir,
            'skema_fasilitas' => $this->skema_fasilitas,
            'nilai_pabean'    => $this->nilai_pabean,
            'tarif' => [
                'bm'    => $this->tarif_bm,
                'ppn'   => $this->tarif_ppn,
                'ppnbm' => $this->tarif_ppnbm,
                'pph'   => $this->tarif_pph,
            ],
            'hasil' => [
                'bm'           => $this->bayar_bm,
                'ppn'          => $this->bayar_ppn,
                'ppnbm'        => $this->bayar_ppnbm,
                'pph'          => $this->bayar_pph,
                'total'        => $this->total_pungutan,
                'kredit_pajak' => $this->total_kredit_pajak,
            ],
            'preset' => $this->selectedPreset ? ($this->presets[$this->selectedPreset]['name'] ?? 'Custom') : 'Manual',
        ];

        array_unshift($this->calculationHistory, $history);
        $this->calculationHistory = array_slice($this->calculationHistory, 0, 20);

        $this->saveHistory();
        $this->dispatch('notify', type: 'success', message: 'Kalkulasi berhasil disimpan ke riwayat!');
    }

    public function loadFromHistory($historyId)
    {
        $item = collect($this->calculationHistory)->firstWhere('id', $historyId);
        if ($item) {
            $this->mata_uang       = $item['mata_uang'] ?? 'USD';
            $this->cifMethod       = $item['cif_method'] ?? 'direct';
            $this->nilai_barang    = $item['nilai_barang'] ?? 0;
            $this->nilai_fob       = $item['fob'] ?? 0;
            $this->nilai_freight   = $item['freight'] ?? 0;
            $this->nilai_insurance = $item['insurance'] ?? 0;
            $this->kurs            = $item['kurs'] ?? $this->kurs;
            $this->status_importir = $item['status_importir'] ?? 'api';
            $this->skema_fasilitas = $item['skema_fasilitas'] ?? 'mfn';
            $this->tarif_bm        = $item['tarif']['bm'] ?? 10;
            $this->tarif_ppn       = $item['tarif']['ppn'] ?? 11;
            $this->tarif_ppnbm     = $item['tarif']['ppnbm'] ?? 0;
            $this->tarif_pph       = $item['tarif']['pph'] ?? 2.5;

            $this->hitung();
            $this->showHistory = false;
            $this->dispatch('notify', type: 'success', message: 'Data kalkulasi berhasil dimuat!');
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
        $text  = "==========================================\n";
        $text .= "🇲🇨 ESTIMASI SIMULASI PABEAN IMPOR (M2B)\n";
        $text .= "PT. MORA MULTI BERKAH | portal.m2b.co.id\n";
        $text .= "==========================================\n";
        $text .= "Tanggal     : " . now()->format('d/m/Y H:i') . " WIB\n";
        $text .= "Mata Uang   : {$this->mata_uang}\n";
        $text .= "Kurs Pajak  : Rp " . number_format($this->kurs, 2, ',', '.') . "\n";

        if ($this->cifMethod === 'breakdown') {
            $text .= "Nilai FOB   : {$this->mata_uang} " . number_format($this->nilai_fob, 2, ',', '.') . "\n";
            $text .= "Freight     : {$this->mata_uang} " . number_format($this->nilai_freight, 2, ',', '.') . "\n";
            $text .= "Asuransi    : {$this->mata_uang} " . number_format($this->nilai_insurance, 2, ',', '.') . "\n";
        }

        $text .= "Total CIF   : {$this->mata_uang} " . number_format($this->nilai_barang, 2, ',', '.') . "\n";
        $text .= "Nilai Pabean: Rp " . number_format($this->nilai_pabean, 0, ',', '.') . "\n\n";

        $statusLabel = match($this->status_importir) {
            'api'     => 'Importir API (PPh 2.5%)',
            'non_api' => 'Non-API (PPh 7.5%)',
            'no_npwp' => 'Tanpa NPWP (PPh 15%)',
            default   => 'Standar'
        };

        $fasilitasLabel = match($this->skema_fasilitas) {
            'form_e'  => 'Form E (ACFTA 0%)',
            'form_d'  => 'Form D (ATIGA 0%)',
            'form_ak' => 'Form AK (AKFTA 0%)',
            'kite'    => 'KITE / Pembebasan',
            default   => 'Normal (MFN)'
        };

        $text .= "Status Importir: {$statusLabel}\n";
        $text .= "Skema Fasilitas: {$fasilitasLabel}\n";
        $text .= "------------------------------------------\n";
        $text .= "RINCIAN PUNGUTAN BILLING (SSPCP):\n";
        $text .= "1. Bea Masuk ({$this->tarif_bm}%) : Rp " . number_format($this->bayar_bm, 0, ',', '.') . "\n";
        $text .= "2. PPN Impor ({$this->tarif_ppn}%) : Rp " . number_format($this->bayar_ppn, 0, ',', '.') . "\n";
        if ($this->tarif_ppnbm > 0) {
            $text .= "3. PPnBM ({$this->tarif_ppnbm}%)     : Rp " . number_format($this->bayar_ppnbm, 0, ',', '.') . "\n";
        }
        $text .= "4. PPh Impor ({$this->tarif_pph}%) : Rp " . number_format($this->bayar_pph, 0, ',', '.') . "\n";
        $text .= "==========================================\n";
        $text .= "TOTAL BILLING DJBC : Rp " . number_format($this->total_pungutan, 0, ',', '.') . "\n";
        $text .= "==========================================\n";
        $text .= "💡 ANALISIS PERPAJAKAN & CASH FLOW:\n";
        $text .= "• Biaya Pokok Barang (HPP) : Rp " . number_format($this->beban_pokok, 0, ',', '.') . "\n";
        $text .= "• Total Kredit Pajak (Klaim) : Rp " . number_format($this->total_kredit_pajak, 0, ',', '.') . "\n";
        $text .= "  - PPN Masukan (SPT 1111)   : Rp " . number_format($this->kredit_pajak_ppn, 0, ',', '.') . "\n";
        $text .= "  - PPh 22 Badan (SPT 1771)  : Rp " . number_format($this->kredit_pajak_pph, 0, ',', '.') . "\n";
        $text .= "\n*Catatan: Nilai akhir ditentukan resmi oleh Pejabat Bea Cukai pada dokumen PIB/SPPB.";

        $this->dispatch('copy-text', text: $text);
    }

    public function downloadPdf()
    {
        $this->hitung();

        $data = [
            'tanggal'          => now()->format('d F Y H:i') . ' WIB',
            'mata_uang'        => $this->mata_uang,
            'kurs'             => $this->kurs,
            'cifMethod'        => $this->cifMethod,
            'nilai_fob'        => $this->nilai_fob,
            'nilai_freight'    => $this->nilai_freight,
            'nilai_insurance'  => $this->nilai_insurance,
            'nilai_barang'     => $this->nilai_barang,
            'nilai_pabean'     => $this->nilai_pabean,
            'nilai_impor'      => $this->nilai_impor,
            'status_importir'  => $this->status_importir,
            'skema_fasilitas'  => $this->skema_fasilitas,
            'tarif_bm'         => $this->tarif_bm,
            'tarif_ppn'        => $this->tarif_ppn,
            'tarif_ppnbm'      => $this->tarif_ppnbm,
            'tarif_pph'        => $this->tarif_pph,
            'bayar_bm'         => $this->bayar_bm,
            'bayar_ppn'        => $this->bayar_ppn,
            'bayar_ppnbm'      => $this->bayar_ppnbm,
            'bayar_pph'        => $this->bayar_pph,
            'total_pungutan'   => $this->total_pungutan,
            'beban_pokok'      => $this->beban_pokok,
            'kredit_pajak_ppn' => $this->kredit_pajak_ppn,
            'kredit_pajak_pph' => $this->kredit_pajak_pph,
            'total_kredit_pajak' => $this->total_kredit_pajak,
            'net_real_cost'    => $this->net_real_cost,
            'selectedPreset'   => $this->selectedPreset ? ($this->presets[$this->selectedPreset]['name'] ?? 'Custom') : 'Manual',
            'generated_by'     => Auth::user()->name ?? 'Staff M2B',
        ];

        $pdf = Pdf::loadView('exports.customs-calculation-pdf', $data)->setPaper('a4', 'portrait');

        $filename = 'Estimasi-Pabean-M2B-' . $this->mata_uang . '-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename
        );
    }

    public function resetCalculator()
    {
        $this->nilai_barang    = 0;
        $this->nilai_fob       = 0;
        $this->nilai_freight   = 0;
        $this->nilai_insurance = 0;
        $this->selectedPreset  = '';
        $this->skema_fasilitas = 'mfn';
        $this->status_importir = 'api';
        $this->tarif_bm        = 10;
        $this->tarif_ppn       = 11;
        $this->tarif_ppnbm     = 0;
        $this->tarif_pph       = 2.5;

        $this->syncWithTaxRate();
        $this->hitung();
        $this->dispatch('notify', type: 'success', message: 'Kalkulator telah di-reset ke nilai awal.');
    }

    public function render()
    {
        return view('livewire.admin.customs-calculator')->layout('layouts.admin');
    }
}
