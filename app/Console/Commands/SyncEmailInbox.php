<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SyncEmailInbox extends Command
{
    protected $signature = 'email:sync {mailbox?} {--force} {--backfill} {--days=7}';
    protected $description = 'Sync IMAP email inbox — fetches recent emails using date-based query';

    protected array $mailboxes = ['sales', 'import', 'export', 'finance', 'gmail'];

    public function handle()
    {
        $targetMailbox = $this->argument('mailbox');
        $isForce = $this->option('force');

        $mailboxes = $targetMailbox ? [$targetMailbox] : $this->mailboxes;

        foreach ($mailboxes as $mailbox) {
            $this->info("------------------------------------------");
            $this->info("🔄 Memproses Akun: " . strtoupper($mailbox));
            $this->syncMailbox($mailbox, $isForce);
        }

        return Command::SUCCESS;
    }

    protected function syncMailbox(string $mailbox, bool $isForce = false)
    {
        try {
            $client = Client::account($mailbox);
            if (!$client->isConnected()) { $client->connect(); }

            // Outlook Fix: Cari folder INBOX atau folder lain yang mungkin menampung email masuk
            $folders = $client->getFolders();
            $targetFolder = null;
            foreach ($folders as $folder) {
                $name = strtolower($folder->name);
                if ($name === 'inbox' || str_contains($name, 'fokus') || str_contains($name, 'focused')) {
                    $targetFolder = $folder;
                    break;
                }
            }

            if (!$targetFolder) {
                $this->error("   ❌ Folder masuk tidak ditemukan.");
                return;
            }

            $this->info("   📂 Scan Folder: {$targetFolder->name}");

            // Tentukan jumlah hari untuk fetch
            $days = (int) $this->option('days');
            $isBackfill = $this->option('backfill');

            if ($isBackfill) {
                // Mode backfill: ambil email 30 hari terakhir, limit 500
                $sinceDate = Carbon::now()->subDays(30);
                $limit = 500;
                $this->info("   ⏪ MODE BACKFILL: Mengambil email 30 hari terakhir (max {$limit})");
            } else {
                // Mode normal: ambil email N hari terakhir (default 7), limit 200
                $sinceDate = Carbon::now()->subDays($days);
                $limit = 200;
            }

            // Gunakan query SINCE untuk mengambil email berdasarkan tanggal
            // Ini jauh lebih andal daripada limit(20) yang bisa "kehabisan slot"
            try {
                $messages = $targetFolder->messages()
                    ->since($sinceDate)
                    ->limit($limit)
                    ->setFetchOrder('desc')
                    ->get();
            } catch (\Throwable $e) {
                // Fallback: jika SINCE tidak didukung server, gunakan all() dengan limit lebih besar
                Log::warning("IMAP SINCE query gagal untuk {$mailbox}, fallback ke all(): " . $e->getMessage());
                $this->warn("   ⚠️ Filter tanggal tidak didukung server, menggunakan fallback...");
                $messages = $targetFolder->messages()
                    ->all()
                    ->limit($limit)
                    ->setFetchOrder('desc')
                    ->get();
            }

            $this->info("   🔍 Memeriksa " . $messages->count() . " email (sejak " . $sinceDate->format('d M Y') . ")...");

            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($messages as $message) {
                $uid = (int) $message->getUid();
                $msgId = (string)$message->getMessageId();

                // Cek ganda di database (UID & Message-ID)
                $exists = DB::table('emails')
                    ->where('mailbox', $mailbox)
                    ->where(function($q) use ($uid, $msgId) {
                        $q->where('uid', $uid)->orWhere('message_id', $msgId);
                    })->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                DB::beginTransaction();
                try {
                    $from = $message->getFrom()[0]->mail ?? 'unknown@m2b.co.id';
                    $fromName = $message->getFrom()[0]->personal ?? $from;
                    $subject = (string) $message->getSubject();
                    $dateObj = $message->getDate();
                    $date = $dateObj instanceof Carbon ? $dateObj : Carbon::parse($dateObj->first());
                    
                    // Body: Utamakan HTML, bersihkan sedikit
                    $rawBody = $message->getHTMLBody() ?: $message->getTextBody() ?: "(Email Kosong)";
                    
                    // Sanitize UTF-8 untuk menghindari error "Incorrect string value"
                    $body = mb_convert_encoding($rawBody, 'UTF-8', 'UTF-8');
                    $subject = mb_convert_encoding($subject, 'UTF-8', 'UTF-8');
                    $fromName = mb_convert_encoding($fromName, 'UTF-8', 'UTF-8');

                    $emailId = DB::table('emails')->insertGetId([
                        'mailbox'    => $mailbox,
                        'uid'        => $uid,
                        'message_id' => $msgId,
                        'subject'    => $subject,
                        'from_email' => $from,
                        'from_name'  => $fromName,
                        'body'       => $body,
                        'is_read'    => $message->getFlags()->has('seen'),
                        'email_date' => $date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($message->getAttachments() as $att) {
                        $filename = $att->getName();
                        $path = "email_attachments/{$mailbox}/{$uid}";
                        $fullPath = "{$path}/" . Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . "_" . uniqid() . "." . (pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin');
                        Storage::disk('public')->put($fullPath, $att->getContent());

                        DB::table('email_attachments')->insert([
                            'email_id' => $emailId, 'filename' => $filename, 'file_path'=> $fullPath,
                            'mime_type'=> $att->getMimeType(), 'size' => $att->getSize(), 'created_at'=> now(),
                        ]);
                    }

                    DB::commit();
                    $syncedCount++;
                    $this->line("   ✅ SYNCED: [{$date->format('d M')}] {$subject}");

                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error("Error syncing UID {$uid} [{$mailbox}]: " . $e->getMessage());
                    $this->warn("   ⚠️ Gagal sync UID {$uid}: " . Str::limit($e->getMessage(), 80));
                }
            }

            $this->info("   📊 Hasil: {$syncedCount} email baru, {$skippedCount} sudah ada.");

        } catch (\Throwable $e) {
            $this->error("   ❌ Koneksi Error [{$mailbox}]: " . $e->getMessage());
            Log::error("IMAP sync failed for {$mailbox}: " . $e->getMessage(), [
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);
        }
    }
}