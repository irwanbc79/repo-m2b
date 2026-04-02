<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--with-env : Sertakan salinan .env (aman di server, jangan commit)}
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

            if ($this->option('with-env')) {
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

        $pdo = new \PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $output = "-- Portal M2B Database Backup\n";
        $output .= "-- Date: {$date}\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $createStmt['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $output .= "INSERT INTO `{$table}` ({$columns}) VALUES\n";
                $vals = [];
                foreach ($rows as $row) {
                    $escaped = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row);
                    $vals[] = '(' . implode(', ', $escaped) . ')';
                }
                $output .= implode(",\n", $vals) . ";\n\n";
            }
        }

        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $path = $sqlPath;
        if (function_exists('gzencode')) {
            $path .= '.gz';
            File::put($path, gzencode($output, 9));
        } else {
            File::put($sqlPath, $output);
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
