<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\CashierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: kolom cash_transactions.cost_category adalah
 * enum('shipment','overhead','other'). Kategori akuntansi seperti
 * 'payment_from_customer' pernah ditulis mentah ke kolom ini sehingga
 * insert gagal di MySQL (Data truncated) dan jurnal ikut rollback.
 */
class CashierCostCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach ([
            ['code' => '1103', 'name' => 'Bank Mandiri', 'type' => 'kas_bank'],
            ['code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'piutang'],
            ['code' => '4101', 'name' => 'Pendapatan Jasa', 'type' => 'pendapatan'],
            ['code' => '5101', 'name' => 'Biaya Operasional', 'type' => 'beban_operasional'],
            ['code' => '5199', 'name' => 'Biaya Lain-lain', 'type' => 'beban_lain'],
        ] as $account) {
            Account::create($account + ['opening_balance' => 0, 'current_balance' => 0]);
        }
    }

    public function test_payment_from_customer_tersimpan_dengan_cost_category_null(): void
    {
        $tx = app(CashierService::class)->processPayment([
            'type' => 'in',
            'category' => 'payment_from_customer',
            'counterpart_type' => 'customer',
            'amount' => 1000000,
            'transaction_date' => '2026-07-01',
            'description' => 'Pelunasan INV/TEST/0001',
        ]);

        $this->assertNull($tx->cost_category);
        $this->assertDatabaseHas('cash_transactions', ['id' => $tx->id, 'type' => 'in']);
        $this->assertNotNull($tx->journal_id);
    }

    public function test_payment_to_vendor_dinormalisasi_ke_nilai_enum_valid(): void
    {
        $tx = app(CashierService::class)->processPayment([
            'type' => 'out',
            'category' => 'payment_to_vendor',
            'counterpart_type' => 'vendor',
            'amount' => 500000,
            'transaction_date' => '2026-07-01',
            'description' => 'Bayar vendor trucking',
        ]);

        $this->assertContains($tx->cost_category, ['shipment', 'overhead', 'other']);
    }

    public function test_cost_category_valid_tidak_diubah(): void
    {
        $tx = app(CashierService::class)->processPayment([
            'type' => 'out',
            'category' => 'operational_expense',
            'cost_category' => 'overhead',
            'amount' => 250000,
            'transaction_date' => '2026-07-01',
            'description' => 'Biaya listrik kantor',
        ]);

        $this->assertSame('overhead', $tx->cost_category);
    }
}
