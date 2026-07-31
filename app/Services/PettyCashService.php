<?php
namespace App\Services;

use App\Models\PettyCashFund;
use App\Models\PettyCashTransaction;
use App\Models\PettyCashTopup;
use App\Models\PettyCashSettingLog;
use App\Models\PettyCashTransactionLog;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class PettyCashService
{
    // COA sesuai M2B existing
    const COA_PETTY_CASH = '1102';  // Kas Kecil (Petty Cash)
    const COA_KAS_BESAR = '1101';   // Kas Besar (untuk top-up)

    /**
     * Buat transaksi pengeluaran kas kecil
     */
    public function createTransaction(PettyCashFund $fund, array $data): PettyCashTransaction
    {
        return DB::transaction(function () use ($fund, $data) {
            // Validasi
            if (!$fund->canSpend($data['amount'])) {
                throw new Exception('Saldo tidak cukup atau melebihi limit Rp' . number_format($fund->max_transaction, 0, ',', '.'));
            }
            if (empty($data['proof_file'])) {
                throw new Exception('Bukti transaksi wajib diupload');
            }

            $balanceBefore = $fund->current_balance;
            $balanceAfter = $balanceBefore - $data['amount'];

            // Simpan transaksi (auto-approve untuk pemegang kas)
            $transaction = PettyCashTransaction::create([
                'petty_cash_fund_id' => $fund->id,
                'transaction_number' => PettyCashTransaction::generateNumber(),
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'amount' => $data['amount'],
                'category' => $data['category'],
                'description' => $data['description'],
                'shipment_id' => $data['shipment_id'] ?? null,
                'proof_file' => $data['proof_file'],
                'status' => 'approved',
                'created_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            // Update saldo
            $fund->update(['current_balance' => $balanceAfter]);

            // Buat jurnal otomatis
            $this->createExpenseJournal($transaction);

            return $transaction;
        });
    }

    /**
     * Ubah transaksi kas kecil yang sudah tercatat.
     *
     * Perubahan yang menyentuh buku besar (jumlah, kategori, tanggal) TIDAK
     * menyunting jurnal lama. Jurnal lama dibalik dengan jurnal koreksi, lalu
     * dibuat jurnal baru sesuai nilai yang benar.
     *
     * Alasannya konkret: SELURUH laporan akuntansi portal ini (General Ledger,
     * Trial Balance, Laba Rugi, Neraca) membaca journal_items TANPA menyaring
     * status jurnal. Jadi menandai jurnal "batal" tidak akan mengeluarkan
     * angkanya dari laporan — satu-satunya cara yang benar-benar menihilkan
     * adalah jurnal balik. Sekalian, buku besar jadi menyimpan riwayat
     * koreksinya sendiri, bukan berubah diam-diam.
     *
     * @param array $data field yang boleh diubah
     */
    public function updateTransaction(PettyCashTransaction $t, array $data, ?string $reason = null): PettyCashTransaction
    {
        return DB::transaction(function () use ($t, $data, $reason) {
            if ($t->status === 'cancelled') {
                throw new Exception('Transaksi yang sudah dibatalkan tidak bisa diubah.');
            }

            $fund = $t->fund()->lockForUpdate()->first();

            $sebelum = $t->only([
                'amount', 'category', 'description', 'transaction_date', 'shipment_id', 'proof_file',
            ]);

            $baru = [
                'amount'           => isset($data['amount']) ? (float) $data['amount'] : (float) $t->amount,
                'category'         => $data['category'] ?? $t->category,
                'description'      => $data['description'] ?? $t->description,
                'transaction_date' => $data['transaction_date'] ?? $t->transaction_date,
                'shipment_id'      => array_key_exists('shipment_id', $data) ? $data['shipment_id'] : $t->shipment_id,
                'proof_file'       => $data['proof_file'] ?? $t->proof_file,
            ];

            $selisih = $baru['amount'] - (float) $t->amount;

            // Batas per transaksi tetap berlaku untuk nilai barunya.
            if ($baru['amount'] > $fund->max_transaction) {
                throw new Exception('Jumlah melebihi batas per transaksi Rp' . number_format($fund->max_transaction, 0, ',', '.'));
            }
            if ($baru['amount'] <= 0) {
                throw new Exception('Jumlah harus lebih dari nol.');
            }
            // Hanya kenaikan yang perlu dicek saldo — penurunan selalu aman.
            if ($selisih > 0 && $fund->current_balance < $selisih) {
                throw new Exception('Saldo kas kecil tidak cukup untuk menaikkan jumlah sebesar Rp' . number_format($selisih, 0, ',', '.'));
            }

            $perubahan = $this->bedakan($sebelum, $baru);
            if (empty($perubahan)) {
                return $t;
            }

            $saldoBaru = $fund->current_balance - $selisih;

            $t->update($baru + [
                'balance_before' => $saldoBaru + $baru['amount'],
                'balance_after'  => $saldoBaru,
            ]);

            $fund->update(['current_balance' => $saldoBaru]);

            // Buku besar hanya perlu disentuh bila angkanya memang berubah.
            if ($this->menyentuhBukuBesar($perubahan)) {
                $this->reverseJournal($t, 'Koreksi');
                $this->createExpenseJournal($t->fresh());
            }

            $this->catatJejak($t, PettyCashTransactionLog::ACTION_UPDATED, $perubahan, $reason);

            return $t->fresh();
        });
    }

    /**
     * Batalkan transaksi: saldo dikembalikan, efeknya di buku besar ditiadakan
     * lewat jurnal balik, tapi barisnya TETAP ada dengan status dibatalkan.
     *
     * Sengaja tidak menghapus: nomor transaksi yang lompat tanpa penjelasan
     * justru menyulitkan penelusuran saat audit.
     */
    public function cancelTransaction(PettyCashTransaction $t, string $reason): PettyCashTransaction
    {
        return DB::transaction(function () use ($t, $reason) {
            if ($t->status === 'cancelled') {
                throw new Exception('Transaksi ini sudah dibatalkan.');
            }
            if (trim($reason) === '') {
                throw new Exception('Alasan pembatalan wajib diisi.');
            }

            $fund = $t->fund()->lockForUpdate()->first();

            $this->reverseJournal($t, 'Pembatalan');

            $fund->update(['current_balance' => $fund->current_balance + (float) $t->amount]);

            $t->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancelled_by'  => Auth::id(),
                'reject_reason' => $reason,
            ]);

            $this->catatJejak($t, PettyCashTransactionLog::ACTION_CANCELLED, [], $reason);

            return $t->fresh();
        });
    }

    /**
     * Buat jurnal yang meniadakan jurnal transaksi ini (debit-kredit ditukar).
     */
    protected function reverseJournal(PettyCashTransaction $t, string $sebab): void
    {
        $asal = $t->journal_id ? Journal::with('items')->find($t->journal_id) : null;

        if (! $asal) {
            // Tidak ada jurnal untuk dibalik (mis. COA belum lengkap saat
            // transaksi dibuat). Bukan alasan menggagalkan koreksi.
            \Log::warning("[kas-kecil] jurnal asal tidak ditemukan untuk transaksi {$t->transaction_number}");

            return;
        }

        $balik = Journal::create([
            'journal_number'   => 'JU-PCR-' . now()->format('Ymd') . '-' . str_pad(
                Journal::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
            ),
            'transaction_date' => now()->toDateString(),
            'description'      => "[Kas Kecil] {$sebab} atas {$asal->journal_number}: {$t->description}",
            'reference_no'     => $t->transaction_number,
            'status'           => 'posted',
            'created_by'       => Auth::id(),
            'posted_at'        => now(),
        ]);

        foreach ($asal->items as $item) {
            JournalItem::create([
                'journal_id'  => $balik->id,
                'account_id'  => $item->account_id,
                // Ditukar: inilah yang membuat efeknya nihil di laporan.
                'debit'       => $item->credit,
                'credit'      => $item->debit,
                'description' => "{$sebab}: {$item->description}",
            ]);
        }

        $t->update(['reversal_journal_id' => $balik->id]);
    }

    /**
     * Bandingkan nilai lama & baru, hanya kembalikan yang benar-benar berubah.
     */
    protected function bedakan(array $sebelum, array $sesudah): array
    {
        $perubahan = [];

        foreach ($sesudah as $field => $nilaiBaru) {
            $nilaiLama = $sebelum[$field] ?? null;

            // Tanggal & angka disamakan bentuknya dulu supaya perbedaan semu
            // (mis. "45000" vs 45000.00) tidak tercatat sebagai perubahan.
            if ($field === 'transaction_date') {
                $lama = $nilaiLama ? \Carbon\Carbon::parse($nilaiLama)->toDateString() : null;
                $baru = $nilaiBaru ? \Carbon\Carbon::parse($nilaiBaru)->toDateString() : null;
            } elseif ($field === 'amount') {
                $lama = (float) $nilaiLama;
                $baru = (float) $nilaiBaru;
            } else {
                $lama = $nilaiLama;
                $baru = $nilaiBaru;
            }

            if ($lama != $baru) {
                $perubahan[$field] = ['dari' => $lama, 'ke' => $baru];
            }
        }

        return $perubahan;
    }

    /**
     * Benar bila perubahannya mengubah angka/akun/tanggal di buku besar.
     * Ganti keterangan atau tautan job tidak perlu menyentuh jurnal.
     */
    protected function menyentuhBukuBesar(array $perubahan): bool
    {
        return (bool) array_intersect(array_keys($perubahan), ['amount', 'category', 'transaction_date']);
    }

    protected function catatJejak(PettyCashTransaction $t, string $action, array $perubahan, ?string $reason): void
    {
        PettyCashTransactionLog::create([
            'petty_cash_transaction_id' => $t->id,
            'action'                    => $action,
            'changes'                   => $perubahan ?: null,
            'reason'                    => $reason,
            'changed_by'                => Auth::id(),
            'changed_by_name'           => Auth::user()?->name,
        ]);
    }

    /**
     * Buat jurnal pengeluaran: Debit Beban, Kredit Kas Kecil
     */
    protected function createExpenseJournal(PettyCashTransaction $t): void
    {
        $expenseCode = $t->category_coa;
        $expenseAcc = Account::where('code', $expenseCode)->first();
        $pcAcc = Account::where('code', self::COA_PETTY_CASH)->first();

        if (!$expenseAcc || !$pcAcc) {
            \Log::warning("COA kas kecil tidak ditemukan: expense={$expenseCode}, pc=" . self::COA_PETTY_CASH);
            return;
        }

        // Generate journal number sesuai format M2B
        $journalNumber = 'JU-PC-' . now()->format('Ymd') . '-' . str_pad(
            Journal::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
        );

        $journal = Journal::create([
            'journal_number' => $journalNumber,
            'transaction_date' => $t->transaction_date,
            'description' => "[Kas Kecil] {$t->category_label}: {$t->description}",
            'reference_no' => $t->transaction_number,
            'status' => 'posted',
            'created_by' => Auth::id(),
            'posted_at' => now(),
        ]);

        // Debit: Beban
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $expenseAcc->id,
            'debit' => $t->amount,
            'credit' => 0,
            'description' => $t->description,
        ]);

        // Kredit: Kas Kecil
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $pcAcc->id,
            'debit' => 0,
            'credit' => $t->amount,
            'description' => $t->description,
        ]);

        $t->update(['journal_id' => $journal->id]);
    }

    /**
     * Request top-up kas kecil
     */
    public function requestTopup(PettyCashFund $fund, float $amount, ?string $notes = null): PettyCashTopup
    {
        if ($amount > $fund->max_topup_amount) {
            throw new Exception("Max top up: Rp" . number_format($fund->max_topup_amount, 0, ',', '.'));
        }

        return PettyCashTopup::create([
            'petty_cash_fund_id' => $fund->id,
            'topup_number' => PettyCashTopup::generateNumber(),
            'amount_requested' => $amount,
            'balance_before' => $fund->current_balance,
            'status' => 'pending',
            'requested_by' => Auth::id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Approve request top-up
     */
    public function approveTopup(PettyCashTopup $topup, ?float $amount = null): void
    {
        if (!$topup->isPending()) {
            throw new Exception('Top up sudah diproses');
        }

        $topup->update([
            'amount_approved' => $amount ?? $topup->amount_requested,
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Proses transfer top-up (dana sudah ditransfer)
     */
    public function processTopupTransfer(PettyCashTopup $topup, ?string $proof = null): void
    {
        if ($topup->status !== 'approved') {
            throw new Exception('Top up belum diapprove');
        }

        DB::transaction(function () use ($topup, $proof) {
            $fund = $topup->fund;
            $balanceAfter = $fund->current_balance + $topup->amount_approved;

            $topup->update([
                'balance_after' => $balanceAfter,
                'status' => 'transferred',
                'transferred_at' => now(),
                'transfer_proof' => $proof,
            ]);

            $fund->update(['current_balance' => $balanceAfter]);

            $this->createTopupJournal($topup);
        });
    }

    /**
     * Buat jurnal top-up: Debit Kas Kecil, Kredit Kas Besar
     */
    protected function createTopupJournal(PettyCashTopup $topup): void
    {
        $pcAcc = Account::where('code', self::COA_PETTY_CASH)->first();
        $kasAcc = Account::where('code', self::COA_KAS_BESAR)->first();

        if (!$pcAcc || !$kasAcc) {
            \Log::warning('COA top up tidak ditemukan');
            return;
        }

        $journalNumber = 'JU-PCT-' . now()->format('Ymd') . '-' . str_pad(
            Journal::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
        );

        $journal = Journal::create([
            'journal_number' => $journalNumber,
            'transaction_date' => now()->toDateString(),
            'description' => "[Top Up Kas Kecil] {$topup->topup_number}",
            'reference_no' => $topup->topup_number,
            'status' => 'posted',
            'created_by' => Auth::id(),
            'posted_at' => now(),
        ]);

        // Debit: Kas Kecil
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $pcAcc->id,
            'debit' => $topup->amount_approved,
            'credit' => 0,
        ]);

        // Kredit: Kas Besar
        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $kasAcc->id,
            'debit' => 0,
            'credit' => $topup->amount_approved,
        ]);

        $topup->update(['journal_id' => $journal->id]);
    }

    /**
     * Reject top-up
     */
    public function rejectTopup(PettyCashTopup $topup, string $reason): void
    {
        $topup->update([
            'status' => 'rejected',
            'reject_reason' => $reason,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Update setting kas kecil dengan log
     */
    public function updateFundSettings(PettyCashFund $fund, array $data, ?string $reason = null): void
    {
        $changes = [];
        
        foreach (['plafon', 'min_balance_alert', 'max_transaction', 'holder_user_id', 'approver_user_id', 'name'] as $field) {
            if (isset($data[$field]) && $fund->$field != $data[$field]) {
                $changes[] = [
                    'field' => $field,
                    'old' => $fund->$field,
                    'new' => $data[$field],
                ];
            }
        }

        if (empty($changes)) return;

        DB::transaction(function () use ($fund, $data, $changes, $reason) {
            // Log semua perubahan
            foreach ($changes as $change) {
                PettyCashSettingLog::create([
                    'petty_cash_fund_id' => $fund->id,
                    'changed_by' => Auth::id(),
                    'field_changed' => $change['field'],
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                    'reason' => $reason,
                ]);
            }

            // Update fund
            $fund->update($data);
        });
    }

    /**
     * Summary untuk dashboard
     */
    public function getSummary(PettyCashFund $fund, string $period = 'month'): array
    {
        $query = $fund->transactions()->approved();

        if ($period === 'today') {
            $query->whereDate('transaction_date', today());
        } elseif ($period === 'week') {
            $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
        } else {
            $query->thisMonth();
        }

        $txns = $query->get();

        return [
            'total_transactions' => $txns->count(),
            'total_amount' => $txns->sum('amount'),
            'by_category' => $txns->groupBy('category')->map(fn($items) => [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ]),
            'current_balance' => $fund->current_balance,
            'usage_percentage' => $fund->usage_percentage,
            'needs_topup' => $fund->needsTopup(),
        ];
    }
}
