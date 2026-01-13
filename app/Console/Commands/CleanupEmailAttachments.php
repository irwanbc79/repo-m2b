<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupEmailAttachments extends Command
{
    protected $signature = 'email:cleanup';
    protected $description = 'Cleanup old temporary email attachments';

    public function handle()
    {
        $basePath = 'email_attachments';
        $ttlDays = 7;
        $now = Carbon::now();

        if (!Storage::exists($basePath)) {
            $this->info('No email_attachments directory. Skip.');
            return Command::SUCCESS;
        }

        $directories = Storage::directories($basePath);

        foreach ($directories as $mailboxDir) {
            // hapus folder SHIP_TMP
            $tmpDir = $mailboxDir . '/SHIP_TMP';
            if (Storage::exists($tmpDir)) {
                Storage::deleteDirectory($tmpDir);
                $this->info("Deleted temp dir: {$tmpDir}");
            }

            // cleanup file lama (jika ada file stray)
            $files = Storage::allFiles($mailboxDir);
            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp(
                    Storage::lastModified($file)
                );

                if ($lastModified->diffInDays($now) >= $ttlDays) {
                    Storage::delete($file);
                    $this->info("Deleted old file: {$file}");
                }
            }
        }

        $this->info('Email attachment cleanup completed.');
        return Command::SUCCESS;
    }
}
