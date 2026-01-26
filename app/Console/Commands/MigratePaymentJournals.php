<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Journal;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class MigratePaymentJournals extends Command
{
    protected $signature = 'journals:migrate-payments {--dry-run : Preview tanpa eksekusi}';
    protected $description = 'Generate jurnal pembayaran untuk invoice PAID yang belum punya jurnal';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        // Ambil invoice PAID
        $paidInvoices = Invoice::where('status', 'paid')->get();
        
        // Ambil ID yang sudah punya jurnal
        $existingIds = Journal::where('reference_no', 'like', 'PAY-%')
            ->get()
            ->map(fn($j) => (int) str_replace('PAY-', '', $j->reference_no))
            ->toArray();
        
        $missing = $paidInvoices->filter(fn($inv) => !in_array($inv->id, $existingIds));
        
        $this->info("Invoice PAID: {$paidInvoices->count()}");
        $this->info("Sudah ada jurnal: " . count($existingIds));
        $this->info("Perlu dibuatkan: {$missing->count()}");
        
        if ($missing->isEmpty()) {
            $this->info("Tidak ada jurnal yang perlu dibuat.");
            return 0;
        }
        
        if ($dryRun) {
            $this->warn("\n[DRY RUN] Preview invoice yang akan dibuatkan jurnal:");
            $this->table(
                ['ID', 'Invoice No', 'Grand Total', 'Payment Date'],
                $missing->map(fn($inv) => [
                    $inv->id,
                    $inv->invoice_number,
                    number_format($inv->grand_total, 0, ',', '.'),
                    $inv->payment_date ?? $inv->updated_at->format('Y-m-d')
                ])
            );
            return 0;
        }
        
        // Konfirmasi
        if (!$this->confirm("Lanjutkan membuat {$missing->count()} jurnal?")) {
            return 0;
        }
        
        $success = 0;
        $failed = 0;
        
        foreach ($missing as $invoice) {
            try {
                DB::beginTransaction();
                
                $journal = AccountingService::createJournalFromPayment($invoice, '1103');
                
                if ($journal) {
                    DB::commit();
                    $this->line("✓ {$invoice->invoice_number} -> Jurnal #{$journal->journal_number}");
                    $success++;
                } else {
                    DB::rollBack();
                    $this->error("✗ {$invoice->invoice_number} -> Gagal (akun tidak ditemukan atau amount 0)");
                    $failed++;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ {$invoice->invoice_number} -> Error: {$e->getMessage()}");
                $failed++;
            }
        }
        
        $this->newLine();
        $this->info("Selesai! Sukses: {$success}, Gagal: {$failed}");
        
        return 0;
    }
}
