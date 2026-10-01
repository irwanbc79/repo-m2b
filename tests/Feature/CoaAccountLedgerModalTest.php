<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalItem;
use Livewire\Livewire;
use App\Livewire\Admin\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoaAccountLedgerModalTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'email' => 'admin.coa@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_can_open_and_close_ledger_modal_for_account()
    {
        $account = Account::create([
            'code' => 'TEST-1103',
            'name' => 'Piutang Usaha Testing',
            'type' => 'piutang',
            'opening_balance' => 1000000,
            'current_balance' => 1000000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ChartOfAccounts::class)
            ->assertSee('Piutang Usaha Testing')
            ->assertSet('isLedgerModalOpen', false)
            ->call('openLedgerModal', $account->id)
            ->assertSet('isLedgerModalOpen', true)
            ->assertSet('ledgerAccountId', $account->id)
            ->assertSee('Piutang Usaha Testing')
            ->call('closeLedgerModal')
            ->assertSet('isLedgerModalOpen', false)
            ->assertSet('ledgerAccountId', null);
    }

    public function test_ledger_modal_calculates_running_balance_and_detects_negative_balance()
    {
        // Akun piutang: saldo normal DEBIT
        $account = Account::create([
            'code' => 'TEST-1104',
            'name' => 'Piutang Dagang Testing',
            'type' => 'piutang',
            'opening_balance' => 500000,
            'current_balance' => 500000,
            'is_active' => true,
        ]);

        // Jurnal 1: Kredit 800.000 (Menyebabkan saldo jadi 500.000 - 800.000 = -300.000 -> MINUS)
        $journal1 = Journal::create([
            'journal_number' => 'JRN-001',
            'reference_no' => 'INV-001',
            'transaction_date' => now()->subDays(2)->format('Y-m-d'),
            'description' => 'Pembayaran piutang berlebih',
            'created_by' => $this->admin->id,
        ]);

        JournalItem::create([
            'journal_id' => $journal1->id,
            'account_id' => $account->id,
            'debit' => 0,
            'credit' => 800000,
            'note' => 'Catatan overpayment',
        ]);

        // Jurnal 2: Debit 1.000.000 (Mengembalikan saldo jadi -300.000 + 1.000.000 = +700.000)
        $journal2 = Journal::create([
            'journal_number' => 'JRN-002',
            'reference_no' => 'INV-002',
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Tagihan baru',
            'created_by' => $this->admin->id,
        ]);

        JournalItem::create([
            'journal_id' => $journal2->id,
            'account_id' => $account->id,
            'debit' => 1000000,
            'credit' => 0,
            'note' => 'Faktur invoice',
        ]);

        $test = Livewire::actingAs($this->admin)
            ->test(ChartOfAccounts::class)
            ->call('openLedgerModal', $account->id);

        $ledgerData = $test->get('ledgerData') ?? $test->instance()->getLedgerData();

        $this->assertNotNull($ledgerData);
        $this->assertEquals(500000, $ledgerData['openingBalance']);
        $this->assertEquals(1000000, $ledgerData['totalDebit']);
        $this->assertEquals(800000, $ledgerData['totalCredit']);
        $this->assertEquals(700000, $ledgerData['closingBalance']);
        $this->assertTrue($ledgerData['hasNegativeBalance']); // Karena pernah minus di baris 1

        // Cek baris 1 minus
        $this->assertEquals(-300000, $ledgerData['rows'][0]['running_balance']);
        $this->assertTrue($ledgerData['rows'][0]['is_negative']);

        // Cek baris 2 positif
        $this->assertEquals(700000, $ledgerData['rows'][1]['running_balance']);
        $this->assertFalse($ledgerData['rows'][1]['is_negative']);
    }

    public function test_ledger_modal_search_filter()
    {
        $account = Account::create([
            'code' => 'TEST-1101',
            'name' => 'Kas Operasional Testing',
            'type' => 'kas_bank',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $journal = Journal::create([
            'journal_number' => 'JRN-ABC',
            'reference_no' => 'REF-999',
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Biaya bensin operasional',
            'created_by' => $this->admin->id,
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $account->id,
            'debit' => 150000,
            'credit' => 0,
            'note' => 'Nota pertalite',
        ]);

        $test = Livewire::actingAs($this->admin)
            ->test(ChartOfAccounts::class)
            ->call('openLedgerModal', $account->id)
            ->set('ledgerSearch', 'pertalite');

        $ledgerData = $test->instance()->getLedgerData();
        $this->assertCount(1, $ledgerData['rows']);

        $test->set('ledgerSearch', 'tidak_ada_transaksi_ini');
        $ledgerDataEmpty = $test->instance()->getLedgerData();
        $this->assertCount(0, $ledgerDataEmpty['rows']);
    }
}
