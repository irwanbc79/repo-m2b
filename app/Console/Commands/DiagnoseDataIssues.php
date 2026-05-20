<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseDataIssues extends Command
{
    protected $signature = 'diagnose:data-issues
                            {--fix-lini1=  : Pindahkan JC LINI 1 dari #83 ke shipment ID ini}
                            {--fix-lolo=   : Hapus JC ID duplikat LOLO di #71 (masukkan JC ID yang akan dihapus)}
                            {--dry-run     : Preview tanpa eksekusi}';

    protected $description = 'Diagnosa & fix: LINI 1 salah shipment di #83, LOLO duplikat di #71';

    public function handle()
    {
        $fixLini1  = $this->option('fix-lini1');
        $fixLolo   = $this->option('fix-lolo');
        $isDryRun  = $this->option('dry-run');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         DIAGNOSA ISSUE: #83 LINI 1 & #71 LOLO DUPLIKAT      ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // ── [1] Semua shipment PT. GUNUNG MAS SAKTI ABADI ──────────────────────
        $this->info('─── [1] SEMUA SHIPMENT PT. GUNUNG MAS SAKTI ABADI ──────────────');

        $gmShipments = DB::table('shipments as s')
            ->join('customers as c', 's.customer_id', '=', 'c.id')
            ->where('c.id', function ($q) {
                $q->select('customer_id')->from('shipments')->where('id', 83)->limit(1);
            })
            ->select(
                's.id', 's.awb_number', 's.bl_number', 's.service_type',
                's.status', 's.origin', 's.destination',
                DB::raw("DATE_FORMAT(s.created_at,'%Y-%m-%d') as ship_date")
            )
            ->orderBy('s.created_at')
            ->get();

        $headers = ['ID', 'AWB/BL', 'Tipe', 'Rute', 'Status', 'Tgl Buat'];
        $rows = $gmShipments->map(fn($r) => [
            "#{$r->id}" . ($r->id == 83 ? ' ← #83' : ''),
            $r->awb_number ?? $r->bl_number ?? '-',
            strtoupper($r->service_type ?? '-'),
            mb_strimwidth(($r->origin ?? '-') . ' → ' . ($r->destination ?? '-'), 0, 35, '..'),
            $r->status,
            $r->ship_date,
        ])->toArray();
        $this->table($headers, $rows);

        // ── [2] Job Costs shipment #83 ──────────────────────────────────────────
        $this->info('─── [2] JOB COSTS SHIPMENT #83 (IMP-260128-348) ────────────────');

        $jc83 = DB::table('job_costs as jc')
            ->leftJoin('vendors as v', 'jc.vendor_id', '=', 'v.id')
            ->where('jc.shipment_id', 83)
            ->select(
                'jc.id', 'jc.description', 'jc.amount', 'jc.status', 'jc.date_paid',
                DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
            )
            ->orderBy('jc.id')
            ->get();

        $headers = ['JC ID', 'Keterangan', 'Vendor', 'Amount', 'Status', 'Date Paid'];
        $rows = $jc83->map(fn($r) => [
            $r->id,
            mb_strimwidth($r->description ?? '-', 0, 30, '..'),
            mb_strimwidth($r->vendor_name, 0, 25, '..'),
            'Rp ' . number_format($r->amount, 0, ',', '.'),
            $r->status,
            $r->date_paid ?? '-',
        ])->toArray();
        $this->table($headers, $rows);

        $this->warn('  ⚠ LINI 1 (JC ID teratas) kemungkinan harus dipindah ke Gunung Mas Ship 5');
        $this->line('  Gunakan --fix-lini1=<shipment_id> untuk memindahkannya.');

        // ── [3] Job Costs shipment #71 ──────────────────────────────────────────
        $this->info('');
        $this->info('─── [3] JOB COSTS SHIPMENT #71 (DOM-260109-355) ────────────────');

        $jc71 = DB::table('job_costs as jc')
            ->leftJoin('vendors as v', 'jc.vendor_id', '=', 'v.id')
            ->where('jc.shipment_id', 71)
            ->select(
                'jc.id', 'jc.description', 'jc.amount', 'jc.status', 'jc.date_paid',
                'jc.created_at',
                DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
            )
            ->orderBy('jc.id')
            ->get();

        $headers = ['JC ID', 'Keterangan', 'Vendor', 'Amount', 'Status', 'Created At'];
        $rows = $jc71->map(fn($r) => [
            $r->id,
            mb_strimwidth($r->description ?? '-', 0, 30, '..'),
            mb_strimwidth($r->vendor_name, 0, 25, '..'),
            'Rp ' . number_format($r->amount, 0, ',', '.'),
            $r->status,
            substr($r->created_at, 0, 16),
        ])->toArray();
        $this->table($headers, $rows);

        // Deteksi duplikat berdasarkan keyword LOLO
        $loloEntries = $jc71->filter(fn($r) => stripos($r->description ?? '', 'lolo') !== false);
        if ($loloEntries->count() > 1) {
            $this->warn('  ⚠ Ditemukan ' . $loloEntries->count() . ' entry LOLO — kemungkinan duplikat:');
            foreach ($loloEntries as $l) {
                $this->line("      JC#{$l->id} | {$l->description} | Rp " . number_format($l->amount, 0, ',', '.') . " | {$l->vendor_name} | dibuat: " . substr($l->created_at, 0, 16));
            }
            $this->line('  Gunakan --fix-lolo=<JC_ID_yang_dihapus> untuk hapus duplikat.');
        } elseif ($loloEntries->count() === 1) {
            $this->info('  ✓ Hanya ada 1 entry LOLO — tidak ada duplikat.');
        } else {
            $this->info('  Tidak ada entry LOLO ditemukan.');
        }

        // ── CT yang ter-link ke #71 ─────────────────────────────────────────────
        $this->info('');
        $this->info('─── [4] CASH TRANSACTION OUT SHIPMENT #71 ──────────────────────');

        $ct71 = DB::table('cash_transactions as ct')
            ->leftJoin('vendors as v', 'ct.vendor_id', '=', 'v.id')
            ->where('ct.shipment_id', 71)
            ->where('ct.type', 'out')
            ->select(
                'ct.id', 'ct.transaction_date', 'ct.description', 'ct.amount',
                'ct.job_cost_id',
                DB::raw("COALESCE(v.name,'(no vendor)') as vendor_name")
            )
            ->orderBy('ct.id')
            ->get();

        $headers = ['CT ID', 'Date', 'Keterangan', 'Vendor', 'Amount', 'JC Linked'];
        $rows = $ct71->map(fn($r) => [
            $r->id,
            $r->transaction_date,
            mb_strimwidth($r->description ?? '-', 0, 28, '..'),
            mb_strimwidth($r->vendor_name, 0, 22, '..'),
            'Rp ' . number_format($r->amount, 0, ',', '.'),
            $r->job_cost_id ? "JC#{$r->job_cost_id}" : '-',
        ])->toArray();
        $this->table($headers, $rows);

        // ── Eksekusi Fix ────────────────────────────────────────────────────────
        if ($fixLini1) {
            $this->info('');
            $this->info('─── FIX: PINDAH LINI 1 KE SHIPMENT #' . $fixLini1 . ' ──────────────────');

            // JC LINI 1 di #83 = yang description-nya mengandung 'LINI 1'
            $lini1Jc = DB::table('job_costs')
                ->where('shipment_id', 83)
                ->where('description', 'like', '%LINI 1%')
                ->first();

            if (!$lini1Jc) {
                $this->error('  JC LINI 1 di shipment #83 tidak ditemukan!');
            } else {
                $this->line("  JC#{$lini1Jc->id} [{$lini1Jc->description}] Rp " . number_format($lini1Jc->amount, 0, ',', '.'));
                $this->line("  Akan dipindah dari shipment #83 → shipment #{$fixLini1}");

                if ($isDryRun) {
                    $this->warn('  ** DRY RUN — tidak ada perubahan **');
                } elseif ($this->confirm('  Lanjutkan pindah JC ini?')) {
                    DB::table('job_costs')->where('id', $lini1Jc->id)->update([
                        'shipment_id' => (int) $fixLini1,
                        'updated_at'  => now(),
                    ]);
                    $this->info("  ✓ JC#{$lini1Jc->id} berhasil dipindah ke shipment #{$fixLini1}");
                }
            }
        }

        if ($fixLolo) {
            $this->info('');
            $this->info('─── FIX: HAPUS LOLO DUPLIKAT JC#' . $fixLolo . ' ────────────────────');

            $loloJc = DB::table('job_costs')->where('id', (int) $fixLolo)->where('shipment_id', 71)->first();

            if (!$loloJc) {
                $this->error("  JC#{$fixLolo} tidak ditemukan di shipment #71!");
            } else {
                // Cek apakah ada CT yang link ke JC ini
                $linkedCt = DB::table('cash_transactions')->where('job_cost_id', $loloJc->id)->first();
                if ($linkedCt) {
                    $this->warn("  ⚠ JC#{$loloJc->id} ter-link ke CT#{$linkedCt->id}. Link akan dihapus juga.");
                }

                $this->line("  JC#{$loloJc->id} [{$loloJc->description}] Rp " . number_format($loloJc->amount, 0, ',', '.') . " status={$loloJc->status}");
                $this->warn('  Akan DIHAPUS permanen dari database.');

                if ($isDryRun) {
                    $this->warn('  ** DRY RUN — tidak ada perubahan **');
                } elseif ($this->confirm('  Yakin hapus JC ini?')) {
                    if ($linkedCt) {
                        DB::table('cash_transactions')->where('id', $linkedCt->id)->update([
                            'job_cost_id' => null,
                            'updated_at'  => now(),
                        ]);
                    }
                    DB::table('job_costs')->where('id', (int) $fixLolo)->delete();
                    $this->info("  ✓ JC#{$fixLolo} berhasil dihapus.");
                }
            }
        }

        $this->info('');
        return 0;
    }
}
