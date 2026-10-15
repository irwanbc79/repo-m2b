<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageVendors;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Admin\SurveyDashboard;
use App\Livewire\Customer\Create as CustomerCreateModal;
use App\Livewire\Customer\CreateShipment;
use App\Livewire\Customer\Dashboard as CustomerDashboard;
use App\Livewire\Customer\DocumentUpload;
use App\Livewire\Customer\InvoiceList;
use App\Livewire\Customer\Profile as CustomerProfile;
use App\Livewire\Customer\QuotationList;
use App\Livewire\Customer\ShipmentDetail as CustomerShipmentDetail;
use App\Livewire\Customer\ShipmentList;
use App\Models\Customer;
use App\Models\CustomerSurvey;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class Phase3SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'roles' => [$role],
            'is_active' => true,
        ], $extra));
    }

    private function createPrimaryAndSecondaryCustomers(): array
    {
        $primaryUser = $this->makeUser('customer');
        $customer = Customer::create([
            'user_id' => $primaryUser->id,
            'customer_code' => 'CST-P3-001',
            'company_name' => 'PT Multi PIC Nusantara',
        ]);

        // Secondary PIC with View-Only role (linked only via pivot customer_user)
        $viewerPic = $this->makeUser('customer_viewer');
        DB::table('customer_user')->insert([
            'customer_id' => $customer->id,
            'user_id' => $viewerPic->id,
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Secondary PIC with Full-Access role (linked only via pivot customer_user)
        $fullPic = $this->makeUser('customer');
        DB::table('customer_user')->insert([
            'customer_id' => $customer->id,
            'user_id' => $fullPic->id,
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$customer, $primaryUser, $viewerPic, $fullPic];
    }

    public function test_multi_pic_viewer_can_access_customer_portal_and_see_company_data(): void
    {
        [$customer, , $viewerPic, $fullPic] = $this->createPrimaryAndSecondaryCustomers();

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'AWB-MPIC-001',
            'origin' => 'Shanghai',
            'destination' => 'Jakarta',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-MPIC-001',
            'customer_id' => $customer->id,
            'shipment_id' => $shipment->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 1500000,
            'grand_total' => 1500000,
        ]);

        $this->actingAs($viewerPic)
            ->get(route('customer.dashboard'))
            ->assertOk();

        Livewire::actingAs($viewerPic)
            ->test(CustomerDashboard::class)
            ->assertStatus(200)
            ->assertSee('AWB-MPIC-001');

        Livewire::actingAs($viewerPic)
            ->test(ShipmentList::class)
            ->assertStatus(200)
            ->assertSee('AWB-MPIC-001');

        Livewire::actingAs($viewerPic)
            ->test(InvoiceList::class)
            ->assertStatus(200)
            ->assertSee('INV-MPIC-001');

        Livewire::actingAs($viewerPic)
            ->test(CustomerShipmentDetail::class, ['id' => $shipment->id])
            ->assertStatus(200);

        Livewire::actingAs($fullPic)
            ->test(CreateShipment::class)
            ->set('origin', 'Singapore')
            ->set('destination', 'Belawan')
            ->set('service_type', 'import')
            ->set('shipment_type', 'sea')
            ->set('container_mode', 'FCL')
            ->set('pieces', 10)
            ->set('package_type', 'Carton')
            ->call('save')
            ->assertRedirect(route('customer.shipments.index'));

        $this->assertDatabaseHas('shipments', [
            'customer_id' => $customer->id,
            'origin' => 'Singapore',
            'destination' => 'Belawan',
        ]);
    }

    public function test_customer_viewer_is_blocked_from_all_portal_mutations(): void
    {
        [$customer, , $viewerPic] = $this->createPrimaryAndSecondaryCustomers();

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'AWB-VIEWONLY-001',
            'origin' => 'Busan',
            'destination' => 'Surabaya',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-VIEWONLY-001',
            'customer_id' => $customer->id,
            'quotation_type' => 'shipment',
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'status' => 'sent',
            'approval_status' => 'pending',
            'subtotal' => 2000000,
            'grand_total' => 2000000,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-VIEWONLY-001',
            'customer_id' => $customer->id,
            'shipment_id' => $shipment->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'paid',
            'subtotal' => 2000000,
            'grand_total' => 2000000,
        ]);

        Livewire::actingAs($viewerPic)
            ->test(CreateShipment::class)
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(DocumentUpload::class, ['shipmentId' => $shipment->id])
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(CustomerShipmentDetail::class, ['id' => $shipment->id])
            ->set('newMessage', 'Halo admin')
            ->call('sendMessage')
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(QuotationList::class)
            ->call('approve', $quotation->id)
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(QuotationList::class)
            ->call('reject', $quotation->id)
            ->assertForbidden();

        $this->assertSame('pending', $quotation->fresh()->approval_status);

        Livewire::actingAs($viewerPic)
            ->test(InvoiceList::class)
            ->call('openUploadModal', $invoice->id)
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(InvoiceList::class)
            ->call('requestFakturPajak', $invoice->id)
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(CustomerProfile::class)
            ->set('company_name', 'PT Diubah Viewer')
            ->call('updateProfile')
            ->assertForbidden();

        Livewire::actingAs($viewerPic)
            ->test(CustomerProfile::class)
            ->call('openRequestUserModal')
            ->assertForbidden();

        $this->assertSame('PT Multi PIC Nusantara', $customer->fresh()->company_name);
    }

    public function test_unrouted_and_admin_livewire_components_enforce_rbac(): void
    {
        [$customer, $primaryUser] = $this->createPrimaryAndSecondaryCustomers();
        $opsUser = $this->makeUser('staff_operations');
        $salesUser = $this->makeUser('staff_sales');
        $adminUser = $this->makeUser('admin');

        Livewire::actingAs($primaryUser)
            ->test(CustomerCreateModal::class)
            ->assertForbidden();

        Livewire::actingAs($opsUser)
            ->test(CustomerCreateModal::class)
            ->assertForbidden();

        $vendor = Vendor::create([
            'code' => 'VEN-901',
            'name' => 'PT Vendor Aman',
            'category' => 'Trucking',
        ]);

        Livewire::actingAs($opsUser)
            ->test(ManageVendors::class)
            ->call('create')
            ->assertForbidden();

        Livewire::actingAs($opsUser)
            ->test(ManageVendors::class)
            ->call('delete', $vendor->id)
            ->assertForbidden();

        $product = Product::create([
            'code' => 'PRD-901',
            'name' => 'Custom Clearance Fee',
            'category' => 'import',
            'service_type' => 'service',
            'default_price' => 500000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($salesUser)
            ->test(ProductManager::class)
            ->call('delete', $product->id)
            ->assertForbidden();

        Livewire::actingAs($adminUser)
            ->test(ProductManager::class)
            ->call('toggleActive', $product->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $product->fresh()->is_active);

        $survey = CustomerSurvey::create([
            'customer_id' => $customer->id,
            'company_name' => $customer->company_name,
            'survey_year' => (int) date('Y'),
            'survey_quarter' => 'Q1',
            'response_date' => now(),
            'overall_satisfaction' => 5,
            'nps_score' => 9,
            'likelihood_reuse' => 5,
        ]);

        Livewire::actingAs($primaryUser)
            ->test(SurveyDashboard::class)
            ->assertForbidden();

        Livewire::actingAs($opsUser)
            ->test(SurveyDashboard::class)
            ->call('deleteResponse', $survey->id)
            ->assertForbidden();

        Livewire::actingAs($adminUser)
            ->test(SurveyDashboard::class)
            ->call('deleteResponse', $survey->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('customer_surveys', ['id' => $survey->id]);
    }
}
