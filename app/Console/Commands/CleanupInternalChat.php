<?php

namespace App\Console\Commands;

use App\Models\InternalMessage;
use App\Models\InternalMessageRead;
use App\Services\InternalChatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Bersihkan pesan chat internal yang sudah lewat masa simpan.
 *
 * Dijalankan sejak fitur chat lahir, bukan menyusul belakangan: tabel
 * `emails` di portal ini tumbuh jadi 60% isi database justru karena
 * pembersihannya dipikirkan setelah datanya menggunung.
 *
 * Pesan yang DISEMATKAN dikecualikan — di logistik, satu pesan bisa berisi
 * instruksi yang masih dipakai berbulan-bulan.
 */
class CleanupInternalChat extends Command
{
    protected $signature = 'chat:cleanup
        {--days= : Masa simpan dalam hari (default 90)}
        {--dry-run : Tampilkan yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus pesan chat internal yang lewat masa simpan (kecuali yang disematkan)';

    public function handle(): int
    {
        $hari  = (int) ($this->option('days') ?: InternalChatService::SIMPAN_HARI);
        $batas = now()->subDays($hari);

        $q = InternalMessage::where('created_at', '<', $batas)->where('is_pinned', false);
        $jumlah = $q->count();

        if ($jumlah === 0) {
            $this->info("Tidak ada pesan lebih tua dari {$hari} hari.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$jumlah} pesan akan dihapus (lebih tua dari {$hari} hari).");

            return self::SUCCESS;
        }

        $q->delete();

        // Penanda baca yang menunjuk pesan terhapus dibiarkan — nilainya tetap
        // valid sebagai batas dan jumlahnya sangat kecil (satu per percakapan).

        $this->info("{$jumlah} pesan chat internal dihapus (lebih tua dari {$hari} hari).");
        Log::info("[chat:cleanup] {$jumlah} pesan dihapus (>{$hari} hari)");

        return self::SUCCESS;
    }
}
