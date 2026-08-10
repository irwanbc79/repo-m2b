<?php

namespace App\Console\Commands;

use App\Models\InternalMessage;
use App\Models\InternalMessageRead;
use App\Services\InternalChatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        $berkas = (clone $q)->whereNotNull('attachment_path')->count();

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$jumlah} pesan ({$berkas} berlampiran) akan dihapus, lebih tua dari {$hari} hari.");

            return self::SUCCESS;
        }

        // Hapus FILE-nya dulu, baris menyusul. Kalau baris dihapus lebih dulu
        // lalu proses berhenti, jejak menuju file itu hilang dan file-nya
        // menumpuk selamanya tanpa ada yang bisa melihat atau menghapusnya.
        $fileTerhapus = 0;
        (clone $q)->whereNotNull('attachment_path')
            ->select('id', 'attachment_path')
            ->chunkById(200, function ($batch) use (&$fileTerhapus) {
                foreach ($batch as $m) {
                    try {
                        if (Storage::disk('local')->exists($m->attachment_path)) {
                            Storage::disk('local')->delete($m->attachment_path);
                            $fileTerhapus++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning("[chat:cleanup] gagal hapus file {$m->attachment_path}: " . $e->getMessage());
                    }
                }
            });

        $q->delete();

        // Penanda baca yang menunjuk pesan terhapus dibiarkan — nilainya tetap
        // valid sebagai batas dan jumlahnya sangat kecil (satu per percakapan).

        $this->info("{$jumlah} pesan chat internal dihapus ({$fileTerhapus} file lampiran ikut dihapus), lebih tua dari {$hari} hari.");
        Log::info("[chat:cleanup] {$jumlah} pesan + {$fileTerhapus} file dihapus (>{$hari} hari)");

        return self::SUCCESS;
    }
}
