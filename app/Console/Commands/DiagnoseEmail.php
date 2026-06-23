<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DiagnoseEmail extends Command
{
    protected $signature = 'email:diagnose {mailbox?}';
    protected $description = 'Diagnosa lengkap koneksi IMAP & jumlah email di server vs database';

    protected array $mailboxes = ['sales', 'import', 'export', 'finance', 'gmail', 'pajak', 'outlook', 'shipping'];

    public function handle()
    {
        $target = $this->argument('mailbox');
        $mailboxes = $target ? [$target] : $this->mailboxes;

        foreach ($mailboxes as $mailbox) {
            $this->newLine();
            $this->info("══════════════════════════════════════════");
            $this->info("  📧 DIAGNOSA: " . strtoupper($mailbox));
            $this->info("══════════════════════════════════════════");

            // 1. Cek config
            $config = config("imap.accounts.{$mailbox}");
            if (!$config) {
                $this->error("  ❌ Config tidak ditemukan di config/imap.php");
                continue;
            }
            $this->line("  Host    : {$config['host']}");
            $this->line("  Port    : {$config['port']}");
            $this->line("  User    : {$config['username']}");
            $this->line("  Encrypt : {$config['encryption']}");

            // 2. Cek koneksi
            try {
                $client = Client::account($mailbox);
                $client->connect();
                $this->info("  ✅ Koneksi BERHASIL");
            } catch (\Throwable $e) {
                $this->error("  ❌ Koneksi GAGAL: " . $e->getMessage());
                continue;
            }

            // 3. List SEMUA folder
            $this->newLine();
            $this->info("  📂 DAFTAR FOLDER DI SERVER:");
            try {
                $folders = $client->getFolders(false);
                foreach ($folders as $folder) {
                    $name = $folder->name;
                    $fullName = $folder->full_name;
                    
                    // Hitung email di folder ini
                    try {
                        $count = $folder->messages()->all()->count();
                    } catch (\Throwable $e) {
                        $count = '(error)';
                    }
                    
                    $marker = (strtolower($name) === 'inbox') ? ' ◀ INBOX' : '';
                    $this->line("     📁 {$fullName} — {$count} email{$marker}");
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ Gagal list folder: " . $e->getMessage());
            }

            // 4. Cek INBOX detail
            $this->newLine();
            $this->info("  📊 DETAIL INBOX:");
            try {
                $folder = $client->getFolder('INBOX');
                
                // Total email
                $totalAll = $folder->messages()->all()->count();
                $this->line("     Total email di server  : {$totalAll}");

                // Email 7 hari terakhir
                try {
                    $since7 = $folder->messages()->since(Carbon::now()->subDays(7))->count();
                    $this->line("     Email 7 hari terakhir  : {$since7}");
                } catch (\Throwable $e) {
                    $this->warn("     Email 7 hari terakhir  : (SINCE query gagal)");
                }

                // Email 30 hari terakhir 
                try {
                    $since30 = $folder->messages()->since(Carbon::now()->subDays(30))->count();
                    $this->line("     Email 30 hari terakhir : {$since30}");
                } catch (\Throwable $e) {
                    $this->warn("     Email 30 hari terakhir : (SINCE query gagal)");
                }

                // Unseen
                try {
                    $unseen = $folder->messages()->unseen()->count();
                    $this->line("     Email belum dibaca     : {$unseen}");
                } catch (\Throwable $e) {
                    $this->warn("     Email belum dibaca     : (query gagal)");
                }

                // 5 email terbaru
                $this->newLine();
                $this->info("  📬 5 EMAIL TERBARU DI SERVER:");
                $latest = $folder->messages()->all()->limit(5)->setFetchOrder('desc')->get();
                foreach ($latest as $msg) {
                    $date = $msg->getDate();
                    $dateStr = ($date instanceof Carbon) ? $date->format('d M Y H:i') : (string)$date;
                    $subject = substr((string)$msg->getSubject(), 0, 60);
                    $from = $msg->getFrom()[0]->mail ?? 'unknown';
                    $this->line("     [{$dateStr}] {$from}");
                    $this->line("       ↳ {$subject}");
                }
            } catch (\Throwable $e) {
                $this->error("  ❌ Gagal cek INBOX: " . $e->getMessage());
            }

            // 5. Cek database
            $this->newLine();
            $this->info("  🗄️  DATABASE PORTAL:");
            $dbTotal = DB::table('emails')->where('mailbox', $mailbox)->count();
            $dbRecent = DB::table('emails')->where('mailbox', $mailbox)
                ->where('email_date', '>=', Carbon::now()->subDays(30))
                ->count();
            $lastSync = DB::table('emails')->where('mailbox', $mailbox)
                ->orderByDesc('email_date')->value('email_date');
            
            $this->line("     Total di database      : {$dbTotal}");
            $this->line("     30 hari terakhir di DB  : {$dbRecent}");
            $this->line("     Email terakhir di DB    : {$lastSync}");

            // 6. Perbandingan
            $this->newLine();
            if (isset($totalAll) && is_numeric($totalAll)) {
                $diff = $totalAll - $dbTotal;
                if ($diff > 0) {
                    $this->warn("  ⚠️  Ada {$diff} email di server yang BELUM ada di database!");
                } elseif ($diff < 0) {
                    $this->warn("  ⚠️  Database punya " . abs($diff) . " email LEBIH BANYAK dari server (kemungkinan email dihapus di server)");
                } else {
                    $this->info("  ✅ Server dan database sinkron!");
                }
            }

            try { $client->disconnect(); } catch (\Throwable $e) {}
        }

        $this->newLine();
        $this->info("══════════════════════════════════════════");
        $this->info("  Diagnosa selesai.");
        $this->info("══════════════════════════════════════════");

        return Command::SUCCESS;
    }
}
