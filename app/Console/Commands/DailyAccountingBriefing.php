<?php

namespace App\Console\Commands;

use App\Models\CashTransaction;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\EmailStatsService;
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
        $unbooked = InvoicePayment::with('invoice')
            ->whereHas('invoice')
            ->whereNotIn('id', $bookedIds)
            ->get();

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
                $isAnomaly = $u->invoice && ((float)$u->amount > (float)$u->invoice->grand_total * 2 && (float)$u->amount >= 1000000);
                $anomalyTag = $isAnomaly ? " (⚠️ Anomali nominal vs invoice tagihan Rp " . number_format($u->invoice->grand_total, 0, ',', '.') . ")" : "";
                $out .= "     - " . ($u->invoice->invoice_number ?? ('invoice #' . $u->invoice_id))
                     . " · " . $rp($u->amount) . $anomalyTag . " — minta Kasir membukukan.\n";
            }
        }
        $out .= "\n";

        $out .= $this->bagianEmail();

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

    /**
     * Sinyal email yang menuntut tindakan.
     *
     * Layar Statistik Email hanya berguna kalau ada yang membukanya, dan
     * realistisnya sering terlewat. Briefing ini sudah sampai ke meja Direktur
     * tiap pagi — jadi angkanya dititipkan di sini supaya intelijennya sampai
     * tanpa siapa pun perlu membuka halaman.
     *
     * Hanya menampilkan yang perlu ditindaklanjuti; kalau semua bersih,
     * cukup satu baris supaya briefing tidak jadi panjang tanpa guna.
     */
    private function bagianEmail(): string
    {
        try {
            $stats = app(EmailStatsService::class)->untukPeriode(30);
            $kanal = $stats->kesehatanKanal();
            $ops   = $stats->operasional();
            $corong = $stats->corongBisnis();
        } catch (\Throwable $e) {
            // Briefing keuangan jauh lebih penting daripada bagian ini —
            // kalau statistik email bermasalah, briefingnya tetap terkirim.
            Log::warning('[finance:daily-briefing] bagian email dilewati: ' . $e->getMessage());

            return '';
        }

        $baris = [];

        if ($kanal['mental'] > 0) {
            $baris[] = "  ⚠️ {$kanal['mental']} email MENTAL 30 hari terakhir — alamat customer perlu diperbaiki";
        }
        if ($ops['menggantung'] > 0) {
            $baris[] = "  ⚠️ {$ops['menggantung']} email masuk menggantung >24 jam — kandidat jadi keluhan";
        }
        if ($kanal['mangkrak'] > 0) {
            $baris[] = "  ⚠️ {$kanal['mangkrak']} email tak dikabarkan nasibnya >1 jam — cek kredit & kredensial";
        }
        if ($corong['quotation_panas'] > 0) {
            $baris[] = "  🔥 {$corong['quotation_panas']} quotation dibuka ≥3× tanpa balasan — kandidat ditelepon hari ini";
        }
        if ($corong['terkirim_belum_dibuka'] > 0) {
            $baris[] = "  · {$corong['terkirim_belum_dibuka']} email sampai tapi belum dibuka — pertimbangkan susulan WhatsApp";
        }

        $out = "EMAIL\n";

        if (empty($baris)) {
            $out .= "  ✅ Tidak ada yang perlu ditindaklanjuti.\n";
        } else {
            $out .= implode("\n", $baris) . "\n";
        }

        if ($ops['menit_balas'] !== null) {
            $jam  = intdiv((int) $ops['menit_balas'], 60);
            $sisa = (int) $ops['menit_balas'] % 60;
            $lama = $jam > 0 ? "{$jam}j {$sisa}m" : "{$sisa} menit";
            $out .= "  Rata-rata waktu balas: {$lama}"
                . ($ops['menit_balas'] > 120 ? ' (di atas target 2 jam)' : '') . "\n";
        }

        return $out . "\n";
    }

    private function bulanId(Carbon $d): string
    {
        $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return ($bulan[$d->month] ?? $d->format('m')) . ' ' . $d->year;
    }
}
