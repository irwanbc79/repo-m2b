<?php

namespace Tests\Feature;

use App\Livewire\Admin\BankReconciliation;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Journal;
use App\Models\User;
use App\Services\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankReconciliationJournalTest extends TestCase
{
    use RefreshDatabase;

    private function userAdmin(): User
    {
        return User::factory()->create(['name' => 'Kinan', 'role' => 'admin', 'roles' => ['admin']]);
    }

    private function setupAccounts(): void
    {
        Account::firstOrCreate(['code' => '1101'], ['name' => 'Bank Mandiri', 'type' => 'kas_bank', 'opening_balance' => 0, 'current_balance' => 0]);
        Account::firstOrCreate(['code' => '5101'], ['name' => 'Beban Gaji & Upah', 'type' => 'beban_operasional', 'opening_balance' => 0, 'current_balance' => 0]);
        Account::firstOrCreate(['code' => '2103'], ['name' => 'Hutang Titipan & Jaminan', 'type' => 'hutang_lancar', 'opening_balance' => 0, 'current_balance' => 0]);
        Account::firstOrCreate(['code' => '2104'], ['name' => 'Kelebihan Pembayaran / Refund', 'type' => 'hutang_lancar', 'opening_balance' => 0, 'current_balance' => 0]);
    }

    public function test_smart_suggestion_akun_lawan_untuk_gaji_dan_jaminan(): void
    {
        $this->setupAccounts();
        $service = new BankReconciliationService();

        // 1. Transaksi Gaji CS Lina (750.000)
        $trxGaji = BankTransaction::create([
            'bank_name' => 'mandiri',
            'account_number' => '1060055988896',
            'transaction_date' => '2026-08-03',
            'description' => 'GAJI CS M2B (LINA) MCM InhouseTrf',
            'debit_amount' => 750000,
            'credit_amount' => 0,
            'balance' => 13838932.64,
            'category' => 'salary',
        ]);

        $suggestedGaji = $service->suggestCounterAccountId($trxGaji);
        $accGaji = Account::where('code', '5101')->first();
        $this->assertSame($accGaji->id, $suggestedGaji);

        // 2. Transaksi Pengembalian Jaminan (500.000)
        $trxJaminan = BankTransaction::create([
            'bank_name' => 'mandiri',
            'account_number' => '1060055988896',
            'transaction_date' => '2026-08-05',
            'description' => 'PENGEMBALIAN JAMINAN DEDI YUDI RAHADI',
            'debit_amount' => 500000,
            'credit_amount' => 0,
            'balance' => 7622432.64,
            'category' => 'deposit',
        ]);

        $suggestedJaminan = $service->suggestCounterAccountId($trxJaminan);
        $accJaminan = Account::where('code', '2103')->first();
        $this->assertSame($accJaminan->id, $suggestedJaminan);

        // 3. Transaksi Pengembalian Kelebihan Pembayaran (5.716.500)
        $trxRefund = BankTransaction::create([
            'bank_name' => 'mandiri',
            'account_number' => '1060055988896',
            'transaction_date' => '2026-08-03',
            'description' => 'pengembalian kelebihan bayar',
            'debit_amount' => 5716500,
            'credit_amount' => 0,
            'balance' => 8122432.64,
            'category' => 'operational',
        ]);

        $suggestedRefund = $service->suggestCounterAccountId($trxRefund);
        $accRefund = Account::where('code', '2104')->first();
        $this->assertSame($accRefund->id, $suggestedRefund);
    }

    public function test_buat_jurnal_dan_rekonsiliasi_otomatis(): void
    {
        $this->setupAccounts();
        $service = new BankReconciliationService();
        $bankAcc = Account::where('code', '1101')->first();
        $gajiAcc = Account::where('code', '5101')->first();

        $trx = BankTransaction::create([
            'bank_name' => 'mandiri',
            'account_number' => '1060055988896',
            'transaction_date' => '2026-08-03',
            'description' => 'GAJI CS M2B (LINA)',
            'debit_amount' => 750000,
            'credit_amount' => 0,
            'balance' => 13838932.64,
            'reference_number' => 'REF12345',
        ]);

        $admin = $this->userAdmin();
        $journal = $service->createJournalAndReconcile(
            $trx,
            $bankAcc->id,
            $gajiAcc->id,
            'Pembayaran Gaji CS Lina Agustus 2026',
            'REF12345',
            $admin->id
        );

        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertStringStartsWith('JR-', $journal->journal_number);
        $this->assertCount(2, $journal->items);

        // Debit Beban Gaji 750k, Kredit Bank Mandiri 750k
        $itemDebit = $journal->items->where('debit', '>', 0)->first();
        $itemCredit = $journal->items->where('credit', '>', 0)->first();
        $this->assertSame($gajiAcc->id, $itemDebit->account_id);
        $this->assertEquals(750000, $itemDebit->debit);
        $this->assertSame($bankAcc->id, $itemCredit->account_id);
        $this->assertEquals(750000, $itemCredit->credit);

        // Transaksi bank sudah match
        $trx->refresh();
        $this->assertTrue($trx->is_reconciled);
        $this->assertSame($journal->id, $trx->journal_id);
    }

    public function test_livewire_1_click_create_journal_flow(): void
    {
        $this->setupAccounts();
        $admin = $this->userAdmin();
        $bankAcc = Account::where('code', '1101')->first();
        $gajiAcc = Account::where('code', '5101')->first();

        $trx = BankTransaction::create([
            'bank_name' => 'mandiri',
            'account_number' => '1060055988896',
            'transaction_date' => '2026-08-03',
            'description' => 'GAJI CS M2B (LINA)',
            'debit_amount' => 750000,
            'credit_amount' => 0,
            'balance' => 13838932.64,
        ]);

        Livewire::actingAs($admin)
            ->test(BankReconciliation::class)
            ->call('openCreateJournalModal', $trx->id)
            ->assertSet('showCreateJournalModal', true)
            ->assertSet('journalBankAccountId', $bankAcc->id)
            ->assertSet('journalCounterAccountId', $gajiAcc->id)
            ->assertSet('journalAmount', 750000)
            ->call('saveJournalFromTransaction')
            ->assertHasNoErrors()
            ->assertSet('showCreateJournalModal', false);

        $trx->refresh();
        $this->assertTrue($trx->is_reconciled);
        $this->assertNotNull($trx->journal_id);
    }
}
