<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCashTransactionPostedStatus extends Command
{
    protected $signature = 'cashier:sync-posted-status {--dry-run : Preview saja, tidak update data}';

    protected $description = 'Sinkronkan is_posted pada cash_transactions berdasarkan status journal yang sudah posted';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Hitung berapa yang perlu diupdate
        $count = DB::table('cash_transactions as ct')
            ->join('journals as j', 'ct.journal_id', '=', 'j.id')
            ->where('j.status', 'posted')
            ->where(function ($q) {
                $q->where('ct.is_posted', false)
                  ->orWhereNull('ct.is_posted');
            })
            ->count();

        if ($count === 0) {
            $this->info('Semua cash_transactions sudah sinkron. Tidak ada yang perlu diupdate.');
            return 0;
        }

        $this->warn("Ditemukan {$count} transaksi dengan journal 'posted' tapi is_posted = false.");

        if ($dryRun) {
            $this->info('[DRY RUN] Tidak ada perubahan yang disimpan.');

            // Tampilkan sample data
            $samples = DB::table('cash_transactions as ct')
                ->join('journals as j', 'ct.journal_id', '=', 'j.id')
                ->where('j.status', 'posted')
                ->where(function ($q) {
                    $q->where('ct.is_posted', false)
                      ->orWhereNull('ct.is_posted');
                })
                ->select('ct.id', 'ct.transaction_date', 'ct.type', 'ct.amount', 'j.journal_number', 'j.posted_at')
                ->limit(10)
                ->get();

            $this->table(
                ['ID', 'Tanggal', 'Tipe', 'Jumlah', 'No Jurnal', 'Posted At'],
                $samples->map(fn($r) => [$r->id, $r->transaction_date, $r->type, number_format($r->amount), $r->journal_number, $r->posted_at])
            );

            return 0;
        }

        if (!$this->confirm("Lanjut update {$count} transaksi?")) {
            $this->info('Dibatalkan.');
            return 0;
        }

        // Update is_posted dan posted_at dari data journal
        $updated = DB::statement("
            UPDATE cash_transactions ct
            INNER JOIN journals j ON ct.journal_id = j.id
            SET ct.is_posted = 1,
                ct.posted_at = COALESCE(j.posted_at, j.updated_at, NOW())
            WHERE j.status = 'posted'
              AND (ct.is_posted = 0 OR ct.is_posted IS NULL)
        ");

        $this->info("Berhasil. {$count} transaksi telah diupdate ke is_posted = true.");

        return 0;
    }
}
