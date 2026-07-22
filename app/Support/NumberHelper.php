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

        return $prefix . self::formatCompact($value, $decimals);
    }
}
