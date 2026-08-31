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

    public function test_shipment_stats_exclude_cancelled_shipments(): void
    {
        $this->actingAs($this->admin);

        // Shipment aktif 1
        Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'AWB-ACT-01',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'service_type' => 'domestic',
            'shipment_type' => 'land',
            'status' => 'in_progress',
            'weight' => 500,
            'volume' => 2.5,
            'pieces' => 10,
        ]);

        // Shipment aktif 2
        Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'AWB-ACT-02',
            'origin' => 'Jakarta',
            'destination' => 'Bali',
            'service_type' => 'domestic',
            'shipment_type' => 'air',
            'status' => 'completed',
            'weight' => 300,
            'volume' => 1.5,
            'pieces' => 5,
        ]);

        // Shipment Cancel (dibatalkan)
        Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'AWB-CAN-01',
            'origin' => 'Jakarta',
            'destination' => 'Medan',
            'service_type' => 'domestic',
            'shipment_type' => 'sea',
            'status' => 'cancel',
            'weight' => 10000,
            'volume' => 50,
            'pieces' => 100,
        ]);

        $component = new ShipmentManagement();
        $stats = $component->getStats();

        // Total shipment harus 2 (tidak termasuk yang cancel)
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(2, $stats['this_month']);

        // Total weight harus 800 (bukan 10800)
        $this->assertEquals(800, $stats['total_weight']);

        // Total volume harus 4.0 (bukan 54.0)
        $this->assertEquals(4.0, $stats['total_volume']);

        // Total pieces harus 15 (bukan 115)
        $this->assertEquals(15, $stats['total_pieces']);
    }
}
