<?php

namespace Tests\Feature;

use App\Livewire\Admin\InvoiceManager;
use App\Livewire\Admin\ShipmentManagement;
use App\Livewire\Admin\QuotationManager;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DateRangeFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@m2b.co.id',
            'role' => 'admin',
        ]);

        $this->customer = Customer::create([
            'company_name' => 'PT Test Customer',
            'customer_code' => 'CUST-00999',
            'phone' => '08123456789',
        ]);
    }

    public function test_invoice_date_filter_presets_work_correctly(): void
    {
        $this->actingAs($this->admin);

        // Invoice 1: Hari ini
        $invToday = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-TODAY-01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
            'type' => 'Commercial',
            'grand_total' => 1000000,
        ]);

        // Invoice 2: Bulan lalu
        $invLastMonth = Invoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-LASTM-01',
            'invoice_date' => now()->subMonth()->startOfMonth()->addDays(5)->toDateString(),
            'due_date' => now()->subMonth()->startOfMonth()->addDays(12)->toDateString(),
            'status' => 'paid',
            'type' => 'Commercial',
            'grand_total' => 2000000,
        ]);

        // Test filter 'today'
        Livewire::test(InvoiceManager::class)
            ->set('datePreset', 'today')
            ->assertSee('INV-TODAY-01')
            ->assertDontSee('INV-LASTM-01');

        // Test filter 'last_month'
        Livewire::test(InvoiceManager::class)
            ->set('datePreset', 'last_month')
            ->assertSee('INV-LASTM-01')
            ->assertDontSee('INV-TODAY-01');
    }

    public function test_shipment_date_filter_presets_work_correctly(): void
    {
        $this->actingAs($this->admin);

        // Shipment 1: Hari ini
        $sToday = Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'AWB-TODAY-01',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'service_type' => 'domestic',
            'shipment_type' => 'land',
            'status' => 'pending',
            'created_at' => now(),
        ]);

        // Shipment 2: Tahun lalu
        $sLastYear = Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'AWB-LASTY-01',
            'origin' => 'Medan',
            'destination' => 'Jakarta',
            'service_type' => 'domestic',
            'shipment_type' => 'sea',
            'status' => 'completed',
        ]);
        $sLastYear->created_at = now()->subYear()->startOfYear()->addDays(10);
        $sLastYear->save();

        // Test filter 'today'
        Livewire::test(ShipmentManagement::class)
            ->set('datePreset', 'today')
            ->assertSee('TODAY-01')
            ->assertDontSee('LASTY-01');

        // Test filter 'last_year'
        Livewire::test(ShipmentManagement::class)
            ->set('datePreset', 'last_year')
            ->assertSee('LASTY-01')
            ->assertDontSee('TODAY-01');
    }

    public function test_quotation_date_filter_presets_work_correctly(): void
    {
        $this->actingAs($this->admin);

        // Quotation 1: Hari ini
        $qToday = Quotation::create([
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-TODAY-01',
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'service_type' => 'import',
            'quotation_type' => 'shipment',
            'status' => 'sent',
            'origin' => 'China',
            'destination' => 'Jakarta',
            'grand_total' => 5000000,
        ]);

        // Quotation 2: Bulan lalu
        $qLastMonth = Quotation::create([
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-LASTM-01',
            'quotation_date' => now()->subMonth()->startOfMonth()->addDays(3)->toDateString(),
            'valid_until' => now()->subMonth()->startOfMonth()->addDays(17)->toDateString(),
            'service_type' => 'export',
            'quotation_type' => 'shipment',
            'status' => 'accepted',
            'origin' => 'Jakarta',
            'destination' => 'Singapore',
            'grand_total' => 7000000,
        ]);

        // Test filter 'today'
        Livewire::test(QuotationManager::class)
            ->set('datePreset', 'today')
            ->assertSee('QT-TODAY-01')
            ->assertDontSee('QT-LASTM-01');

        // Test filter 'last_month'
        Livewire::test(QuotationManager::class)
            ->set('datePreset', 'last_month')
            ->assertSee('QT-LASTM-01')
            ->assertDontSee('QT-TODAY-01');
    }
}
