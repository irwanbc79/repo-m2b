<?php

namespace Tests\Feature;

use App\Livewire\Admin\Accounting\ChartOfAccounts;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChartOfAccountsSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Account $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
        ]);

        $this->bankAccount = Account::create([
            'code' => '1103',
            'name' => 'Bank Mandiri IDR',
            'type' => 'kas_bank',
            'opening_balance' => 1000000,
            'current_balance' => 1000000,
        ]);
    }

    public function test_calculated_balance_matches_general_ledger_journal_items(): void
    {
        $journal = Journal::create([
            'journal_number' => 'JV-20260824-0001',
            'transaction_date' => '2026-08-24',
            'description' => 'Test Transaction',
            'status' => 'posted',
            'created_by' => $this->admin->id,
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $this->bankAccount->id,
            'debit' => 5000000,
            'credit' => 2000000,
        ]);

        // Opening (1.000.000) + Debit (5.000.000) - Credit (2.000.000) = 4.000.000
        $this->assertEquals(4000000, $this->bankAccount->calculated_balance);

        $this->bankAccount->recalculateBalance();
        $this->assertEquals(4000000, $this->bankAccount->fresh()->current_balance);
    }

    public function test_artisan_sync_balances_command_updates_all_accounts(): void
    {
        $journal = Journal::create([
            'journal_number' => 'JV-20260824-0002',
            'transaction_date' => '2026-08-24',
            'description' => 'Test Transaction 2',
            'status' => 'posted',
            'created_by' => $this->admin->id,
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'account_id' => $this->bankAccount->id,
            'debit' => 1500000,
            'credit' => 0,
        ]);

        $this->artisan('accounting:sync-balances')
            ->assertSuccessful();

        $this->assertEquals(2500000, $this->bankAccount->fresh()->current_balance);
    }

    public function test_chart_of_accounts_ui_renders_dynamic_balance_and_allows_sync(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ChartOfAccounts::class)
            ->call('syncBalances')
            ->assertHasNoErrors()
            ->assertSee('Bank Mandiri IDR');
    }
}
