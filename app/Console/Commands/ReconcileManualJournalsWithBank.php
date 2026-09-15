<?php

namespace App\Console\Commands;

use App\Models\BankTransaction;
use App\Models\Journal;
use App\Models\InvoicePayment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileManualJournalsWithBank extends Command
{
    protected $signature = 'finance:reconcile-manual-journals
                            {--from=2026-01-01 : Start date}
                            {--to=2026-04-30 : End date}
                            {--bank=mandiri : Bank name filter}
                            {--user-id=114 : User ID who matched, default Kinan}
                            {--dry-run : Preview matching without modifying database}';

    protected $description = 'Match and reconcile manual journal entries with bank transactions for Bank Reconciliation menu.';

    protected array $knownFees = [1250, 2500, 2900, 3000, 6500];

    public function handle(): int
    {
        $fromDate = $this->option('from');
        $toDate = $this->option('to');
        $bankName = $this->option('bank');
        $userId = (int) $this->option('user-id');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("===============================================================");
        $this->info(" BANK RECONCILIATION: MANUAL JOURNALS & BANK TRANSACTIONS");
        $this->info("===============================================================");
        $this->line("Periode      : {$fromDate} s/d {$toDate}");
        $this->line("Bank         : {$bankName}");
        $this->line("Operator ID  : {$userId} (Kinan)");
        $this->line("Mode         : " . ($dryRun ? "🔍 DRY-RUN (Preview saja, tidak ada write ke DB)" : "🚀 APPLY (Eksekusi perubahan ke DB)"));
        $this->newLine();

        // 1. Ambil Bank Transactions yang belum direkonsiliasi
        $bankQuery = BankTransaction::query()
            ->where('bank_name', $bankName)
            ->whereBetween('transaction_date', ["{$fromDate} 00:00:00", "{$toDate} 23:59:59"])
            ->orderBy('transaction_date')
            ->orderBy('id');

        $totalInPeriod = (clone $bankQuery)->count();
        $alreadyReconciled = (clone $bankQuery)->where('is_reconciled', true)->count();
        $unreconciled = (clone $bankQuery)->where('is_reconciled', false)->get();

        $this->info("Status Awal:");
        $this->line("  Total Transaksi Bank di Periode : {$totalInPeriod}");
        $this->line("  Sudah Direkonsiliasi            : {$alreadyReconciled}");
        $this->line("  Belum Direkonsiliasi (Pending)  : {$unreconciled->count()}");
        $this->newLine();

        if ($unreconciled->isEmpty()) {
            $this->info("Semua transaksi bank sudah direkonsiliasi!");
            return self::SUCCESS;
        }

        // 2. Ambil Jurnal yang menyentuh akun Bank Mandiri (1103)
        $journals = Journal::with(['items.account'])
            ->whereBetween('transaction_date', [$fromDate, "{$toDate} 23:59:59"])
            ->orderBy('transaction_date')
            ->get();

        $mandiriJournals = [];
        foreach ($journals as $j) {
            $mIn = 0;
            $mOut = 0;
            foreach ($j->items as $item) {
                if ($item->account && $item->account->code === '1103') {
                    $mIn += (float) $item->debit;   // Debit 1103 = Uang Masuk ke Bank
                    $mOut += (float) $item->credit; // Credit 1103 = Uang Keluar dari Bank
                }
            }

            if ($mIn > 0 || $mOut > 0) {
                $mandiriJournals[] = [
                    'id' => $j->id,
                    'journal_number' => $j->journal_number,
                    'date' => $j->transaction_date ? $j->transaction_date->format('Y-m-d') : null,
                    'description' => $j->description,
                    'money_in' => $mIn,
                    'money_out' => $mOut,
                    'type' => $mIn > 0 ? 'IN' : 'OUT',
                    'amount' => max($mIn, $mOut),
                ];
            }
        }

        $this->line("Ditemukan " . count($mandiriJournals) . " Jurnal Umum yang menyentuh Akun 1103 (Bank Mandiri).");
        $this->newLine();

        $matchedBtIds = [];
        $usedJournalIds = [];
        $matches = [];

        // -------------------------------------------------------------
        // PASS 1: Exact 1:1 Match (Nominal sama & Selisih Tanggal <= 3 hari)
        // -------------------------------------------------------------
        foreach ($unreconciled as $bt) {
            if (in_array($bt->id, $matchedBtIds)) continue;

            $isCredit = (float) $bt->credit_amount > 0;
            $amt = $isCredit ? (float) $bt->credit_amount : (float) $bt->debit_amount;
            $date = $bt->transaction_date ? $bt->transaction_date->format('Y-m-d') : null;

            $best = null;
            $minDiff = 999;

            foreach ($mandiriJournals as $j) {
                if (in_array($j['id'], $usedJournalIds)) continue;

                $jAmt = $isCredit ? $j['money_in'] : $j['money_out'];
                if (abs($jAmt - $amt) < 0.01) {
                    $diff = abs((strtotime($date) - strtotime($j['date'])) / 86400);
                    if ($diff <= 3 && $diff < $minDiff) {
                        $minDiff = $diff;
                        $best = $j;
                    }
                }
            }

            if ($best) {
                $matches[] = [
                    'bt_id' => $bt->id,
                    'journal_id' => $best['id'],
                    'journal_number' => $best['journal_number'],
                    'bt_desc' => $bt->description,
                    'j_desc' => $best['description'],
                    'amount' => $amt,
                    'type' => $isCredit ? 'CREDIT (Masuk)' : 'DEBIT (Keluar)',
                    'bt_date' => $date,
                    'j_date' => $best['date'],
                    'diff' => $minDiff,
                    'pass' => 'Pass 1 (1:1 Exact)',
                    'notes' => "Matched 1:1 with {$best['journal_number']}",
                ];
                $matchedBtIds[] = $bt->id;
                $usedJournalIds[] = $best['id'];
            }
        }

        $this->info("✓ Pass 1 selesai: " . count($matches) . " transaksi bank berhasil di-match 1:1.");

        // -------------------------------------------------------------
        // PASS 2: Bundled Principal + Transfer Fee (1.250 / 2.500 / 2.900 / 3.000 / 6.500)
        // -------------------------------------------------------------
        $p2Count = 0;
        foreach ($unreconciled as $bt) {
            if (in_array($bt->id, $matchedBtIds)) continue;
            if ((float) $bt->debit_amount <= 0) continue;

            $amt = (float) $bt->debit_amount;
            $date = $bt->transaction_date ? $bt->transaction_date->format('Y-m-d') : null;

            foreach ($this->knownFees as $fee) {
                $potentialTotal = $amt + $fee;

                foreach ($mandiriJournals as $j) {
                    if (in_array($j['id'], $usedJournalIds)) continue;
                    if ($j['type'] === 'OUT' && abs($j['money_out'] - $potentialTotal) < 0.01) {
                        $diff = abs((strtotime($date) - strtotime($j['date'])) / 86400);
                        if ($diff <= 3) {
                            // Cari transaksi fee yang berpasangan
                            $foundFeeBt = null;
                            foreach ($unreconciled as $fBt) {
                                if (!in_array($fBt->id, $matchedBtIds) && $fBt->id !== $bt->id) {
                                    if ((float) $fBt->debit_amount == $fee && abs((strtotime($fBt->transaction_date->format('Y-m-d')) - strtotime($date)) / 86400) <= 2) {
                                        $foundFeeBt = $fBt;
                                        break;
                                    }
                                }
                            }

                            if ($foundFeeBt) {
                                // Match pokok
                                $matches[] = [
                                    'bt_id' => $bt->id,
                                    'journal_id' => $j['id'],
                                    'journal_number' => $j['journal_number'],
                                    'bt_desc' => $bt->description,
                                    'j_desc' => $j['description'],
                                    'amount' => $amt,
                                    'type' => 'DEBIT (Pokok)',
                                    'bt_date' => $date,
                                    'j_date' => $j['date'],
                                    'diff' => $diff,
                                    'pass' => 'Pass 2 (Bundled Pokok)',
                                    'notes' => "Matched with {$j['journal_number']} (pokok Rp " . number_format($amt, 0, ',', '.') . " + fee Rp " . number_format($fee, 0, ',', '.') . ")",
                                ];
                                // Match biaya transfer
                                $matches[] = [
                                    'bt_id' => $foundFeeBt->id,
                                    'journal_id' => $j['id'],
                                    'journal_number' => $j['journal_number'],
                                    'bt_desc' => $foundFeeBt->description,
                                    'j_desc' => $j['description'],
                                    'amount' => $fee,
                                    'type' => 'DEBIT (Fee)',
                                    'bt_date' => $foundFeeBt->transaction_date->format('Y-m-d'),
                                    'j_date' => $j['date'],
                                    'diff' => $diff,
                                    'pass' => 'Pass 2 (Bundled Fee)',
                                    'notes' => "Matched with {$j['journal_number']} (biaya transfer Rp " . number_format($fee, 0, ',', '.') . ")",
                                ];

                                $matchedBtIds[] = $bt->id;
                                $matchedBtIds[] = $foundFeeBt->id;
                                $usedJournalIds[] = $j['id'];
                                $p2Count += 2;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        $this->info("✓ Pass 2 selesai: {$p2Count} transaksi bank (pasangan pokok + biaya admin) berhasil di-match.");

        // -------------------------------------------------------------
        // PASS 3: Extended Date Drift (4 s/d 14 Hari)
        // -------------------------------------------------------------
        $p3Count = 0;
        foreach ($unreconciled as $bt) {
            if (in_array($bt->id, $matchedBtIds)) continue;

            $isCredit = (float) $bt->credit_amount > 0;
            $amt = $isCredit ? (float) $bt->credit_amount : (float) $bt->debit_amount;
            $date = $bt->transaction_date ? $bt->transaction_date->format('Y-m-d') : null;

            $best = null;
            $minDiff = 999;

            foreach ($mandiriJournals as $j) {
                if (in_array($j['id'], $usedJournalIds)) continue;

                $jAmt = $isCredit ? $j['money_in'] : $j['money_out'];
                if (abs($jAmt - $amt) < 0.01) {
                    $diff = abs((strtotime($date) - strtotime($j['date'])) / 86400);
                    if ($diff <= 14 && $diff < $minDiff) {
                        $minDiff = $diff;
                        $best = $j;
                    }
                }
            }

            if ($best) {
                $matches[] = [
                    'bt_id' => $bt->id,
                    'journal_id' => $best['id'],
                    'journal_number' => $best['journal_number'],
                    'bt_desc' => $bt->description,
                    'j_desc' => $best['description'],
                    'amount' => $amt,
                    'type' => $isCredit ? 'CREDIT (Masuk)' : 'DEBIT (Keluar)',
                    'bt_date' => $date,
                    'j_date' => $best['date'],
                    'diff' => $minDiff,
                    'pass' => 'Pass 3 (Extended Drift)',
                    'notes' => "Matched with {$best['journal_number']} (Selisih tanggal {$minDiff} hari: Bank {$date} vs Jurnal {$best['date']})",
                ];
                $matchedBtIds[] = $bt->id;
                $usedJournalIds[] = $best['id'];
                $p3Count++;
            }
        }

        $this->info("✓ Pass 3 selesai: {$p3Count} transaksi bank (selisih tanggal 4-14 hari) berhasil di-match.");
        $this->newLine();

        $totalMatched = count($matches);
        $remainingBt = $unreconciled->filter(fn($b) => !in_array($b->id, $matchedBtIds));

        $this->info("HASIL AKHIR PENCOCOKAN:");
        $this->table(
            ['Metode Pencocokan', 'Jumlah Transaksi'],
            [
                ['Pass 1 (1:1 Exact Match, <= 3 hari)', count(array_filter($matches, fn($m) => $m['pass'] === 'Pass 1 (1:1 Exact)'))],
                ['Pass 2 (Bundled Pokok + Fee Bank)', $p2Count],
                ['Pass 3 (Extended Date Drift 4-14 hari)', $p3Count],
                ['TOTAL BERHASIL DI-MATCH', $totalMatched],
                ['Sisa Belum Di-match', $remainingBt->count()],
            ]
        );

        $this->newLine();
        $this->info("Persentase Rekonsiliasi:");
        $newReconciledTotal = $alreadyReconciled + $totalMatched;
        $pctBefore = round($alreadyReconciled / $totalInPeriod * 100, 1);
        $pctAfter = round($newReconciledTotal / $totalInPeriod * 100, 1);
        $this->line("  Sebelum : {$alreadyReconciled} / {$totalInPeriod} ({$pctBefore}%)");
        $this->line("  Sesudah : {$newReconciledTotal} / {$totalInPeriod} ({$pctAfter}%)  [+{$totalMatched} transaksi]");
        $this->newLine();

        // Tampilkan sisa yang belum match
        if ($remainingBt->isNotEmpty()) {
            $this->warn("Daftar {$remainingBt->count()} Transaksi Bank yang Belum Match (Bunga Bank / Pajak / Invoice):");
            $remRows = [];
            foreach ($remainingBt as $rb) {
                $amt = (float) $rb->credit_amount > 0 ? '+' . number_format($rb->credit_amount, 0, ',', '.') : '-' . number_format($rb->debit_amount, 0, ',', '.');
                $remRows[] = [
                    'ID' => $rb->id,
                    'Tanggal' => $rb->transaction_date ? $rb->transaction_date->format('Y-m-d') : '-',
                    'Nominal' => $amt,
                    'Keterangan' => substr($rb->description, 0, 50),
                ];
            }
            $this->table(['ID', 'Tanggal', 'Nominal', 'Keterangan'], array_slice($remRows, 0, 15));
            if (count($remRows) > 15) {
                $this->line("  ... dan " . (count($remRows) - 15) . " transaksi lainnya.");
            }
            $this->newLine();
        }

        // Eksekusi ke Database jika BUKAN dry-run
        if ($dryRun) {
            $this->warn("Mode Dry-Run selesai. Tidak ada perubahan yang disimpan ke database.");
            $this->line("Jalankan tanpa flag --dry-run untuk menyimpan hasil rekonsiliasi.");
            return self::SUCCESS;
        }

        $this->info("Menyimpan hasil rekonsiliasi ke database...");
        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $appliedCount = 0;

            foreach ($matches as $m) {
                BankTransaction::where('id', $m['bt_id'])->update([
                    'is_reconciled' => true,
                    'journal_id' => $m['journal_id'],
                    'matched_by' => $userId,
                    'matched_at' => $now,
                    'matching_notes' => $m['notes'],
                ]);
                $appliedCount++;
            }

            DB::commit();
            $this->info("✅ Berhasil memperbarui {$appliedCount} transaksi bank di database!");
            $this->info("Semua transaksi di atas kini berstatus ✅ Matched di menu Rekonsiliasi Bank.");
            Log::info("finance:reconcile-manual-journals applied", [
                'applied_count' => $appliedCount,
                'period' => "{$fromDate} - {$toDate}",
                'user_id' => $userId,
            ]);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Gagal menyimpan rekonsiliasi: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
