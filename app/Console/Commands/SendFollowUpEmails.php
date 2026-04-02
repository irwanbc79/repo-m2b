<?php

namespace App\Console\Commands;

use App\Mail\ShipmentFollowUpMail;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendFollowUpEmails extends Command
{
    protected $signature = 'emails:send-followup {--dry-run : Preview tanpa kirim email} {--test-email= : Kirim sample email ke alamat ini untuk ujicoba} {--lang= : Paksa bahasa (id/en) untuk mode test}';
    protected $description = 'Kirim email follow-up 3 hari setelah invoice PAID';

    public function handle(): int
    {
        // MODE TEST: kirim sample email ke alamat tertentu
        if ($testEmail = $this->option('test-email')) {
            $invoice = Invoice::with(['customer.user', 'shipment'])
                ->where('status', 'paid')
                ->whereHas('customer.user')
                ->latest('payment_date')
                ->first();

            if (!$invoice) {
                $this->error('Tidak ada invoice PAID ditemukan untuk sample.');
                return self::FAILURE;
            }

            try {
                Mail::to($testEmail)->send(new ShipmentFollowUpMail($invoice, $this->option('lang')));
                $this->info("[TEST] Email berhasil dikirim ke: {$testEmail}");
                $this->info("  Invoice: {$invoice->invoice_number} | Customer: {$invoice->customer->company_name}");
            } catch (\Throwable $e) {
                $this->error("[TEST GAGAL] " . $e->getMessage());
                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $targetDate = Carbon::today()->subDays(3)->toDateString();

        $invoices = Invoice::with(['customer.user', 'shipment'])
            ->where('status', 'paid')
            ->whereDate('payment_date', $targetDate)
            ->whereNull('follow_up_sent_at')
            ->whereHas('customer.user')
            ->whereHas('customer', fn($q) => $q->where('no_followup_email', false))
            ->get();

        if ($invoices->isEmpty()) {
            $this->info("Tidak ada invoice PAID pada {$targetDate} yang belum dikirim follow-up.");
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$invoices->count()} invoice. Mengirim follow-up email...");

        $sent = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            $email = $invoice->customer->user->email ?? null;

            if (!$email) {
                $this->warn("  [SKIP] Invoice #{$invoice->invoice_number} - email customer tidak ditemukan.");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [DRY-RUN] Akan kirim ke: {$email} (Invoice: {$invoice->invoice_number})");
                continue;
            }

            try {
                Mail::to($email)->send(new ShipmentFollowUpMail($invoice));
                $invoice->update(['follow_up_sent_at' => now()]);
                $this->info("  [OK] Terkirim ke: {$email} (Invoice: {$invoice->invoice_number})");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  [GAGAL] {$email}: " . $e->getMessage());
                $failed++;
            }
        }

        if (!$this->option('dry-run')) {
            $this->info("Selesai. Terkirim: {$sent}, Gagal: {$failed}.");
        }

        return self::SUCCESS;
    }
}
