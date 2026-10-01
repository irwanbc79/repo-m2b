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

    public function test_can_open_edit_journal_modal_and_save_changes_directly_from_coa()
    {
        $accountPiutang = Account::create([
            'code' => 'TEST-1105',
            'name' => 'Piutang Koreksi Langsung',
            'type' => 'piutang',
            'opening_balance' => 500000,
            'current_balance' => 500000,
            'is_active' => true,
        ]);

        $accountKas = Account::create([
            'code' => 'TEST-1106',
            'name' => 'Kas Koreksi Langsung',
            'type' => 'kas_bank',
            'opening_balance' => 1000000,
            'current_balance' => 1000000,
            'is_active' => true,
        ]);

        // Jurnal yang salah: Kredit Piutang 800.000 (menyebabkan Piutang jadi minus Rp -300.000)
        $journal = Journal::create([
            'journal_number' => 'JRN-WRONG-01',
            'reference_no' => 'INV-SALAH',
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Salah catat nominal pembayaran',
            'created_by' => $this->admin->id,
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $accountKas->id,
            'debit' => 800000,
            'credit' => 0,
            'note' => 'Penerimaan',
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $accountPiutang->id,
            'debit' => 0,
            'credit' => 800000,
            'note' => 'Piutang kelebihan catat',
        ]);

        $accountPiutang->recalculateBalance();
        $this->assertEquals(-300000, $accountPiutang->fresh()->current_balance);

        // Buka COA -> Buka Modal Jurnal Akun -> Buka Modal Edit Jurnal Langsung
        $component = Livewire::actingAs($this->admin)
            ->test(ChartOfAccounts::class)
            ->call('openLedgerModal', $accountPiutang->id)
            ->assertSet('isLedgerModalOpen', true)
            ->call('openEditJournalModal', $journal->id)
            ->assertSet('isJournalEditModalOpen', true)
            ->assertSet('editJournalNumber', 'JRN-WRONG-01')
            ->assertSet('editDescription', 'Salah catat nominal pembayaran');

        // Kinan mengoreksi nominal kredit dari 800.000 menjadi 300.000 (dan debit kas jadi 300.000)
        $component->set('editDescription', 'Koreksi pembayaran piutang yang benar')
            ->set('editItems.0.debit', 300000)
            ->set('editItems.1.credit', 300000)
            ->call('saveEditedJournal')
            ->assertSet('isJournalEditModalOpen', false)
            ->assertHasNoErrors();

        // Verifikasi database jurnal terupdate
        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'description' => 'Koreksi pembayaran piutang yang benar',
        ]);

        // Verifikasi saldo piutang kini sudah tidak minus: 500.000 - 300.000 = +200.000
        $this->assertEquals(200000, (float) $accountPiutang->fresh()->current_balance);
        $this->assertEquals(1300000, (float) $accountKas->fresh()->current_balance);

        // Verifikasi data ledger di modal COA otomatis terefleksi dan saldo minus sudah hilang!
        $updatedLedgerData = $component->instance()->getLedgerData();
        $this->assertEquals(200000, $updatedLedgerData['closingBalance']);
        $this->assertFalse($updatedLedgerData['hasNegativeBalance']);
    }
}

