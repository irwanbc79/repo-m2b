<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupToDrive extends Command
{
    protected $signature = 'backup:drive';
    protected $description = 'Backup dokumen ke Backblaze B2 (S3)';

    public function handle()
    {
        $this->info('🚀 Memulai Backup ke Backblaze B2...');
        
        // Pastikan config cache bersih dulu
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        try {
            // 1. Ambil semua file di folder 'documents' (lokal)
            $files = Storage::disk('public')->allFiles('documents');
            
            if (empty($files)) {
                $this->info("📂 Tidak ada dokumen untuk dibackup.");
                return;
            }

            $bar = $this->output->createProgressBar(count($files));
            $uploaded = 0;
            $skipped = 0;

            foreach ($files as $file) {
                $needsUpload = true;

                // 2. Cek Eksistensi (Dengan Try-Catch agar tidak mati jika error koneksi)
                try {
                    if (Storage::disk('s3')->exists($file)) {
                        $needsUpload = false;
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    // Jika gagal cek, kita paksa upload saja (Overwrite)
                    $needsUpload = true;
                }

                // 3. Proses Upload
                if ($needsUpload) {
                    $content = Storage::disk('public')->get($file);
                    // Upload dengan setting 'public' atau 'private' sesuai kebutuhan
                    Storage::disk('s3')->put($file, $content);
                    $uploaded++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->info("\n\n✅ Backup Selesai!");
            $this->table(
                ['Status', 'Jumlah File'],
                [
                    ['Terupload Baru', $uploaded],
                    ['Dilewati (Sudah Ada)', $skipped],
                    ['Total File', count($files)],
                ]
            );

        } catch (\Exception $e) {
            $this->error("\n❌ Error Fatal: " . $e->getMessage());
            $this->line("Cek koneksi internet atau konfigurasi .env Backblaze Anda.");
        }
    }
}