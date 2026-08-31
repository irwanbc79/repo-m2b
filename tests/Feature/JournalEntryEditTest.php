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

class JournalEntryEditTest extends TestCase
{
    use RefreshDatabase;

    private User $staffAccounting;
    private Account $cashAccount;
    private Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffAccounting = User::factory()->create([
            'name' => 'Kinan Accounting',
            'email' => 'kinan@m2b.co.id',
            'role' => 'staff_accounting',
            'roles' => ['staff_accounting'],
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

    public function test_staff_accounting_can_edit_journal_entry_and_update_balance(): void
    {
        $this->actingAs($this->staffAccounting);

        // 1. Create a journal entry: Beban Rp 100.000 vs Kas Rp 100.000
        $journal = Journal::create([
            'journal_number' => 'JR-2608-0001',
            'transaction_date' => '2026-08-20',
            'description' => 'Beli Alat Tulis Typo',
            'reference_no' => 'REF-001',
            'created_by' => $this->staffAccounting->id,
            'status' => 'posted',
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $this->expenseAccount->id,
            'debit' => 100000,
            'credit' => 0,
        ]);
        $this->expenseAccount->increment('current_balance', 100000);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 0,
            'credit' => 100000,
        ]);
        $this->cashAccount->decrement('current_balance', 100000);

        $this->assertEquals(900000, $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(100000, $this->expenseAccount->fresh()->current_balance);

        // 2. Edit the journal: Koreksi nominal jadi Rp 150.000 dan perbaiki keterangan
        Livewire::test(JournalEntry::class)
            ->call('edit', $journal->id)
            ->assertSet('isEditing', true)
            ->assertSet('editingId', $journal->id)
            ->assertSet('description', 'Beli Alat Tulis Typo')
            ->set('description', 'Beli Alat Tulis Kantor (Benar)')
            ->set('items.0.debit', 150000)
            ->set('items.1.credit', 150000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        // 3. Verifikasi data terupdate dan nomor jurnal tidak berubah
        $journal->refresh();
        $this->assertEquals('JR-2608-0001', $journal->journal_number);
        $this->assertEquals('Beli Alat Tulis Kantor (Benar)', $journal->description);

        // 4. Verifikasi saldo akun di-adjust dengan benar (Kas berkurang Rp 150.000 dari saldo awal 1.000.000 -> 850.000)
        $this->assertEquals(850000, $this->cashAccount->fresh()->current_balance);
        $this->assertEquals(150000, $this->expenseAccount->fresh()->current_balance);
    }
}
