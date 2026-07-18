<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referensi Lartas otoritatif (snapshot INSW/INTR, direkam staf). Idea #2.
 */
class LartasReference extends Model
{
    protected $fillable = [
        'hs_code', 'trade_flow', 'is_free', 'izin_names', 'izin_code',
        'komoditi_group', 'regulation', 'description', 'keterangan',
        'doc_types', 'source', 'checked_by', 'checked_at',
    ];

    protected $casts = [
        'is_free'    => 'boolean',
        'doc_types'  => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Cari referensi untuk HS + arah. Cocok persis dulu, lalu prefix menurun
     * (10→8→6→4 digit) — INSW sering menetapkan lartas di tingkat pos/subpos.
     */
    public static function lookup(?string $hsCode, string $tradeFlow = 'import'): ?self
    {
        $hs = preg_replace('/[^0-9]/', '', (string) $hsCode);
        if ($hs === '') {
            return null;
        }

        $exact = static::where('trade_flow', $tradeFlow)->get()
            ->first(fn ($r) => preg_replace('/[^0-9]/', '', $r->hs_code) === $hs);
        if ($exact) {
            return $exact;
        }

        // Prefix fallback: bandingkan digit ternormalisasi.
        $all = static::where('trade_flow', $tradeFlow)->get()
            ->map(function ($r) {
                $r->_norm = preg_replace('/[^0-9]/', '', $r->hs_code);
                return $r;
            })
            ->filter(fn ($r) => $r->_norm !== '' && str_starts_with($hs, $r->_norm))
            ->sortByDesc(fn ($r) => strlen($r->_norm));

        return $all->first();
    }
}
