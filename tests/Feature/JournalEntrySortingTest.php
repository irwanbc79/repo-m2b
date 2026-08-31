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

class JournalEntrySortingTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(): User
    {
        return User::factory()->create([
            'name' => 'Kinan Accounting',
            'email' => 'kinan@m2b.co.id',
            'role' => 'admin',
        ]);
    }

    public function test_journals_are_sorted_by_transaction_date_chronologically()
    {
        $user = $this->createStaff();

        $kas = Account::create([
            'code' => '1101',
            'name' => 'Kas Operasional',
            'type' => 'kas_bank',
            'current_balance' => 0
        ]);

        $modal = Account::create([
            'code' => '5101',
            'name' => 'Beban Operasional',
            'type' => 'beban_operasional',
            'current_balance' => 0
        ]);

        // Transaksi 1: Tanggal 1 Agustus tapi diinput paling akhir (created_at paling baru)
        $j1 = Journal::create([
            'journal_number' => 'JR-2608-0001',
            'transaction_date' => '2026-08-01',
            'description' => 'Transaksi Awal Bulan',
            'created_by' => $user->id,
            'status' => 'posted',
            'created_at' => now(),
        ]);

        // Transaksi 2: Tanggal 25 Agustus tapi diinput duluan (created_at lebih lama)
        $j2 = Journal::create([
            'journal_number' => 'JR-2608-0002',
            'transaction_date' => '2026-08-25',
            'description' => 'Transaksi Akhir Bulan',
            'created_by' => $user->id,
            'status' => 'posted',
            'created_at' => now()->subHours(2),
        ]);

        // Default descending: j2 (25 Agt) harus di atas j1 (1 Agt)
        Livewire::actingAs($user)
            ->test(JournalEntry::class)
            ->assertSeeInOrder(['JR-2608-0002', 'JR-2608-0001'])
            // Toggle ascending: j1 (1 Agt) harus di atas j2 (25 Agt)
            ->call('sortBy', 'transaction_date')
            ->assertSeeInOrder(['JR-2608-0001', 'JR-2608-0002']);
    }
}
