<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\TaxNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxCenterAndKonsultanPajakTest extends TestCase
{
    use RefreshDatabase;

    protected function createKonsultanPajakUser(): User
    {
        return User::factory()->create([
            'role'      => 'konsultan_pajak',
            'roles'     => ['konsultan_pajak'],
            'is_active' => true,
        ]);
    }

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'role'      => 'admin',
            'roles'     => ['admin'],
            'is_active' => true,
        ]);
    }

    public function test_konsultan_pajak_can_access_tax_center_and_see_metrics()
    {
        $konsultan = $this->createKonsultanPajakUser();

        // Create sample invoice with PPN and PPh 23
        $customer = Customer::create([
            'customer_code' => 'CUST-TAX-001',
            'company_name'  => 'PT Sumber Rezeki',
            'phone'         => '0812345678',
            'address'       => 'Jl. Testing',
        ]);

        Invoice::create([
            'invoice_number'      => 'INV-TAX-001',
            'customer_id'         => $customer->id,
            'invoice_date'        => now()->format('Y-m-d'),
            'due_date'            => now()->addDays(14)->format('Y-m-d'),
            'service_total'       => 10000000,
            'reimbursement_total' => 5000000,
            'subtotal'            => 15000000,
            'tax_amount'          => 1100000,
            'pph_amount'          => 200000,
            'grand_total'         => 15900000,
            'status'              => 'paid',
        ]);

        $this->actingAs($konsultan);

        $response = $this->get(route('admin.tax-notes.index'));
        $response->assertStatus(200);
        $response->assertSee('Pusat Perpajakan &amp; Catatan Pajak', false);
        $response->assertSee('PPN Keluaran (11%)');
        $response->assertSee('PPh 23 Dipotong Customer (2%)');
        $response->assertSee('Ekualisasi Omzet');
    }

    public function test_admin_can_access_tax_center_and_sees_same_synced_view()
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin);

        $response = $this->get(route('admin.tax-notes.index'));
        $response->assertStatus(200);
        $response->assertSee('Pusat Perpajakan &amp; Catatan Pajak', false);
    }

    public function test_konsultan_pajak_can_access_accounting_modules_read_only()
    {
        $konsultan = $this->createKonsultanPajakUser();

        // Ensure basic account exists
        Account::firstOrCreate(
            ['code' => '1101'],
            [
                'name'            => 'Kas Besar',
                'type'            => 'kas_bank',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active'       => true,
            ]
        );

        $this->actingAs($konsultan);

        // General Ledger
        $this->get(route('accounting.ledger'))->assertStatus(200);

        // Trial Balance
        $this->get(route('accounting.trial_balance'))->assertStatus(200);

        // Profit Loss
        $this->get(route('accounting.profit_loss'))->assertStatus(200);

        // Balance Sheet
        $this->get(route('accounting.balance_sheet'))->assertStatus(200);

        // Chart of Accounts
        $this->get(route('accounting.coa'))->assertStatus(200);
    }

    public function test_konsultan_pajak_cannot_create_or_manage_journals()
    {
        $konsultan = $this->createKonsultanPajakUser();
        $this->actingAs($konsultan);

        // Livewire component JournalEntry should forbid create / save
        Livewire::test(\App\Livewire\Admin\Accounting\JournalEntry::class)
            ->call('create')
            ->assertStatus(403);
    }

    public function test_konsultan_pajak_can_login_successfully()
    {
        $user = User::factory()->create([
            'email'     => 'konsultan@test.com',
            'password'  => bcrypt('Pajak2026!'),
            'role'      => 'konsultan_pajak',
            'roles'     => ['konsultan_pajak'],
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email'    => 'konsultan@test.com',
            'password' => 'Pajak2026!',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
