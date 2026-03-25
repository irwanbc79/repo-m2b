<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HistoricalSnapshot;

/**
 * Import ringkasan keuangan historis dari Accurate Accounting (PT Mora Multi Berkah).
 * Data bersumber dari file Laba/Rugi yang diekspor dari Accurate.
 * Aman dijalankan berulang — upsert berdasarkan (period, period_type).
 */
class OldDataHistoricalSeeder extends Seeder
{
    public function run(): void
    {
        $monthly = $this->getMonthlyData();
        $annual  = $this->getAnnualData();

        $imported = 0;

        foreach ($monthly as $row) {
            HistoricalSnapshot::updateOrCreate(
                ['period' => $row['period'], 'period_type' => 'monthly'],
                array_merge($row, ['period_type' => 'monthly', 'source' => 'accurate'])
            );
            $imported++;
        }

        foreach ($annual as $row) {
            HistoricalSnapshot::updateOrCreate(
                ['period' => $row['period'], 'period_type' => 'annual'],
                array_merge($row, ['period_type' => 'annual', 'source' => 'accurate'])
            );
            $imported++;
        }

        $this->command->info("OldDataHistoricalSeeder: {$imported} records upserted.");
    }

    /**
     * Data bulanan 2024 dan 2025 dari file "Laba/Rugi (Multi Periode)".
     */
    private function getMonthlyData(): array
    {
        return [
            // ── 2025 ──────────────────────────────────────────────────────────
            ['period' => '2025-01', 'revenue' =>  95400000, 'cogs' =>  64757558, 'gross_profit' =>  30642442, 'net_profit' =>   -918530],
            ['period' => '2025-02', 'revenue' => 207841579, 'cogs' => 218277171, 'gross_profit' => -10435592, 'net_profit' => -39523064],
            ['period' => '2025-03', 'revenue' => 136352354, 'cogs' => 110620362, 'gross_profit' =>  25731992, 'net_profit' =>  -2346900],
            ['period' => '2025-04', 'revenue' =>  11095289, 'cogs' =>   8600920, 'gross_profit' =>   2494369, 'net_profit' => -22440033],
            ['period' => '2025-05', 'revenue' =>  40417090, 'cogs' =>  17272015, 'gross_profit' =>  23145075, 'net_profit' =>   2793217],
            ['period' => '2025-06', 'revenue' =>  14788344, 'cogs' =>  14959494, 'gross_profit' =>   -171150, 'net_profit' => -22418940],
            ['period' => '2025-07', 'revenue' =>  42707135, 'cogs' =>  27638922, 'gross_profit' =>  15068213, 'net_profit' =>  -6709847],
            ['period' => '2025-08', 'revenue' =>  48047097, 'cogs' =>   8591736, 'gross_profit' =>  39455361, 'net_profit' =>  11517219],
            ['period' => '2025-09', 'revenue' =>  13144000, 'cogs' =>  23970310, 'gross_profit' => -10826310, 'net_profit' => -35901273],
            ['period' => '2025-10', 'revenue' => 125872654, 'cogs' =>  19736703, 'gross_profit' => 106135951, 'net_profit' =>  83143264],
            ['period' => '2025-11', 'revenue' =>  32658361, 'cogs' =>   5645658, 'gross_profit' =>  27012703, 'net_profit' =>   9501118],
            ['period' => '2025-12', 'revenue' =>  66150037, 'cogs' =>  65839518, 'gross_profit' =>    310519, 'net_profit' => -65750138],

            // ── 2024 ──────────────────────────────────────────────────────────
            ['period' => '2024-01', 'revenue' =>  14732914, 'cogs' =>    150000, 'gross_profit' =>  14582914, 'net_profit' => -26845226],
            ['period' => '2024-02', 'revenue' =>   5650000, 'cogs' =>    100000, 'gross_profit' =>   5550000, 'net_profit' => -34235406],
            ['period' => '2024-03', 'revenue' =>  23346243, 'cogs' =>  31989045, 'gross_profit' =>  -8642802, 'net_profit' => -82977860],
            ['period' => '2024-04', 'revenue' =>   2400000, 'cogs' =>   3650000, 'gross_profit' =>  -1250000, 'net_profit' => -44897682],
            ['period' => '2024-05', 'revenue' =>  41667017, 'cogs' =>  30303579, 'gross_profit' =>  11363438, 'net_profit' => -28729017],
            ['period' => '2024-06', 'revenue' => 126229666, 'cogs' =>  35728355, 'gross_profit' =>  90501311, 'net_profit' =>  52831259],
            ['period' => '2024-07', 'revenue' => 107443695, 'cogs' => 167922202, 'gross_profit' => -60478507, 'net_profit' => -90288834],
            ['period' => '2024-08', 'revenue' =>  38378865, 'cogs' =>  42799296, 'gross_profit' =>  -4420431, 'net_profit' => -31283509],
            ['period' => '2024-09', 'revenue' => 149547611, 'cogs' =>  58199369, 'gross_profit' =>  91348242, 'net_profit' =>  63818610],
            ['period' => '2024-10', 'revenue' =>  20911149, 'cogs' =>  39915590, 'gross_profit' => -19004441, 'net_profit' => -43652370],
            ['period' => '2024-11', 'revenue' => 323789245, 'cogs' => 241999463, 'gross_profit' =>  81789782, 'net_profit' =>  50942091],
            ['period' => '2024-12', 'revenue' => 154152500, 'cogs' =>   1675000, 'gross_profit' => 152477500, 'net_profit' =>  68643176],
        ];
    }

    /**
     * Data tahunan 2021–2023 dari file "Laba/Rugi (Standar)".
     * Tidak ada breakdown bulanan pada file ini.
     */
    private function getAnnualData(): array
    {
        return [
            ['period' => '2021', 'revenue' =>   82323000, 'cogs' =>          0, 'gross_profit' =>   82323000, 'net_profit' => -200885718],
            ['period' => '2022', 'revenue' =>  870001300, 'cogs' => 193414010,  'gross_profit' =>  676587290, 'net_profit' =>  338022927],
            ['period' => '2023', 'revenue' => 1341084151, 'cogs' => 1195091272, 'gross_profit' =>  145992879, 'net_profit' => -278751936],
        ];
    }
}
