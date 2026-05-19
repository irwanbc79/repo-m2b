<?php

namespace App\Console\Commands;

use App\Mail\TestimonialReminderMail;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTestimonialReminder extends Command
{
    protected $signature = 'emails:testimonial-reminder {--dry-run : Preview tanpa kirim}';
    protected $description = 'Kirim reminder ke customer yang belum mengisi testimoni setelah 7 hari';

    public function handle(): int
    {
        // Cari testimoni: belum diisi (content kosong), dibuat 7-30 hari lalu, belum dapat reminder, token belum expired
        $testimonials = Testimonial::with(['customer.user', 'invoice'])
            ->where('status', 'pending')
            ->where('content', '')
            ->whereNull('reminder_sent_at')
            ->where(fn($q) => $q->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now()))
            ->whereDate('created_at', '<=', now()->subDays(7)->toDateString())
            ->whereDate('created_at', '>=', now()->subDays(30)->toDateString())
            ->whereHas('customer', fn($q) => $q->where('no_followup_email', false))
            ->whereHas('customer.user')
            ->get();

        if ($testimonials->isEmpty()) {
            $this->info('Tidak ada testimoni yang perlu diingatkan.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$testimonials->count()} testimoni belum diisi. Mengirim reminder...");

        $sent   = 0;
        $failed = 0;

        foreach ($testimonials as $testimonial) {
            $email = $testimonial->customer?->user?->email;

            if (!$email) {
                $this->warn("  [SKIP] Testimoni #{$testimonial->id} — email tidak ditemukan.");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [DRY-RUN] Akan kirim reminder ke: {$email} (Testimoni #{$testimonial->id})");
                continue;
            }

            try {
                Mail::to($email)->send(new TestimonialReminderMail($testimonial));
                $testimonial->update(['reminder_sent_at' => now()]);
                $this->info("  [OK] Reminder terkirim ke: {$email}");
                $sent++;
            } catch (\Throwable $e) {
                $this->error("  [GAGAL] {$email}: " . $e->getMessage());
                Log::error("SendTestimonialReminder gagal untuk testimonial #{$testimonial->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        if (!$this->option('dry-run')) {
            $this->info("Selesai. Terkirim: {$sent}, Gagal: {$failed}.");
        }

        return self::SUCCESS;
    }
}
