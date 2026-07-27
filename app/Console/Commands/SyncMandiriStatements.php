<?php

namespace App\Console\Commands;

use App\Services\Mandiri\MandiriApiService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMandiriStatements extends Command
{
    /**
     * Nama dan signature dari Artisan Command.
     */
    protected $signature = 'mandiri:sync {--days=3 : Jumlah hari ke belakang yang akan ditarik}';

    /**
     * Deskripsi command.
     */
    protected $description = 'Sinkronisasi mutasi rekening Mandiri M2B & jalankan rekonsiliasi invoice otomatis';

    /**
     * Eksekusi perintah command.
     */
    public function handle(MandiriApiService $mandiriService): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate   = now()->format('Y-m-d');

        $this->info("⏳ Memulai sinkronisasi mutasi Mandiri ({$startDate} s/d {$endDate})...");

        try {
            $statements = $mandiriService->getBankStatement($startDate, $endDate);
            $count = count($statements);

            $this->info("✓ Berhasil memproses {$count} data mutasi dari API Mandiri.");
            Log::info("Command mandiri:sync berhasil memproses {$count} mutasi.");

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Gagal melakukan sinkronisasi Mandiri: " . $e->getMessage());
            Log::error("Command mandiri:sync Gagal: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}
