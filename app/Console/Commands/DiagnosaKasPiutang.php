<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Diagnosa saldo Kas & Piutang — HANYA MEMBACA, tidak mengubah apa pun.
 *
 * Menyiapkan angka sisi portal untuk dibandingkan dengan rekening koran Bank
 * Mandiri, supaya bisa ditentukan koreksinya Skenario A (reklas ke bank) atau
 * Skenario B (pencatatan ganda).
 */
class DiagnosaKasPiutang extends Command
{
    protected $signature = 'kas:diagnosa
                            {--akun= : Tampilkan mutasi bulanan satu akun (kode akun)}
                            {--mutasi=10 : Jumlah transaksi terbesar yang ditampilkan pada mode --akun}';

    protected $description = 'Diagnosa saldo kas, bank & piutang (read-only) — bahan penentuan koreksi';

    public function handle(): int
    {
        if ($kode = $this->option('akun')) {
            return $this->detailAkun($kode);
        }

        $this->ringkasanSaldo();
        $this->jejakBugPiutang();
        $this->line('');
        $this->line('Drill-down: php artisan kas:diagnosa --akun=1101');

        return self::SUCCESS;
    }

    /**
     * Saldo tersimpan (kolom current_balance) vs saldo hasil hitung ulang dari
     * journal_items. Selisih di antara keduanya = drift.
     */
    private function ringkasanSaldo(): void
    {
        // Subquery, bukan join+GROUP BY: MySQL produksi menjalankan
        // ONLY_FULL_GROUP_BY sehingga `select accounts.* ... group by
        // accounts.id` ditolak (SQLite lokal membolehkannya).
        $accounts = Account::query()
            ->whereIn('type', ['kas_bank', 'piutang'])
            ->withSum('journalItems as total_debit', 'debit')
            ->withSum('journalItems as total_credit', 'credit')
            ->withCount('journalItems as jumlah_baris')
            ->orderBy('code')
            ->get();

        $baris = $accounts->map(function (Account $a) {
            // withSum mengembalikan null bila akunnya belum punya baris jurnal;
            // dinolkan dulu supaya accessor saldo tidak jatuh ke query per akun.
            $a->setAttribute('total_debit', $a->total_debit ?? 0);
            $a->setAttribute('total_credit', $a->total_credit ?? 0);

            $gl = (float) $a->calculated_balance;
            $kolom = (float) $a->current_balance;
            $drift = $kolom - $gl;

            return [
                $a->code,
                Str::limit($a->name, 28),
                (int) $a->jumlah_baris,
                $this->rp($kolom),
                $this->rp($gl),
                abs($drift) < 0.01 ? '-' : $this->rp($drift),
                $gl < 0 ? 'MINUS' : '',
            ];
        })->toArray();

        $this->newLine();
        $this->info('SALDO KAS, BANK & PIUTANG');
        $this->table(
            ['Kode', 'Nama Akun', 'Baris', 'Saldo Kolom', 'Saldo Buku Besar', 'Drift', 'Tanda'],
            $baris
        );

        $minus = $accounts->filter(fn ($a) => (float) $a->calculated_balance < 0);
        if ($minus->isNotEmpty()) {
            $this->warn('Akun bersaldo minus: ' . $minus->pluck('code')->implode(', '));
        }
    }

    /**
     * Bukti langsung bug piutang: invoice yang terbit tapi tidak punya jurnal
     * penerbitan (reference_no 'INV-<id>'), jadi piutangnya tidak pernah
     * didebit.
     */
    private function jejakBugPiutang(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('invoices')) {
            return;
        }

        $totalInvoice = DB::table('invoices')->count();

        $tanpaJurnalTerbit = DB::table('invoices')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('journals')
                    ->whereColumn('journals.reference_no', DB::raw($this->ekspresiRef('INV-', 'invoices.id')));
            })
            ->count();

        $adaJurnalBayar = DB::table('invoices')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('journals')
                    ->whereColumn('journals.reference_no', DB::raw($this->ekspresiRef('PAY-', 'invoices.id')));
            })
            ->count();

        $this->newLine();
        $this->info('JEJAK BUG PIUTANG (invoice terbit vs jurnalnya)');
        $this->table(['Keterangan', 'Jumlah'], [
            ['Total invoice', number_format($totalInvoice, 0, ',', '.')],
            ['Tanpa jurnal penerbitan (piutang tak pernah didebit)', number_format($tanpaJurnalTerbit, 0, ',', '.')],
            ['Punya jurnal pembayaran (piutang dikredit)', number_format($adaJurnalBayar, 0, ',', '.')],
        ]);

        if ($tanpaJurnalTerbit > 0 && $adaJurnalBayar > 0) {
            $this->warn('Terkonfirmasi: piutang dikredit saat bayar tapi tidak pernah didebit saat terbit.');
        }
    }

    /**
     * Ekspresi SQL untuk mengambil "YYYY-MM" — produksi MySQL, lokal SQLite.
     */
    private function ekspresiBulan(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', journals.transaction_date)"
            : "DATE_FORMAT(journals.transaction_date, '%Y-%m')";
    }

    /**
     * Penggabungan string: MySQL pakai CONCAT, SQLite pakai ||.
     */
    private function ekspresiRef(string $awalan, string $kolom): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "('{$awalan}' || {$kolom})"
            : "CONCAT('{$awalan}', {$kolom})";
    }

    /**
     * Mutasi bulanan + transaksi terbesar pada satu akun.
     */
    private function detailAkun(string $kode): int
    {
        $account = Account::where('code', $kode)->first();

        if (! $account) {
            $this->error("Akun {$kode} tidak ditemukan.");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("MUTASI BULANAN — {$account->code} {$account->name}");

        $mutasi = DB::table('journal_items')
            ->join('journals', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.account_id', $account->id)
            ->groupBy(DB::raw($this->ekspresiBulan()))
            ->orderBy(DB::raw($this->ekspresiBulan()))
            ->select([
                DB::raw($this->ekspresiBulan() . ' as bulan'),
                DB::raw('SUM(journal_items.debit) as debit'),
                DB::raw('SUM(journal_items.credit) as kredit'),
                DB::raw('COUNT(*) as jumlah'),
            ])
            ->get();

        $saldo = (float) $account->opening_balance;
        $normalDebit = $account->isDebitNormal();

        $baris = $mutasi->map(function ($m) use (&$saldo, $normalDebit) {
            $saldo += $normalDebit
                ? ((float) $m->debit - (float) $m->kredit)
                : ((float) $m->kredit - (float) $m->debit);

            return [$m->bulan, (int) $m->jumlah, $this->rp($m->debit), $this->rp($m->kredit), $this->rp($saldo)];
        })->toArray();

        $this->table(['Bulan', 'Transaksi', 'Debit', 'Kredit', 'Saldo Akhir'], $baris);

        $this->newLine();
        $this->info('TRANSAKSI TERBESAR DI AKUN INI');

        $besar = DB::table('journal_items')
            ->join('journals', 'journals.id', '=', 'journal_items.journal_id')
            ->leftJoin('users', 'users.id', '=', 'journals.created_by')
            ->where('journal_items.account_id', $account->id)
            ->orderByDesc(DB::raw('journal_items.debit + journal_items.credit'))
            ->limit((int) $this->option('mutasi'))
            ->select([
                'journals.transaction_date',
                'journals.journal_number',
                'journals.reference_no',
                'journals.description',
                'journal_items.debit',
                'journal_items.credit',
                'users.name as pembuat',
            ])
            ->get();

        $this->table(
            ['Tanggal', 'No. Jurnal', 'Referensi', 'Keterangan', 'Debit', 'Kredit', 'Input'],
            $besar->map(fn ($t) => [
                $t->transaction_date,
                $t->journal_number,
                $t->reference_no ?: '-',
                Str::limit($t->description ?: '-', 34),
                $this->rp($t->debit),
                $this->rp($t->credit),
                $t->pembuat ?: '-',
            ])->toArray()
        );

        return self::SUCCESS;
    }

    private function rp($angka): string
    {
        return number_format((float) $angka, 0, ',', '.');
    }
}
