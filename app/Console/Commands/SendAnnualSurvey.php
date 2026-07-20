<?php

namespace App\Console\Commands;

use App\Mail\SurveyAnnualInviteMail;
use App\Models\Customer;
use App\Models\CustomerSurvey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Kampanye undangan survey kepuasan tahunan (awal tahun).
 * Sekali per customer per tahun; hormati opt-out; skip yang sudah mengisi tahun ini.
 */
class SendAnnualSurvey extends Command
{
    protected $signature = 'survey:send-annual
        {--dry-run : Tampilkan target tanpa mengirim}
        {--test-email= : Kirim satu contoh ke alamat ini}
        {--year= : Tahun survey (default tahun berjalan)}';

    protected $description = 'Kirim undangan survey kepuasan tahunan ke customer aktif';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: date('Y'));

        if ($testEmail = $this->option('test-email')) {
            Mail::to($testEmail)->send(new SurveyAnnualInviteMail(Customer::first(), $year));
            $this->info("[TEST] Undangan survey {$year} dikirim ke {$testEmail}.");
            return self::SUCCESS;
        }

        // Customer yang sudah mengisi survey tahun ini (skip).
        $sudahIsi = CustomerSurvey::where('survey_year', $year)
            ->whereNotNull('customer_id')->pluck('customer_id')->unique();

        $customers = Customer::query()
            ->where('no_followup_email', false)
            ->whereHas('user', fn ($q) => $q->whereNotNull('email'))
            ->whereNotIn('id', $sudahIsi)
            ->with('user')
            ->get();

        if ($customers->isEmpty()) {
            $this->info("Tidak ada customer yang perlu diundang untuk survey {$year}.");
            return self::SUCCESS;
        }

        $this->info("Mengirim undangan survey {$year} ke {$customers->count()} customer…");
        $sent = 0; $failed = 0;

        foreach ($customers as $customer) {
            $email = $customer->user?->email;
            if (! $email) continue;

            if ($this->option('dry-run')) {
                $this->line("  [DRY-RUN] {$email} ({$customer->company_name})");
                continue;
            }

            try {
                Mail::to($email)->send(new SurveyAnnualInviteMail($customer, $year));
                $this->info("  [OK] {$email} ({$customer->company_name})");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  [GAGAL] {$email}: " . $e->getMessage());
                $failed++;
            }
        }

        if (! $this->option('dry-run')) {
            $this->info("Selesai. Terkirim: {$sent}, Gagal: {$failed}.");
        }

        return self::SUCCESS;
    }
}
