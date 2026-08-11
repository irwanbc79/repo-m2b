<?php

namespace App\Services;

use App\Models\HsCode;

/**
 * Satu-satunya tempat aturan bentuk HS Code.
 *
 * Staf boleh mengetik apa adanya — "60063290", "6006 32 90", "6006.32.90"
 * semuanya diterima — lalu dinormalkan ke bentuk baku BTKI yang bertitik.
 * Alasannya bukan kerapian: 60 dari 79 shipment di production sudah memakai
 * bentuk bertitik, jadi menormalkan di sini membuat nilai quotation bisa
 * disalin apa adanya ke shipment tanpa lapisan penerjemah kedua.
 *
 * Aturan ini sengaja TIDAK ditulis ulang di komponen Livewire, di aturan
 * validasi, maupun di konversi shipment. Aturan format yang disalin ke
 * beberapa tempat akan berbeda diam-diam begitu salah satunya diperbaiki.
 */
class HsCodeFormatter
{
    /** Panjang digit yang sah menurut BTKI: pos, subpos, subpos nasional. */
    public const PANJANG_SAH = [4, 6, 8];

    /** Buang semua selain angka. */
    public static function digit(?string $mentah): string
    {
        return preg_replace('/\D+/', '', (string) $mentah) ?? '';
    }

    public static function sah(?string $mentah): bool
    {
        return in_array(strlen(self::digit($mentah)), self::PANJANG_SAH, true);
    }

    /**
     * Ubah ke bentuk baku BTKI. Mengembalikan null bila panjangnya tidak sah,
     * supaya pemanggil tidak pernah menyimpan bentuk setengah jadi.
     *
     * 60063290 -> 6006.32.90
     * 600632   -> 6006.32
     * 6006     -> 6006
     */
    public static function baku(?string $mentah): ?string
    {
        $d = self::digit($mentah);

        return match (strlen($d)) {
            4       => $d,
            6       => substr($d, 0, 4) . '.' . substr($d, 4, 2),
            8       => substr($d, 0, 4) . '.' . substr($d, 4, 2) . '.' . substr($d, 6, 2),
            default => null,
        };
    }

    /** Cari satu kode persis di BTKI. null bila tidak ada. */
    public static function cariPersis(?string $mentah): ?HsCode
    {
        $baku = self::baku($mentah);

        return $baku ? HsCode::where('hs_code', $baku)->first() : null;
    }

    /**
     * Saran untuk kotak pencarian: menerima potongan kode ATAU nama barang.
     *
     * Pencarian nama dibatasi ke level 8 digit (subpos nasional) lebih dulu
     * karena itulah yang dipakai di dokumen pabean; pos 4 digit terlalu umum
     * untuk dijadikan rekomendasi dan justru menyesatkan kalau muncul di
     * urutan atas.
     */
    public static function saran(string $kata, int $batas = 8): \Illuminate\Support\Collection
    {
        $kata = trim($kata);
        if (mb_strlen($kata) < 2) {
            return collect();
        }

        $digit = self::digit($kata);

        // Diketik sebagai angka -> cocokkan sebagai awalan kode.
        if ($digit !== '' && mb_strlen($digit) >= 2 && preg_match('/^[\d\s.]+$/', $kata)) {
            $awalan = self::baku($digit) ?? $digit;

            return HsCode::query()
                ->where('hs_code', 'like', $awalan . '%')
                ->orderByRaw('LENGTH(hs_code) DESC')
                ->orderBy('hs_code')
                ->limit($batas)
                ->get();
        }

        // Diketik sebagai nama barang.
        //
        // Urutannya penting, bukan sekadar kosmetik: LIKE %kata% mencocokkan
        // apa saja, termasuk di TENGAH kata lain. Mencari "kain" pada data
        // BTKI asli memunculkan "Kokain" (2939.72.00) di urutan teratas —
        // rekomendasi seperti itu membuat staf berhenti mempercayai fitur ini.
        // Karena itu yang cocok sebagai KATA UTUH didahulukan.
        $awalKata = "{$kata}%";
        $tengah   = "% {$kata}%";

        return HsCode::query()
            ->where(fn ($q) => $q->where('description_id', 'like', "%{$kata}%")
                                 ->orWhere('description_en', 'like', "%{$kata}%"))
            ->orderByRaw(
                'CASE
                    WHEN description_id LIKE ? OR description_en LIKE ? THEN 0
                    WHEN description_id LIKE ? OR description_en LIKE ? THEN 1
                    ELSE 2
                 END',
                [$awalKata, $awalKata, $tengah, $tengah],
            )
            ->orderByRaw('CASE WHEN LENGTH(hs_code) = 10 THEN 0 ELSE 1 END')
            ->orderBy('hs_code')
            ->limit($batas)
            ->get();
    }

    /** Uraian ringkas untuk ditampilkan di daftar saran. */
    public static function uraian(HsCode $hs, string $bahasa = 'id'): string
    {
        $teks = $bahasa === 'en'
            ? ($hs->description_en ?: $hs->description_id)
            : ($hs->description_id ?: $hs->description_en);

        // Uraian BTKI banyak yang berbentuk "- - Lain-lain" (turunan dari
        // induknya) dan tidak berarti apa-apa kalau berdiri sendiri.
        return trim(preg_replace('/^[\s\-]+/', '', (string) $teks)) ?: '(tanpa uraian)';
    }
}
