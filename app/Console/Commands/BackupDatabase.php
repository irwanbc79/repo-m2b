<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database 
                            {--env : Sertakan salinan .env (aman di server, jangan commit)}
                            {--keep=7 : Jumlah backup yang disimpan (lama dihapus otomatis)}';
    protected $description = 'Backup database + opsional .env ke storage/app/backups (tanpa mengganggu flow)';

    public function handle(): int
    {
        $this->info('📦 Backup database Portal M2B...');

        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $driver = config('database.default');
        $date = now()->format('Y-m-d_H-i-s');
        $saved = [];

        try {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $path = $this->backupMysql($backupDir, $date);
                if ($path) {
                    $saved[] = $path;
                }
            } elseif ($driver === 'sqlite') {
                $path = $this->backupSqlite($backupDir, $date);
                if ($path) {
                    $saved[] = $path;
                }
            } else {
                $this->warn("Driver {$driver} belum didukung. Backup manual disarankan.");
                return self::FAILURE;
            }

            if ($this->option('env')) {
                $envPath = base_path('.env');
                if (File::exists($envPath)) {
                    $target = $backupDir . '/.env.' . $date;
                    File::copy($envPath, $target);
                    $saved[] = $target;
                    $this->info('   .env disalin (simpan aman, jangan commit).');
                }
            }

            $this->pruneOldBackups($backupDir, (int) $this->option('keep'));

            $this->info('✅ Backup selesai: ' . implode(', ', array_map('basename', $saved)));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Gagal: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function backupMysql(string $backupDir, string $date): ?string
    {
        $db = config('database.connections.mysql');
        $host = $db['host'] ?? '127.0.0.1';
        $port = $db['port'] ?? '3306';
        $user = $db['username'] ?? 'root';
        $pass = $db['password'] ?? '';
        $name = $db['database'] ?? '';

        $sqlPath = $backupDir . "/db_mysql_{$date}.sql";
        $path = $sqlPath;

        $cmd = sprintf(
            'mysqldump -h %s -P %s -u %s %s 2>/dev/null',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($name)
        );

        $prev = getenv('MYSQL_PWD');
        if ($pass !== '') {
            putenv('MYSQL_PWD=' . $pass);
        }
        $content = @shell_exec($cmd);
        putenv($prev !== false ? 'MYSQL_PWD=' . $prev : 'MYSQL_PWD');
        $content = $content ?? '';

        if (trim($content) === '') {
            throw new \RuntimeException('mysqldump gagal atau kosong. Cek kredensial DB dan pastikan mysqldump tersedia di server.');
        }

        if (function_exists('gzencode')) {
            $path = $sqlPath . '.gz';
            File::put($path, gzencode($content, 9));
        } else {
            File::put($sqlPath, $content);
        }

        return $path;
    }

    private function backupSqlite(string $backupDir, string $date): ?string
    {
        $dbPath = config('database.connections.sqlite.database');
        if (!File::exists($dbPath)) {
            throw new \RuntimeException('File SQLite tidak ditemukan: ' . $dbPath);
        }

        $filename = "db_sqlite_{$date}.sqlite";
        $path = $backupDir . '/' . $filename;
        File::copy($dbPath, $path);

        return $path;
    }

    private function pruneOldBackups(string $backupDir, int $keep): void
    {
        $files = File::glob($backupDir . '/db_*');
        if (count($files) <= $keep) {
            return;
        }
        usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));
        $toRemove = array_slice($files, 0, count($files) - $keep);
        foreach ($toRemove as $f) {
            File::delete($f);
            $this->line('   Dihapus (lama): ' . basename($f));
        }
    }
}
