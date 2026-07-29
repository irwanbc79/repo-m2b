<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EmailDelivery;
use Illuminate\Support\Carbon;

/**
 * Angka-angka untuk layar Statistik Pusat Email.
 *
 * Disaring dengan satu aturan: sebuah angka hanya layak ditampilkan bila
 * mengubah tindakan seseorang besok pagi. Yang sekadar enak dilihat tapi
 * tidak menuntun ke apa pun, tidak masuk.
 *
 * Sumbernya dua tabel yang berbeda tugas — `emails` (masuk, dari IMAP) dan
 * `email_deliveries` (keluar, dari listener). Sengaja tidak digabung jadi
 * satu tabel: keduanya punya siklus hidup sendiri, dan menjaga tabel
 * gabungan tetap sinkron justru menambah cara untuk salah.
 */
class EmailStatsService
{
    /** Ambang "menggantung" untuk email masuk (jam). */
    public const AMBANG_MENGGANTUNG_JAM = 24;

    /** Ambang "mangkrak" untuk email keluar (menit). */
    public const AMBANG_MANGKRAK_MENIT = 60;

    public function __construct(private int $hariKebelakang = 30)
    {
    }

    public function untukPeriode(int $hari): self
    {
        return new self($hari);
    }

    private function sejak(): Carbon
    {
        return now()->subDays($this->hariKebelakang)->startOfDay();
    }

    /**
     * Kesehatan kanal — apakah email kita masih dipercaya server penerima.
     */
    public function kesehatanKanal(): array
    {
        $sejak = $this->sejak();

        $total = EmailDelivery::where('sent_at', '>=', $sejak)->count();

        $gagal = EmailDelivery::where('sent_at', '>=', $sejak)
            ->whereIn('status', [EmailDelivery::STATUS_BOUNCED, EmailDelivery::STATUS_FAILED])
            ->count();

        $sampai = EmailDelivery::where('sent_at', '>=', $sejak)
            ->whereIn('status', [
                EmailDelivery::STATUS_DELIVERED,
                EmailDelivery::STATUS_OPENED,
                EmailDelivery::STATUS_CLICKED,
            ])->count();

        return [
            'total_keluar'  => $total,
            'mental'        => $gagal,
            'mangkrak'      => EmailDelivery::stuck(self::AMBANG_MANGKRAK_MENIT)->count(),
            // null = belum ada data. Menampilkan 0% saat kosong itu
            // menyesatkan dan bisa memicu panik tanpa sebab.
            'rasio_sampai'  => $total > 0 ? round($sampai / $total * 100, 1) : null,
        ];
    }

    /**
     * Operasional harian — seberapa cepat kita menjawab customer.
     */
    public function operasional(): array
    {
        $sejak = $this->sejak();

        $belumDibalas = Email::belumDibalas()->count();
        $menggantung  = Email::menggantung(self::AMBANG_MENGGANTUNG_JAM)->count();

        return [
            'masuk_periode'  => Email::where('email_date', '>=', $sejak)->count(),
            'belum_dibalas'  => $belumDibalas,
            'menggantung'    => $menggantung,
            'menit_balas'    => $this->rataRataMenitBalas($sejak),
            'per_akun'       => $this->belumDibalasPerAkun(),
        ];
    }

    /**
     * Rata-rata jeda antara email masuk dan balasannya, dalam menit.
     *
     * Dihitung di PHP, bukan lewat fungsi tanggal SQL, supaya hasilnya sama
     * di MySQL (production) maupun SQLite (test) — portal ini sudah punya
     * satu halaman yang rusak di SQLite gara-gara memakai MONTH().
     */
    private function rataRataMenitBalas(Carbon $sejak): ?float
    {
        $dibalas = Email::whereNotNull('replied_at')
            ->where('email_date', '>=', $sejak)
            ->get(['email_date', 'replied_at']);

        if ($dibalas->isEmpty()) {
            return null;
        }

        $totalMenit = $dibalas->sum(function ($email) {
            if (! $email->email_date || ! $email->replied_at) {
                return 0;
            }

            // Jaga-jaga bila jam server sempat bergeser: jeda negatif
            // diabaikan daripada mengotori rata-rata.
            return max(0, $email->email_date->diffInMinutes($email->replied_at, false));
        });

        return round($totalMenit / $dibalas->count(), 1);
    }

    /**
     * Sebaran email belum dibalas per akun (sales@, import@, dst) — supaya
     * jelas antrean menumpuk di meja siapa.
     */
    private function belumDibalasPerAkun(): array
    {
        return Email::belumDibalas()
            ->selectRaw('mailbox, COUNT(*) as jumlah')
            ->groupBy('mailbox')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'mailbox')
            ->toArray();
    }

    /**
     * Corong bisnis — sinyal untuk sales & penagihan.
     */
    public function corongBisnis(): array
    {
        $sejak = $this->sejak();

        $invoiceTerkirim = EmailDelivery::where('sent_at', '>=', $sejak)
            ->where('related_type', \App\Models\Invoice::class)->count();

        $invoiceDibuka = EmailDelivery::where('sent_at', '>=', $sejak)
            ->where('related_type', \App\Models\Invoice::class)
            ->where('open_count', '>', 0)->count();

        return [
            'invoice_terkirim' => $invoiceTerkirim,
            'rasio_invoice_dibuka' => $invoiceTerkirim > 0
                ? round($invoiceDibuka / $invoiceTerkirim * 100, 1)
                : null,
            // Dibuka berkali-kali tanpa balasan = sedang dipertimbangkan
            // internal mereka. Untuk quotation, ini sinyal paling panas.
            'quotation_panas' => EmailDelivery::where('sent_at', '>=', $sejak)
                ->where('related_type', \App\Models\Quotation::class)
                ->where('open_count', '>=', 3)->count(),
            'terkirim_belum_dibuka' => EmailDelivery::where('sent_at', '>=', $sejak)
                ->where('status', EmailDelivery::STATUS_DELIVERED)
                ->where('open_count', 0)->count(),
        ];
    }

    /**
     * Daftar pendek yang bisa langsung ditindaklanjuti — tiap angka di atas
     * harus bermuara ke sini, bukan berhenti sebagai angka.
     */
    public function perluTindakan(int $batas = 10): array
    {
        return [
            'mental' => EmailDelivery::whereIn('status', [
                    EmailDelivery::STATUS_BOUNCED,
                    EmailDelivery::STATUS_FAILED,
                ])
                ->where('sent_at', '>=', $this->sejak())
                ->latest('sent_at')->limit($batas)->get(),

            'menggantung' => Email::menggantung(self::AMBANG_MENGGANTUNG_JAM)
                ->orderBy('email_date')->limit($batas)->get(),

            'mangkrak' => EmailDelivery::stuck(self::AMBANG_MANGKRAK_MENIT)
                ->latest('sent_at')->limit($batas)->get(),
        ];
    }
}
