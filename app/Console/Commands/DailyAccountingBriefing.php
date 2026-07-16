<?php

namespace App\Console\Commands;

use App\Models\CashTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * "Robot Accountant" — briefing accounting harian untuk owner.
 *
 * Selama M2B belum punya staf accounting khusus, command ini menjadi co-pilot:
 * tiap pagi merangkum posisi kas, piutang, item yang belum terbukukan, dan
 * pengingat, lalu mengirimkannya ke owner. TIDAK menggantikan akuntan — hanya
 * memunculkan apa yang perlu perhatian manusia + menjaga jejak.
 *
 * Hanya memakai data yang skema-nya sudah terverifikasi (invoices,
 * invoice_payments, cash_transactions) agar angka yang dilaporkan akurat.
 */
class DailyAccountingBriefing extends Command
{
    protected $signature = 'finance:daily-briefing
        {--send : Kirim email ke owner (default: hanya tampilkan di layar)}';

    protected $description = 'Briefing accounting harian (kas, piutang, item belum terbukukan, red-flag) untuk owner';

    private const TZ = 'Asia/Jakarta';

    public function handle(): int
    {
        $today = Carbon::now(self::TZ)->startOfDay();
        $report = $this->buildBriefing($today);

        $this->line($report);

        if ($this->option('send')) {
            $recipient = env('OWNER_BRIEFING_EMAIL', 'ekamayangsari01@gmail.com');
            if ($recipient) {
                try {
                    Mail::raw($report, function ($message) use ($recipient, $today) {
                        $message->to($recipient)
                            ->subject('[Portal M2B] Briefing Accounting — ' . $today->format('d M Y'));
                    });
                    $this->info("Briefing dikirim ke {$recipient}");
                } catch (\Throwable $e) {
                    Log::error('[finance:daily-briefing] gagal kirim: ' . $e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }

    private function buildBriefing(Carbon $today): string
    {
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $dateStr = $today->toDateString();
        $monthStart = (clone $today)->startOfMonth()->toDateString();

        // --- KAS ---
        $cashInToday  = (float) CashTransaction::whereDate('transaction_date', $dateStr)->where('type', 'in')->sum('amount');
        $cashOutToday = (float) CashTransaction::whereDate('transaction_date', $dateStr)->where('type', 'out')->sum('amount');
        $cashInMtd    = (float) CashTransaction::where('transaction_date', '>=', $monthStart)->where('type', 'in')->sum('amount');
        $cashOutMtd   = (float) CashTransaction::where('transaction_date', '>=', $monthStart)->where('type', 'out')->sum('amount');

        // --- PIUTANG (AR) ---
        $arAll = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COUNT(*) c, COALESCE(SUM(grand_total - COALESCE(total_paid,0)),0) sisa')->first();
        $arOverdue = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', $dateStr)
            ->selectRaw('COUNT(*) c, COALESCE(SUM(grand_total - COALESCE(total_paid,0)),0) sisa')->first();
        $arDueSoon = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->whereBetween('due_date', [$dateStr, (clone $today)->addDays(7)->toDateString()])
            ->selectRaw('COUNT(*) c, COALESCE(SUM(grand_total - COALESCE(total_paid,0)),0) sisa')->first();

        // --- BELUM TERBUKUKAN (cross-check) ---
        $bookedIds = DB::table('cash_transactions')->whereNotNull('invoice_payment_id')->pluck('invoice_payment_id');
        $unbooked = InvoicePayment::with('invoice')->whereNotIn('id', $bookedIds)->get();

        // --- SUSUN ---
        $out  = "ROBOT ACCOUNTANT M2B — BRIEFING HARIAN\n";
        $out .= $today->format('l, d F Y') . "\n" . str_repeat('=', 44) . "\n\n";

        $out .= "KAS\n";
        $out .= "  Hari ini : masuk {$rp($cashInToday)} · keluar {$rp($cashOutToday)}\n";
        $out .= "  Bulan ini: masuk {$rp($cashInMtd)} · keluar {$rp($cashOutMtd)} · net " . $rp($cashInMtd - $cashOutMtd) . "\n\n";

        $out .= "PIUTANG (belum lunas)\n";
        $out .= "  Total    : {$arAll->c} invoice · {$rp($arAll->sisa)}\n";
        $out .= "  Jatuh tempo 7 hari: {$arDueSoon->c} invoice · {$rp($arDueSoon->sisa)}\n";
        if ((int) $arOverdue->c > 0) {
            $out .= "  ⚠️ TERLAMBAT: {$arOverdue->c} invoice · {$rp($arOverdue->sisa)} — perlu ditagih\n";
        }
        $out .= "\n";

        $out .= "PERLU TINDAKAN\n";
        if ($unbooked->isEmpty()) {
            $out .= "  ✅ Semua pembayaran sudah masuk buku kas.\n";
        } else {
            $out .= "  ⚠️ {$unbooked->count()} pembayaran BELUM terbukukan (" . $rp($unbooked->sum('amount')) . "):\n";
            foreach ($unbooked as $u) {
                $out .= "     - " . ($u->invoice->invoice_number ?? ('invoice #' . $u->invoice_id))
                     . " · " . $rp($u->amount) . " — minta Kasir membukukan.\n";
            }
        }
        $out .= "\n";

        // --- PENGINGAT PAJAK (menjelang akhir bulan) ---
        if ($today->day >= 20) {
            $prev = (clone $today)->subMonthNoOverflow();
            $out .= "PENGINGAT PAJAK\n";
            $out .= "  Menjelang akhir bulan — siapkan kewajiban pajak masa {$this->bulanId($prev)}\n";
            $out .= "  (PPN/PPh, faktur pajak, SPT Masa). Konfirmasi tenggat & detail dengan konsultan pajak.\n\n";
        }

        $out .= str_repeat('-', 44) . "\n";
        $out .= "Catatan: ini alat bantu (co-pilot), bukan pengganti akuntan. Angka diambil\n";
        $out .= "dari data portal — keputusan & pelaporan pajak tetap perlu tinjauan manusia.\n";

        return $out;
    }

    private function bulanId(Carbon $d): string
    {
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return ($bulan[$d->month] ?? $d->format('m')) . ' ' . $d->year;
    }
}
