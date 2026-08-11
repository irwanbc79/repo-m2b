<?php

namespace App\Models;

use App\Services\HsCodeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationCommodity extends Model
{
    protected $fillable = [
        'quotation_id',
        'sort_order',
        'commodity',
        'hs_code',
        'hs_description_id',
        'hs_description_en',
        'found_in_btki',
    ];

    protected $casts = [
        'found_in_btki' => 'boolean',
        'sort_order'    => 'integer',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Isi uraian resmi dari BTKI dan tandai ketemu/tidak.
     *
     * Uraiannya DISALIN, bukan diambil lewat relasi saat cetak: data BTKI
     * bisa diimpor ulang atau diperbarui, sedangkan quotation yang sudah
     * disetujui pelanggan harus tetap bisa dicetak persis seperti semula.
     */
    public function lengkapiDariBtki(): void
    {
        $this->hs_code = HsCodeFormatter::baku($this->hs_code);

        if (! $this->hs_code) {
            $this->found_in_btki     = false;
            $this->hs_description_id = null;
            $this->hs_description_en = null;

            return;
        }

        $hs = HsCodeFormatter::cariPersis($this->hs_code);

        $this->found_in_btki     = $hs !== null;
        $this->hs_description_id = $hs ? mb_substr(HsCodeFormatter::uraian($hs, 'id'), 0, 500) : null;
        $this->hs_description_en = $hs ? mb_substr(HsCodeFormatter::uraian($hs, 'en'), 0, 500) : null;
    }

    /** Baris siap cetak: "Textile fabric — HS Code: 6006.32.90". */
    public function barisCetak(string $bahasa = 'en'): string
    {
        $nama = trim($this->commodity);

        if (! $this->hs_code) {
            return $nama;
        }

        return $nama . ' — HS Code: ' . $this->hs_code;
    }
}
