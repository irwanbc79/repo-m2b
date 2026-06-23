<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SimpleInvoice;
use App\Models\BankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleInvoiceBankDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for authentication
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_can_create_simple_invoice_with_bank_details(): void
    {
        $payload = [
            'customer_name' => 'PT. TEST CUSTOMER',
            'customer_address' => 'Test Address 123',
            'invoice_date' => '2026-06-23',
            'notes' => 'Some notes here',
            'bank_name' => 'PT BANK MANDIRI (Persero) Tbk',
            'bank_account_number' => '106-00164.19-775',
            'bank_account_holder' => 'Eka Mayang Sari Harahap',
            'items' => [
                [
                    'description' => 'Item 1',
                    'quantity' => 1,
                    'unit_price' => 300000,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('finance.simple-invoice.store'), $payload);

        $response->assertRedirect(route('finance.simple-invoice.index'));
        
        $this->assertDatabaseHas('simple_invoices', [
            'customer_name' => 'PT. TEST CUSTOMER',
            'bank_name' => 'PT BANK MANDIRI (Persero) Tbk',
            'bank_account_number' => '106-00164.19-775',
            'bank_account_holder' => 'Eka Mayang Sari Harahap',
            'total' => 300000,
        ]);
    }

    public function test_can_save_custom_bank_account_as_template(): void
    {
        $payload = [
            'customer_name' => 'PT. NEW CUSTOMER',
            'customer_address' => 'Jakarta',
            'invoice_date' => '2026-06-23',
            'bank_name' => 'Bank Mandiri Syariah',
            'bank_account_number' => '711-222-333',
            'bank_account_holder' => 'Nurul Asyikin',
            'save_bank_account' => true,
            'items' => [
                [
                    'description' => 'Custom Services',
                    'quantity' => 1,
                    'unit_price' => 500000,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('finance.simple-invoice.store'), $payload);

        $response->assertRedirect(route('finance.simple-invoice.index'));
        
        // Assert invoice stored
        $this->assertDatabaseHas('simple_invoices', [
            'customer_name' => 'PT. NEW CUSTOMER',
            'bank_account_number' => '711-222-333',
        ]);

        // Assert bank account template saved
        $this->assertDatabaseHas('bank_accounts', [
            'bank_name' => 'Bank Mandiri Syariah',
            'account_number' => '711-222-333',
            'account_holder' => 'Nurul Asyikin',
            'is_system' => false,
        ]);
    }

    public function test_can_update_simple_invoice_bank_details(): void
    {
        // First create a simple invoice
        $invoice = SimpleInvoice::create([
            'invoice_number' => 'INV001-M2B/VI/2026',
            'invoice_date' => '2026-06-23',
            'customer_name' => 'PT. TEST CUSTOMER',
            'customer_address' => 'Test Address',
            'currency' => 'IDR',
            'subtotal' => 300000,
            'total' => 300000,
            'status' => 'unpaid',
            'created_by' => $this->user->id,
            'bank_name' => 'PT BANK MANDIRI (Persero) Tbk',
            'bank_account_number' => '106-00-5598889-6',
            'bank_account_holder' => 'PT. MORA MULTI BERKAH',
        ]);

        $invoice->items()->create([
            'description' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 300000,
            'amount' => 300000,
        ]);

        // Put request to update the details and save a new template
        $payload = [
            'customer_name' => 'PT. TEST CUSTOMER UPDATED',
            'customer_address' => 'New Address',
            'invoice_date' => '2026-06-24',
            'notes' => 'Updated notes',
            'bank_name' => 'Bank Mandiri New',
            'bank_account_number' => '106-00164.19-775',
            'bank_account_holder' => 'Eka Mayang Sari Harahap',
            'save_bank_account' => true,
            'items' => [
                [
                    'description' => 'Item 1 Updated',
                    'quantity' => 2,
                    'unit_price' => 150000,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->put(route('finance.simple-invoice.update', $invoice->id), $payload);

        $response->assertRedirect(route('finance.simple-invoice.index'));

        $this->assertDatabaseHas('simple_invoices', [
            'id' => $invoice->id,
            'customer_name' => 'PT. TEST CUSTOMER UPDATED',
            'bank_name' => 'Bank Mandiri New',
            'bank_account_number' => '106-00164.19-775',
            'bank_account_holder' => 'Eka Mayang Sari Harahap',
        ]);
    }

    public function test_views_contain_correct_bank_details(): void
    {
        $invoice = SimpleInvoice::create([
            'invoice_number' => 'INV002-M2B/VI/2026',
            'invoice_date' => '2026-06-23',
            'customer_name' => 'PT. TEST CUSTOMER',
            'customer_address' => 'Test Address',
            'currency' => 'IDR',
            'subtotal' => 300000,
            'total' => 300000,
            'status' => 'unpaid',
            'created_by' => $this->user->id,
            'bank_name' => 'Custom Bank Corp',
            'bank_account_number' => '999-888-777',
            'bank_account_holder' => 'Custom Account Holder',
        ]);

        $invoice->items()->create([
            'description' => 'Item 1',
            'quantity' => 1,
            'unit_price' => 300000,
            'amount' => 300000,
        ]);

        // 1. Detail View
        $response = $this->actingAs($this->user)
            ->get(route('finance.simple-invoice.detail', $invoice->id));
        $response->assertStatus(200);
        $response->assertSee('Custom Bank Corp');
        $response->assertSee('999-888-777');
        $response->assertSee('Custom Account Holder');

        // 2. Edit View
        $response = $this->actingAs($this->user)
            ->get(route('finance.simple-invoice.edit', $invoice->id));
        $response->assertStatus(200);
        $response->assertSee('Custom Bank Corp');
        $response->assertSee('999-888-777');
        $response->assertSee('Custom Account Holder');

        // 3. Print View
        $response = $this->actingAs($this->user)
            ->get(route('finance.simple-invoice.print', $invoice->id));
        $response->assertStatus(200);
        $response->assertSee('Custom Bank Corp');
        $response->assertSee('999-888-777');
        $response->assertSee('Custom Account Holder');
    }
}
