<?php

namespace App\Console\Commands;

use App\Models\InvoicePayment;
use App\Models\JobCost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Deteksi dini kegagalan pembukuan otomatis.
 *
 * Latar belakang: bug enum cost_category (fixed 2026-07-03) membuat semua
 * pembayaran gagal membuat CashTransaction+jurnal secara diam-diam selama
 * ~2 minggu karena error hanya masuk log. Command ini memastikan kegagalan
 * serupa terdeteksi dalam hitungan hari, bukan minggu.
 */
class CheckFinanceIntegrity extends Command
{
    protected $signature = 'finance:check-integrity
        {--days=2 : Rentang hari ke belakang yang diperiksa}
        {--notify : Kirim email alert ke admin bila ada temuan}';

    protected $description = 'Cek payment/job cost terbaru yang tidak punya CashTransaction, kirim alert bila ada';

    public function handle(): int
    {
        $since = now()->subDays((int) $this->option('days'))->startOfDay();

        $cashTxPaymentIds = DB::table('cash_transactions')
            ->whereNotNull('invoice_payment_id')
            ->pluck('invoice_payment_id');

        $orphanPayments = InvoicePayment::with('invoice')
            ->where('created_at', '>=', $since)
            ->whereNotIn('id', $cashTxPaymentIds)
            ->get();

        $cashTxJobCostIds = DB::table('cash_transactions')
            ->whereNotNull('job_cost_id')
            ->pluck('job_cost_id');

        $orphanJobCosts = JobCost::where('status', 'paid')
            ->where('updated_at', '>=', $since)
            ->whereNotIn('id', $cashTxJobCostIds)
            ->get();

        if ($orphanPayments->isEmpty() && $orphanJobCosts->isEmpty()) {
            $this->info("OK — semua payment & job cost sejak {$since->format('Y-m-d')} punya CashTransaction.");
            return self::SUCCESS;
        }

        $lines = [];
        foreach ($orphanPayments as $p) {
            $lines[] = sprintf(
                'InvoicePayment #%d — %s — Rp %s (%s) TANPA cash transaction',
                $p->id,
                $p->invoice->invoice_number ?? ('invoice #' . $p->invoice_id),
                number_format((float) $p->amount, 0, ',', '.'),
                optional($p->payment_date)->format('Y-m-d')
            );
        }
        foreach ($orphanJobCosts as $jc) {
            $lines[] = sprintf(
                'JobCost #%d (paid) — Rp %s TANPA cash transaction',
                $jc->id,
                number_format((float) $jc->amount, 0, ',', '.')
            );
        }

        $report = "Ditemukan " . count($lines) . " transaksi tanpa pembukuan sejak {$since->format('Y-m-d')}:\n- "
            . implode("\n- ", $lines)
            . "\n\nJalankan `php artisan cashier:backfill-payments --dry-run` untuk menambal, "
            . "dan cek storage/logs/laravel.log untuk akar masalahnya.";

        $this->error($report);
        Log::error('[finance:check-integrity] ' . $report);

        if ($this->option('notify')) {
            $recipient = env('FINANCE_ALERT_EMAIL', config('mail.from.address'));
            if ($recipient) {
                try {
                    Mail::raw($report, function ($message) use ($recipient, $lines) {
                        $message->to($recipient)
                            ->subject('[Portal M2B] ALERT: ' . count($lines) . ' transaksi tanpa pembukuan');
                    });
                    $this->info("Alert dikirim ke {$recipient}");
                } catch (\Throwable $e) {
                    Log::error('[finance:check-integrity] gagal kirim email alert: ' . $e->getMessage());
                }
            }
        }

        return self::FAILURE;
    }
}
