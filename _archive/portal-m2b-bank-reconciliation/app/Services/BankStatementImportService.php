<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\InvoicePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankStatementImportService
{
    /**
     * Bank yang didukung
     */
    const SUPPORTED_BANKS = [
        'mandiri' => 'Bank Mandiri',
        'bca' => 'Bank BCA',
    ];

    /**
     * Format header untuk setiap bank
     */
    const BANK_HEADERS = [
        'mandiri' => ['AccountNo', 'Ccy', 'PostDate', 'Remarks', 'AdditionalDesc', 'Credit Amount', 'Debit Amount', 'Close Balance'],
        'bca' => ['Tanggal', 'Keterangan', 'Cabang', 'Mutasi', 'Saldo'],
    ];

    protected $errors = [];
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $duplicateCount = 0;

    /**
     * Import file CSV statement bank
     */
    public function import(string $filePath, ?string $bankHint = null): array
    {
        $this->resetCounters();

        // Baca file
        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->errors[] = 'Gagal membaca file';
            return $this->getResult();
        }

        // Detect bank dari header
        $bankName = $bankHint ?? $this->detectBank($content);
        if (!$bankName) {
            $this->errors[] = 'Format file tidak dikenali. Pastikan file adalah statement Bank Mandiri atau BCA.';
            return $this->getResult();
        }

        // Parse berdasarkan bank
        $transactions = match ($bankName) {
            'mandiri' => $this->parseMandiri($content),
            'bca' => $this->parseBCA($content),
            default => [],
        };

        if (empty($transactions)) {
            $this->errors[] = 'Tidak ada transaksi yang dapat diproses';
            return $this->getResult();
        }

        // Generate batch ID
        $batchId = $this->generateBatchId();

        // Import ke database
        $this->saveTransactions($transactions, $bankName, $batchId);

        return $this->getResult($batchId);
    }

    /**
     * Detect bank berdasarkan header CSV
     */
    protected function detectBank(string $content): ?string
    {
        $firstLine = strtok($content, "\n");

        // Check Mandiri format (semicolon separated)
        if (str_contains($firstLine, 'AccountNo;') && str_contains($firstLine, 'PostDate')) {
            return 'mandiri';
        }

        // Check BCA format
        if (str_contains($firstLine, 'Tanggal') && str_contains($firstLine, 'Mutasi')) {
            return 'bca';
        }

        return null;
    }

    /**
     * Parse CSV Bank Mandiri
     */
    protected function parseMandiri(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", $content);

        // Skip header
        $header = str_getcsv(array_shift($lines), ';');

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line, ';');

            // Validasi jumlah kolom
            if (count($data) < 8) {
                $this->errors[] = "Baris " . ($lineNumber + 2) . ": Format tidak valid";
                continue;
            }

            try {
                // Parse tanggal format: "01 December 2025 11:26:05"
                $transactionDate = $this->parseMandiriDate($data[2]);

                if (!$transactionDate) {
                    $this->errors[] = "Baris " . ($lineNumber + 2) . ": Format tanggal tidak valid: {$data[2]}";
                    continue;
                }

                // Parse amounts (format: 5694099.00)
                $creditAmount = $this->parseAmount($data[5]);
                $debitAmount = $this->parseAmount($data[6]);
                $balance = $this->parseAmount($data[7]);

                // Skip jika tidak ada transaksi
                if ($creditAmount == 0 && $debitAmount == 0) {
                    $this->skippedCount++;
                    continue;
                }

                $description = trim($data[3]);
                $additionalDesc = trim($data[4] ?? '');

                $transactions[] = [
                    'account_number' => trim($data[0]),
                    'currency' => trim($data[1]),
                    'transaction_date' => $transactionDate,
                    'description' => $description,
                    'additional_description' => $additionalDesc,
                    'credit_amount' => $creditAmount,
                    'debit_amount' => $debitAmount,
                    'balance' => $balance,
                    'reference_number' => $this->extractReference($description),
                    'category' => BankTransaction::detectCategory($description . ' ' . $additionalDesc),
                ];
            } catch (\Exception $e) {
                $this->errors[] = "Baris " . ($lineNumber + 2) . ": " . $e->getMessage();
            }
        }

        return $transactions;
    }

    /**
     * Parse tanggal format Bank Mandiri
     */
    protected function parseMandiriDate(string $dateStr): ?Carbon
    {
        try {
            // Format: "01 December 2025 11:26:05"
            return Carbon::createFromFormat('d F Y H:i:s', trim($dateStr));
        } catch (\Exception $e) {
            try {
                // Alternatif format tanpa waktu
                return Carbon::createFromFormat('d F Y', trim(explode(' ', $dateStr)[0] . ' ' . explode(' ', $dateStr)[1] . ' ' . explode(' ', $dateStr)[2]));
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /**
     * Parse CSV Bank BCA
     */
    protected function parseBCA(string $content): array
    {
        $transactions = [];
        $lines = explode("\n", $content);

        // Skip header
        array_shift($lines);

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line, ',');

            if (count($data) < 5) {
                $this->errors[] = "Baris " . ($lineNumber + 2) . ": Format tidak valid";
                continue;
            }

            try {
                // Parse tanggal format: "24/12/2025"
                $transactionDate = Carbon::createFromFormat('d/m/Y', trim($data[0]));

                // Parse mutasi (format: "1.500.000,00 CR" atau "500.000,00 DB")
                $mutasi = trim($data[3]);
                $creditAmount = 0;
                $debitAmount = 0;

                if (str_contains($mutasi, 'CR')) {
                    $creditAmount = $this->parseBCAAmount($mutasi);
                } elseif (str_contains($mutasi, 'DB')) {
                    $debitAmount = $this->parseBCAAmount($mutasi);
                }

                $balance = $this->parseBCAAmount($data[4]);
                $description = trim($data[1]);

                $transactions[] = [
                    'account_number' => '', // BCA tidak menyertakan nomor rekening di setiap baris
                    'currency' => 'IDR',
                    'transaction_date' => $transactionDate,
                    'description' => $description,
                    'additional_description' => trim($data[2] ?? ''), // Cabang
                    'credit_amount' => $creditAmount,
                    'debit_amount' => $debitAmount,
                    'balance' => $balance,
                    'reference_number' => $this->extractReference($description),
                    'category' => BankTransaction::detectCategory($description),
                ];
            } catch (\Exception $e) {
                $this->errors[] = "Baris " . ($lineNumber + 2) . ": " . $e->getMessage();
            }
        }

        return $transactions;
    }

    /**
     * Parse amount dari format Bank Mandiri (5694099.00)
     */
    protected function parseAmount(string $amount): float
    {
        $amount = trim($amount);
        if (empty($amount)) return 0;

        // Remove any non-numeric characters except . and -
        $amount = preg_replace('/[^0-9.\-]/', '', $amount);

        return (float) $amount;
    }

    /**
     * Parse amount dari format BCA (1.500.000,00)
     */
    protected function parseBCAAmount(string $amount): float
    {
        // Remove CR/DB suffix
        $amount = preg_replace('/\s*(CR|DB)\s*$/i', '', trim($amount));

        // Convert dari format Indonesia (1.500.000,00) ke float
        $amount = str_replace('.', '', $amount);
        $amount = str_replace(',', '.', $amount);

        return (float) $amount;
    }

    /**
     * Extract reference number dari deskripsi
     */
    protected function extractReference(string $description): ?string
    {
        // Pattern untuk reference number Bank Mandiri
        if (preg_match('/(\d{8}[A-Z]+\d+[A-Z0-9]+)/i', $description, $matches)) {
            return $matches[1];
        }

        // Pattern UBP
        if (preg_match('/(UBP\d+[A-Z0-9]+)/i', $description, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Generate unique batch ID
     */
    protected function generateBatchId(): string
    {
        return 'IMP-' . date('Ymd-His') . '-' . Str::random(4);
    }

    /**
     * Save transactions ke database
     */
    protected function saveTransactions(array $transactions, string $bankName, string $batchId): void
    {
        DB::beginTransaction();

        try {
            foreach ($transactions as $transaction) {
                // Check duplicate berdasarkan tanggal, jumlah, dan deskripsi
                $exists = BankTransaction::where('bank_name', $bankName)
                    ->where('transaction_date', $transaction['transaction_date'])
                    ->where('credit_amount', $transaction['credit_amount'])
                    ->where('debit_amount', $transaction['debit_amount'])
                    ->where('description', $transaction['description'])
                    ->exists();

                if ($exists) {
                    $this->duplicateCount++;
                    continue;
                }

                BankTransaction::create([
                    'bank_name' => $bankName,
                    'account_number' => $transaction['account_number'],
                    'transaction_date' => $transaction['transaction_date'],
                    'description' => $transaction['description'],
                    'additional_description' => $transaction['additional_description'],
                    'credit_amount' => $transaction['credit_amount'],
                    'debit_amount' => $transaction['debit_amount'],
                    'balance' => $transaction['balance'],
                    'reference_number' => $transaction['reference_number'],
                    'category' => $transaction['category'],
                    'is_reconciled' => false,
                    'import_batch' => $batchId,
                    'imported_at' => now(),
                ]);

                $this->importedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = 'Database error: ' . $e->getMessage();
            Log::error('Bank statement import failed', [
                'error' => $e->getMessage(),
                'batch_id' => $batchId,
            ]);
        }
    }

    /**
     * Reset counters
     */
    protected function resetCounters(): void
    {
        $this->errors = [];
        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->duplicateCount = 0;
    }

    /**
     * Get import result
     */
    protected function getResult(?string $batchId = null): array
    {
        return [
            'success' => empty($this->errors) && $this->importedCount > 0,
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'duplicates' => $this->duplicateCount,
            'errors' => $this->errors,
            'batch_id' => $batchId,
        ];
    }

    /**
     * Get supported banks
     */
    public static function getSupportedBanks(): array
    {
        return self::SUPPORTED_BANKS;
    }
}
