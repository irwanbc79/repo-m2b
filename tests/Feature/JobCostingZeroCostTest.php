<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\JobCostingManager;
use App\Models\Customer;
use App\Models\JobCost;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JobCostingZeroCostTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'roles' => ['super_admin'],
        ]);

        $this->customer = Customer::create([
            'company_name' => 'PT Online Service',
            'customer_code' => 'CLI-001',
            'phone' => '081234567890',
            'address' => 'Jakarta',
        ]);
    }

    public function test_can_mark_completed_shipment_as_zero_cost()
    {
        $shipment = Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'IMP-260101-001',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'CN',
            'destination' => 'ID',
            'status' => 'completed',
        ]);

        // Verifikasi sebelum di-mark: muncul di dashboard action items
        $dashboardComponent = Livewire::actingAs($this->admin)->test(Dashboard::class);
        $actionItemsBefore = $dashboardComponent->instance()->getActionItems();
        $tanpaJcBefore = collect($actionItemsBefore)->firstWhere('title', 'Selesai tanpa job costing');
        $this->assertNotNull($tanpaJcBefore);
        $this->assertEquals(1, $tanpaJcBefore['count']);

        // Mark as zero cost via JobCostingManager
        Livewire::actingAs($this->admin)
            ->test(JobCostingManager::class)
            ->call('markAsZeroCost', $shipment->id)
            ->assertHasNoErrors();

        // Verifikasi record JobCost terbentuk dengan amount = 0
        $this->assertDatabaseHas('job_costs', [
            'shipment_id' => $shipment->id,
            'amount' => 0,
            'status' => 'paid',
        ]);

        // Verifikasi setelah di-mark: hilang dari dashboard action items
        $dashboardComponentAfter = Livewire::actingAs($this->admin)->test(Dashboard::class);
        $actionItemsAfter = $dashboardComponentAfter->instance()->getActionItems();
        $tanpaJcAfter = collect($actionItemsAfter)->firstWhere('title', 'Selesai tanpa job costing');
        $this->assertNull($tanpaJcAfter);
    }

    public function test_cannot_mark_as_zero_cost_if_real_costs_already_exist()
    {
        $shipment = Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'IMP-260101-002',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'CN',
            'destination' => 'ID',
            'status' => 'completed',
        ]);

        JobCost::create([
            'shipment_id' => $shipment->id,
            'description' => 'Biaya Trucking',
            'amount' => 500000,
            'status' => 'paid',
            'created_by' => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(JobCostingManager::class)
            ->call('markAsZeroCost', $shipment->id);

        // Record amount = 0 tidak boleh dibuat
        $this->assertDatabaseMissing('job_costs', [
            'shipment_id' => $shipment->id,
            'amount' => 0,
        ]);
    }

    public function test_zero_cost_is_not_flagged_by_finance_check_integrity()
    {
        $shipment = Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'IMP-260101-003',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'CN',
            'destination' => 'ID',
            'status' => 'completed',
        ]);

        JobCost::create([
            'shipment_id' => $shipment->id,
            'description' => 'Jasa Murni / Zero Cost (Tanpa Biaya Vendor)',
            'amount'      => 0,
            'status'      => 'paid',
            'date_paid'   => now(),
            'created_by'  => $this->admin->id,
        ]);

        // Jalankan perintah audit integritas keuangan
        $this->artisan('finance:check-integrity')
            ->expectsOutputToContain('OK — semua pembayaran invoice & job cost')
            ->assertSuccessful();
    }

    public function test_zero_cost_cannot_be_booked_via_bukukan()
    {
        $shipment = Shipment::create([
            'customer_id' => $this->customer->id,
            'awb_number' => 'IMP-260101-004',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'CN',
            'destination' => 'ID',
            'status' => 'completed',
        ]);

        $zeroCost = JobCost::create([
            'shipment_id' => $shipment->id,
            'description' => 'Jasa Murni / Zero Cost (Tanpa Biaya Vendor)',
            'amount'      => 0,
            'status'      => 'paid',
            'date_paid'   => now(),
            'created_by'  => $this->admin->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(JobCostingManager::class)
            ->call('bukukan', $zeroCost->id)
            ->assertSee('Biaya Jasa Murni (Zero Cost) tidak memerlukan pembukuan kas/jurnal.');

        // Pastikan tidak ada CashTransaction yang dibuat
        $this->assertDatabaseMissing('cash_transactions', [
            'job_cost_id' => $zeroCost->id,
        ]);
    }
}
