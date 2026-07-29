<?php

namespace App\Livewire\Admin;

use App\Services\EmailStatsService;
use Livewire\Component;

/**
 * Layar Statistik Pusat Email.
 *
 * Perhitungannya sengaja ditaruh di EmailStatsService, bukan di sini, supaya
 * bisa dipakai ulang nanti (mis. briefing harian ke owner) tanpa menyeret
 * komponen Livewire.
 */
class EmailStats extends Component
{
    /** Periode yang ditinjau, dalam hari. */
    public int $periode = 30;

    protected $queryString = [
        'periode' => ['except' => 30],
    ];

    public function setPeriode(int $hari): void
    {
        // Dibatasi ke pilihan yang disediakan supaya angka periode aneh dari
        // URL tidak memaksa query berat.
        if (in_array($hari, [7, 30, 90], true)) {
            $this->periode = $hari;
        }
    }

    public function render()
    {
        $stats = app(EmailStatsService::class)->untukPeriode($this->periode);

        return view('livewire.admin.email-stats', [
            'kanal'        => $stats->kesehatanKanal(),
            'operasional'  => $stats->operasional(),
            'corong'       => $stats->corongBisnis(),
            'perluTindakan' => $stats->perluTindakan(),
        ])->layout('layouts.admin');
    }
}
