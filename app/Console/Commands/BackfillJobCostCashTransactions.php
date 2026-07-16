<?php

namespace App\Console\Commands;

use App\Models\CashTransaction;
use App\Models\JobCost;
use App\Services\CashierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill CashTransaction + jurnal untuk JobCost berstatus 'paid' yang belum
 * terbukukan (legacy quick-pay, era sebelum auto-cash-tx job cost).
 *
 * Memakai jalur IDENTIK dengan auto-book JobCostingManager & tombol "Bukukan"
 * (CashierService::processPayment: type=out, payment_to_vendor →
 * Debit Biaya Operasional / Kredit Bank). Idempotent via job_cost_id.
 *
 * Cutoff --since (default 2026-01-01) menghormati keputusan Direktur:
 * transaksi Des 2025 DITUNDA (opening balance), tidak ikut di-backfill.
 *
 * TIDAK mengubah record JobCost/Invoice — hanya menambah cash tx + jurnal yg
 * hilang, jadi aman dijalankan tanpa mengganggu aktivitas staf di portal.
 */
class BackfillJobCostCashTransactions extends Command
{
    protected $signature = 'jobcost:backfill
        {--dry-run : Preview tanpa menulis}
        {--since=2026-01-01 : Hanya job cost dgn date_paid >= tanggal ini (Des 2025 ditunda)}
        {--yes : Lewati konfirmasi interaktif (utk SSH non-interaktif)}';

    protected $description = 'Buat CashTransaction + jurnal utk JobCost paid yang belum terbukukan (>= --since)';

    public function handle(CashierService $cashier): int
    {
        $since = $this->option('since');
        $dryRun = $this->option('dry-run');

        $bookedIds = DB::table('cash_transactions')->whereNotNull('job_cost_id')->pluck('job_cost_id');

        $targets = JobCost::with('vendor')
            ->where('status', 'paid')
            ->whereNotNull('date_paid')
            ->where('date_paid', '>=', $since)
            ->whereNotIn('id', $bookedIds)
            ->orderBy('date_paid')
            ->get();

        if ($targets->isEmpty()) {
            $this->info("Tidak ada job cost paid tanpa CashTransaction (>= {$since}). Semua sudah terbukukan.");
            return self::SUCCESS;
        }

        $this->info("Job cost akan diproses: {$targets->count()} (>= {$since})");
        $this->info('Total nilai: Rp ' . number_format((float) $targets->sum('amount'), 0, ',', '.'));

        $this->table(
            ['ID', 'Tanggal', 'Vendor', 'Deskripsi', 'Jumlah'],
            $targets->take(200)->map(fn ($c) => [
                $c->id,
                optional($c->date_paid)->format('Y-m-d'),
                \Illuminate\Support\Str::limit($c->vendor->name ?? '-', 20),
                \Illuminate\Support\Str::limit($c->description ?? '-', 24),
                number_format((float) $c->amount, 0, ',', '.'),
            ])
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] Tidak ada data yang ditulis.');
            return self::SUCCESS;
        }

        if (!$this->option('yes') && !$this->confirm("Bukukan {$targets->count()} job cost (buat CashTransaction + jurnal)?")) {
            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($targets as $cost) {
            // Idempotent: cek ulang tepat sebelum tulis
            if (CashTransaction::where('job_cost_id', $cost->id)->exists()) {
                continue;
            }

            try {
                $cashier->processPayment([
                    'type'             => 'out',
                    'category'         => 'payment_to_vendor',
                    'cost_category'    => 'shipment',
                    'counterpart_type' => 'vendor',
                    'amount'           => $cost->amount,
                    'transaction_date' => \Carbon\Carbon::parse($cost->date_paid)->format('Y-m-d'),
                    'job_cost_id'      => $cost->id,
                    'vendor_id'        => $cost->vendor_id,
                    'shipment_id'      => $cost->shipment_id,
                    'proof_file'       => $cost->proof_file,
                    'description'      => ($cost->description ?: 'Job Cost #' . $cost->id) . ' (backfill)',
                    // Audit trail akurat: pakai pembuat asli, fallback user sistem #1.
                    'created_by'       => $cost->created_by ?? 1,
                ]);
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Job cost #{$cost->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Berhasil: {$success}, Gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
