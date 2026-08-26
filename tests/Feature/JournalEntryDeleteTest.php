<?php

namespace Tests\Feature;

use App\Livewire\Admin\Accounting\JournalEntry;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JournalEntryDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $staffAccounting;
    private User $otherStaff;
    private Account $cashAccount;
    private Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffAccounting = User::factory()->create([
            'role' => 'staff_accounting',
            'roles' => ['staff_accounting'],
        ]);

        $this->otherStaff = User::factory()->create([
            'role' => 'staff',
            'roles' => ['staff'],
        ]);

        $this->cashAccount = Account::create([
            'code' => '1101',
            'name' => 'Kas Operasional',
            'type' => 'kas_bank',
            'current_balance' => 1000000,
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'code' => '5101',
            'name' => 'Beban Operasional',
            'type' => 'beban_operasional',
            'current_balance' => 0,
            'is_active' => true,
        ]);
    }

    public function test_staff_accounting_can_create_and_delete_journal_entry(): void
    {
        $this->actingAs($this->staffAccounting);

        // 1. Create a journal entry
        Livewire::test(JournalEntry::class)
            ->set('transaction_date', '2026-08-26')
            ->set('description', 'Beli Perlengkapan Kantor')
            ->set('reference_no', 'MEMO-001')
            ->set('items', [
                ['account_id' => $this->expenseAccount->id, 'debit' => 250000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 250000],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Jurnal berhasil disimpan');

        $journal = Journal::where('reference_no', 'MEMO-001')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('posted', $journal->status);

        // Check balances after save
        $this->assertEquals(750000, $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(250000, $this->expenseAccount->fresh()->current_balance);

        // 2. Staff accounting can delete the posted journal entry
        Livewire::test(JournalEntry::class)
            ->call('delete', $journal->id)
            ->assertSee('Jurnal Entry dan keterkaitan data berhasil dihapus');

        // Verify journal deleted from DB
        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
        $this->assertDatabaseMissing('journal_items', ['journal_id' => $journal->id]);

        // Verify account balances rolled back accurately
        $this->assertEquals(1000000, $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(0, $this->expenseAccount->fresh()->current_balance);
    }
}
