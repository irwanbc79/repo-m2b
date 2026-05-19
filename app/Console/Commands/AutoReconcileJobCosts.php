<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoReconcileJobCosts extends Command
{
    protected $signature = 'reconcile:auto-jobcosts
                            {--dry-run : Tampilkan yang akan di-match tanpa eksekusi}
                            {--month= : Proses semua shipment di bulan ini (format YYYY-MM)}
                            {--shipment= : Proses satu shipment spesifik (ID)}';

    protected $description = 'Auto-reconcile Cash Transaction ke Job Cost berdasarkan shipment + amount + vendor';

    private int $matched   = 0;
    private int $skipped   = 0;
    private int $ambiguous = 0;

    public function handle()
    {
        $isDryRun   = $this->option('dry-run');
        $month      = $this->option('month');
        $shipmentId = $this->option('shipment');

        if (!$month && !$shipmentId) {
            $this->error('Harus pilih salah satu: --month=YYYY-MM atau --shipment=ID');
            return 1;
        }

        $this->info('');
        $this->info('============================================================');
        $this->info('  AUTO-RECONCILE: Cash Transaction → Job Cost');
        $this->info('============================================================');
        $this->info('  Mode: ' . ($isDryRun ? '** DRY RUN **' : 'EKSEKUSI'));
        $this->info('');

        // Ambil cash transactions OUT yang belum ter-link ke JC
        $ctQuery = DB::table('cash_transactions as ct')
            ->join('shipments', 'ct.shipment_id', '=', 'shipments.id')
            ->whereNotNull('ct.shipment_id')
            ->whereNull('ct.job_cost_id')
            ->where('ct.type', 'out');

        if ($shipmentId) {
            $ctQuery->where('ct.shipment_id', $shipmentId);
        } else {
            $ctQuery->whereRaw("DATE_FORMAT(shipments.created_at, '%Y-%m') = ?", [$month]);
        }

        $cashTrxs = $ctQuery->select(
            'ct.id as ct_id', 'ct.shipment_id', 'ct.vendor_id',
            'ct.amount', 'ct.transaction_date', 'ct.description'
        )->orderBy('ct.shipment_id')->orderBy('ct.transaction_date')->get();

        if ($cashTrxs->isEmpty()) {
            $this->info('  Tidak ada Cash Transaction yang perlu di-reconcile.');
            return 0;
        }

        $this->info("  Ditemukan {$cashTrxs->count()} Cash Transaction OUT belum ter-link.");
        $this->info('');

        // Proses per cash transaction
        foreach ($cashTrxs as $ct) {
            $this->tryMatch($ct, $isDryRun);
        }

        // Summary
        $this->info('');
        $this->info('============================================================');
        $this->info('  HASIL RECONCILE');
        $this->info('============================================================');
        $this->info("  ✓ Berhasil di-match  : {$this->matched}");
        $this->warn("  ~ Ambiguous (skip)   : {$this->ambiguous}  ← lebih dari 1 JC cocok");
        $this->warn("  ✗ Tidak ada pasangan : {$this->skipped}  ← perlu input manual di kasir");

        if ($isDryRun && $this->matched > 0) {
            $this->info('');
            $this->warn('  Jalankan tanpa --dry-run untuk eksekusi.');
        }

        $this->info('');
        return 0;
    }

    private function tryMatch(object $ct, bool $isDryRun): void
    {
        // Cari JC yang cocok di shipment yang sama
        // Strategi 1: exact match amount + vendor_id
        // Strategi 2: exact match amount + vendor null di JC
        // Strategi 3: fuzzy amount (±1%) + same vendor

        $candidates = DB::table('job_costs')
            ->where('shipment_id', $ct->shipment_id)
            ->where('status', 'unpaid')
            ->whereNull('date_paid')
            ->get();

        if ($candidates->isEmpty()) {
            $this->line("  CT#{$ct->ct_id} [{$ct->description}] Rp " . number_format($ct->amount, 0, ',', '.') . " → ✗ Tidak ada JC unpaid di shipment ini");
            $this->skipped++;
            return;
        }

        // Fase 1: exact amount + same vendor_id (keduanya non-null)
        $exactVendorMatch = $candidates->filter(fn($jc) =>
            (int)$jc->amount === (int)$ct->amount &&
            $jc->vendor_id !== null &&
            $ct->vendor_id !== null &&
            (int)$jc->vendor_id === (int)$ct->vendor_id
        );

        if ($exactVendorMatch->count() === 1) {
            $this->doMatch($ct, $exactVendorMatch->first(), 'exact+vendor', $isDryRun);
            return;
        }
        if ($exactVendorMatch->count() > 1) {
            $this->logAmbiguous($ct, $exactVendorMatch, 'exact+vendor');
            return;
        }

        // Fase 2: exact amount + JC tidak punya vendor (no vendor di JC)
        $exactNoVendorMatch = $candidates->filter(fn($jc) =>
            (int)$jc->amount === (int)$ct->amount &&
            $jc->vendor_id === null
        );

        if ($exactNoVendorMatch->count() === 1) {
            $this->doMatch($ct, $exactNoVendorMatch->first(), 'exact+no-vendor', $isDryRun);
            return;
        }
        if ($exactNoVendorMatch->count() > 1) {
            $this->logAmbiguous($ct, $exactNoVendorMatch, 'exact+no-vendor');
            return;
        }

        // Fase 3: exact amount + vendor_id sama tapi CT tidak punya vendor
        $exactCtNoVendorMatch = $candidates->filter(fn($jc) =>
            (int)$jc->amount === (int)$ct->amount &&
            $ct->vendor_id === null &&
            $jc->vendor_id !== null
        );

        if ($exactCtNoVendorMatch->count() === 1) {
            $this->doMatch($ct, $exactCtNoVendorMatch->first(), 'exact+ct-no-vendor', $isDryRun);
            return;
        }
        if ($exactCtNoVendorMatch->count() > 1) {
            $this->logAmbiguous($ct, $exactCtNoVendorMatch, 'exact+ct-no-vendor');
            return;
        }

        // Fase 4: fuzzy match ±1% amount + same vendor
        $fuzzyVendorMatch = $candidates->filter(function ($jc) use ($ct) {
            if ($jc->vendor_id === null || $ct->vendor_id === null) return false;
            if ((int)$jc->vendor_id !== (int)$ct->vendor_id) return false;
            $diff = abs((float)$jc->amount - (float)$ct->amount);
            return $diff <= (float)$ct->amount * 0.01; // ±1%
        });

        if ($fuzzyVendorMatch->count() === 1) {
            $this->doMatch($ct, $fuzzyVendorMatch->first(), 'fuzzy±1%+vendor', $isDryRun);
            return;
        }

        // Tidak ada pasangan
        $this->line("  CT#{$ct->ct_id} Rp " . number_format($ct->amount, 0, ',', '.') .
            " [{$ct->description}] → ✗ Tidak ada JC cocok");
        $this->skipped++;
    }

    private function doMatch(object $ct, object $jc, string $mode, bool $isDryRun): void
    {
        $ctAmt = number_format($ct->amount, 0, ',', '.');
        $jcAmt = number_format($jc->amount, 0, ',', '.');

        $this->info(sprintf(
            "  CT#%d Rp %s → JC#%d Rp %s [%s] [%s]",
            $ct->ct_id, $ctAmt, $jc->id, $jcAmt,
            mb_strimwidth($jc->description ?? '-', 0, 30, '..'),
            $mode
        ));

        if (!$isDryRun) {
            // Mark JC as paid
            DB::table('job_costs')->where('id', $jc->id)->update([
                'status'     => 'paid',
                'date_paid'  => $ct->transaction_date,
                'updated_at' => now(),
            ]);

            // Link CT → JC
            DB::table('cash_transactions')->where('id', $ct->ct_id)->update([
                'job_cost_id' => $jc->id,
                'updated_at'  => now(),
            ]);
        }

        $this->matched++;
    }

    private function logAmbiguous(object $ct, $matches, string $mode): void
    {
        $ctAmt = number_format($ct->amount, 0, ',', '.');
        $ids   = $matches->pluck('id')->implode(', ');
        $this->warn(sprintf(
            "  CT#%d Rp %s → ~ Ambiguous: %d JC cocok (ID: %s) [%s] — skip",
            $ct->ct_id, $ctAmt, $matches->count(), $ids, $mode
        ));
        $this->ambiguous++;
    }
}
