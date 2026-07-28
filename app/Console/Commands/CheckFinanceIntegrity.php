<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JobCost;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Deteksi dini kegagalan pembukuan otomatis + alat cross-check Invoicing↔Kasir.
 *
 * Latar belakang: bug enum cost_category (fixed 2026-07-03) membuat pembayaran
 * gagal membuat CashTransaction+jurnal secara diam-diam. Sesudah fix pun sempat
 * terjadi kegagalan senyap (payment #85 Rp38jt & #86, 13-14 Juli 2026) TANPA
 * jejak error.
 *
 * Perbaikan 2026-07-16:
 * - Tidak lagi memakai jendela geser 2 hari yang membuat temuan "menua-hilang"
 *   (payment #85 Rp38jt lolos karena berumur 3 hari). Kini pakai BATAS TETAP
 *   (floor) sehingga temuan TERUS muncul sampai benar-benar dibukukan.
 * - Alert diarahkan ke finance@m2b.co.id (default) sebagai alat cross-check
 *   antara staf Invoicing (pencatat) & Kasir (pembuku), lengkap dengan nama
 *   pencatat + langkah verifikasi.
 */
class CheckFinanceIntegrity extends Command
{
    protected $signature = 'finance:check-integrity
        {--since= : Batas tanggal terlama yang diperiksa untuk JOB COST (default env FINANCE_INTEGRITY_SINCE atau 2026-06-01, untuk mengecualikan backlog legacy). Pembayaran invoice selalu diperiksa penuh.}
        {--notify : Kirim email cross-check ke finance bila ada temuan}
        {--digest : Kirim juga email ringkasan "semua aman" saat TIDAK ada temuan (untuk heartbeat berkala)}';

    protected $description = 'Cek payment/job cost yang belum punya CashTransaction, kirim email cross-check ke finance bila ada';

    public function handle(): int
    {
        // Batas FOKUS: Direktur menetapkan mulai 1 Jan 2026 (transaksi Des 2025
        // ditunda sbg opening balance). Dipakai utk cek invoice quick-pay lama.
        $focusFloor = Carbon::parse('2026-01-01')->startOfDay();

        // Batas JOB COST: kecualikan backlog legacy yg menunggu keputusan Direktur
        // (env FINANCE_INTEGRITY_SINCE, default 2026-06-01). Pakai date_paid
        // (tanggal bayar RIIL), BUKAN updated_at yg bisa jauh setelahnya.
        $jobCostFloor = Carbon::parse(
            $this->option('since') ?: env('FINANCE_INTEGRITY_SINCE', '2026-06-01')
        )->startOfDay();

        $cashTxPaymentIds = DB::table('cash_transactions')
            ->whereNotNull('invoice_payment_id')
            ->pluck('invoice_payment_id');

        // Semua pembayaran invoice yang belum terbukukan — TANPA jendela waktu,
        // agar tidak pernah "menua-hilang" dari radar.
        $orphanPayments = InvoicePayment::with(['invoice', 'recorder'])
            ->whereNotIn('id', $cashTxPaymentIds)
            ->orderBy('payment_date')
            ->get();

        // Blind spot quick-pay: invoice 'paid' TANPA InvoicePayment DAN tanpa
        // CashTransaction. Dibatasi periode fokus (Des 2025 dikecualikan).
        $cashTxInvoiceIds  = DB::table('cash_transactions')->whereNotNull('invoice_id')->pluck('invoice_id');
        $paymentInvoiceIds = DB::table('invoice_payments')->distinct()->pluck('invoice_id');
        $orphanPaidInvoices = Invoice::where('status', 'paid')
            ->where('invoice_date', '>=', $focusFloor)
            ->whereNotIn('id', $cashTxInvoiceIds)
            ->whereNotIn('id', $paymentInvoiceIds)
            ->get();

        $cashTxJobCostIds = DB::table('cash_transactions')
            ->whereNotNull('job_cost_id')
            ->pluck('job_cost_id');

        $orphanJobCosts = JobCost::where('status', 'paid')
            ->where('date_paid', '>=', $jobCostFloor)
            ->whereNotIn('id', $cashTxJobCostIds)
            ->get();

        if ($orphanPayments->isEmpty() && $orphanJobCosts->isEmpty() && $orphanPaidInvoices->isEmpty()) {
            $this->info('OK — semua pembayaran invoice & job cost (sejak ' . $jobCostFloor->format('Y-m-d') . ') sudah terbukukan.');

            if ($this->option('notify') && $this->option('digest')) {
                $recipient = env('FINANCE_ALERT_EMAIL', 'finance@m2b.co.id');
                if ($recipient) {
                    $body = "PORTAL M2B — RINGKASAN PEMBUKUAN\n" . str_repeat('=', 42) . "\n\n"
                        . "✅ Buku kas AMAN. Semua pembayaran invoice sudah terbukukan, dan tidak ada\n"
                        . "job cost (sejak {$jobCostFloor->format('Y-m-d')}) yang tertinggal.\n\n"
                        . "Tidak ada tindakan yang diperlukan. Ringkasan ini dikirim berkala sebagai\n"
                        . "pengingat bahwa pemantauan integritas pembukuan berjalan normal.\n";
                    try {
                        Mail::mailer(env('FINANCE_ALERT_MAILER'))->raw($body, function ($message) use ($recipient) {
                            $message->to($recipient)->subject('[Portal M2B] ✅ Buku kas aman — semua transaksi terbukukan');
                        });
                        $this->info("Ringkasan digest dikirim ke {$recipient}");
                    } catch (\Throwable $e) {
                        Log::error('[finance:check-integrity] gagal kirim digest: ' . $e->getMessage());
                    }
                }
            }

            return self::SUCCESS;
        }

        $paymentLines = [];
        foreach ($orphanPayments as $p) {
            $paymentLines[] = sprintf(
                'InvoicePayment #%d — %s — Rp %s (%s) — dicatat oleh: %s',
                $p->id,
                $p->invoice->invoice_number ?? ('invoice #' . $p->invoice_id),
                number_format((float) $p->amount, 0, ',', '.'),
                optional($p->payment_date)->format('Y-m-d'),
                $p->recorder->name ?? ('user #' . $p->recorded_by)
            );
        }

        $jobCostLines = [];
        foreach ($orphanJobCosts as $jc) {
            $jobCostLines[] = sprintf(
                'JobCost #%d (paid) — Rp %s',
                $jc->id,
                number_format((float) $jc->amount, 0, ',', '.')
            );
        }

        $paidInvoiceLines = [];
        foreach ($orphanPaidInvoices as $inv) {
            $paidInvoiceLines[] = sprintf(
                'Invoice %s — Rp %s (%s) — LUNAS tapi belum ada catatan kas (quick-pay lama)',
                $inv->invoice_number ?? ('#' . $inv->id),
                number_format((float) $inv->grand_total, 0, ',', '.'),
                optional($inv->invoice_date)->format('Y-m-d')
            );
        }

        $total = count($paymentLines) + count($jobCostLines) + count($paidInvoiceLines);
        $report = $this->buildReport($paymentLines, $jobCostLines, $paidInvoiceLines, $total);

        $this->error($report);
        Log::error('[finance:check-integrity] ' . $total . ' transaksi belum terbukukan (lihat email cross-check)');

        if ($this->option('notify')) {
            $recipient = env('FINANCE_ALERT_EMAIL', 'finance@m2b.co.id');
            if ($recipient) {
                try {
                    Mail::mailer(env('FINANCE_ALERT_MAILER'))->raw($report, function ($message) use ($recipient, $total) {
                        $message->to($recipient)
                            ->subject('[Portal M2B] CROSS-CHECK: ' . $total . ' transaksi belum terbukukan');
                    });
                    $this->info("Email cross-check dikirim ke {$recipient}");
                } catch (\Throwable $e) {
                    Log::error('[finance:check-integrity] gagal kirim email: ' . $e->getMessage());
                }
            }
        }

        return self::FAILURE;
    }

    /**
     * Susun email sebagai alat cross-check Invoicing ↔ Kasir.
     */
    protected function buildReport(array $paymentLines, array $jobCostLines, array $paidInvoiceLines, int $total): string
    {
        $out = "PORTAL M2B — CROSS-CHECK PEMBUKUAN\n";
        $out .= str_repeat('=', 42) . "\n\n";
        $out .= "Ditemukan {$total} transaksi yang SUDAH tercatat tapi BELUM masuk buku kas (jurnal).\n";
        $out .= "Mohon staf Invoicing (pencatat) & Kasir (pembuku) saling cross-check.\n\n";

        if ($paymentLines) {
            $out .= "PEMBAYARAN INVOICE (uang masuk):\n- " . implode("\n- ", $paymentLines) . "\n\n";
        }
        if ($paidInvoiceLines) {
            $out .= "INVOICE LUNAS TANPA CATATAN KAS (quick-pay lama):\n- " . implode("\n- ", $paidInvoiceLines) . "\n\n";
        }
        if ($jobCostLines) {
            $out .= "JOB COST / BIAYA (uang keluar):\n- " . implode("\n- ", $jobCostLines) . "\n\n";
        }

        $out .= "LANGKAH VERIFIKASI:\n";
        $out .= "1. Invoicing: pastikan pembayaran benar & bukti transfernya valid.\n";
        $out .= "2. Kasir: cocokkan dengan mutasi bank, lalu bukukan (buat Cash Transaction) di menu Kasir.\n";
        $out .= "3. Bila sudah benar tapi tetap muncul, hubungi admin — kemungkinan gagal auto-posting\n";
        $out .= "   (tambal cepat: `php artisan cashier:backfill-payments --dry-run` lalu tanpa --dry-run).\n\n";
        $out .= "Catatan: item ini akan TERUS muncul di laporan harian ini sampai benar-benar\n";
        $out .= "dibukukan — tidak lagi hilang sendiri. Buku kas yang akurat = prioritas.\n";

        return $out;
    }
}
