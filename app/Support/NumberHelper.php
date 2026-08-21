<?php

namespace App\Support;

class NumberHelper
{
    /**
     * Format numbers into compact representation (e.g., 1.31B, 982.55M, 3.13k, 750)
     *
     * @param float|int|null $value
     * @param int $decimals
     * @return string
     */
    public static function formatCompact($value, int $decimals = 2): string
    {
        if (is_null($value) || !is_numeric($value)) {
            return '0';
        }

        $num = (float) $value;
        $abs = abs($num);

        if ($abs >= 1_000_000_000) {
            $formatted = number_format($num / 1_000_000_000, $decimals);
            return rtrim(rtrim($formatted, '0'), '.') . 'B';
        }

        if ($abs >= 1_000_000) {
            $formatted = number_format($num / 1_000_000, $decimals);
            return rtrim(rtrim($formatted, '0'), '.') . 'M';
        }

        if ($abs >= 1_000) {
            $formatted = number_format($num / 1_000, $decimals);
            return rtrim(rtrim($formatted, '0'), '.') . 'k';
        }

        if (floor($num) == $num) {
            return number_format($num, 0);
        }

        return rtrim(rtrim(number_format($num, $decimals), '0'), '.');
    }

    /**
     * Format currency numbers into compact representation (e.g., Rp 1.31B, Rp 982.55M, Rp 15.4M)
     *
     * @param float|int|null $value
     * @param string $prefix
     * @param int $decimals
     * @return string
     */
    public static function formatCurrencyCompact($value, string $prefix = 'Rp ', int $decimals = 2): string
    {
        if (is_null($value) || !is_numeric($value) || (float) $value == 0) {
            return $prefix . '0';
        }

        return $prefix . self::formatCompactRupiah((float) $value, $decimals);
    }

    /**
     * Satuan uang gaya Indonesia: rb / jt / M (miliar) / T (triliun).
     *
     * Sebelumnya uang ikut memakai satuan Inggris k/M/B, sementara kartu
     * ringkasan tahunan di dashboard sudah memakai jt/M. Akibatnya "M" berarti
     * juta di satu kartu dan miliar di kartu sebelahnya — pada layar yang sama,
     * selisihnya seribu kali. Untuk angka uang, satuan Indonesia yang dipakai.
     */
    private static function formatCompactRupiah(float $num, int $decimals = 2): string
    {
        $abs = abs($num);

        $satuan = [
            1_000_000_000_000 => 'T',
            1_000_000_000     => 'M',
            1_000_000         => 'jt',
            1_000             => 'rb',
        ];

        foreach ($satuan as $batas => $simbol) {
            if ($abs >= $batas) {
                $angka = number_format($num / $batas, $decimals);

                return rtrim(rtrim($angka, '0'), '.') . $simbol;
            }
        }

        return floor($num) == $num
            ? number_format($num, 0)
            : rtrim(rtrim(number_format($num, $decimals), '0'), '.');
    }
}
