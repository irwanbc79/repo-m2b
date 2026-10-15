<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLogManager;
use App\Livewire\Admin\BankReconciliation;
use App\Livewire\Admin\JobCostingManager;
use App\Livewire\Admin\QuotationManager;
use App\Livewire\Admin\ShipmentDetail as AdminShipmentDetail;
use App\Livewire\Admin\ShipmentManagement;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\VendorManagement;
use App\Livewire\Customer\ShipmentDetail as CustomerShipmentDetail;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\JobCost;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class Phase2SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createRoleUser(string $role, ?string $email = null): User
    {
        return User::factory()->create([
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $email ?? ($role . '.' . uniqid() . '@m2b.co.id'),
            'role' => $role,
            'roles' => [$role],
            'is_active' => true,
        ]);
    }

    /**
     * [FASE2-01] UserManagement: blokir delete oleh role read-only (manager)
     * & cegah eskalasi hak akses vertikal (admin tidak boleh assign/edit/delete super_admin atau director).
     */
    public function test_fase2_01_user_management_blocks_unprivileged_delete_and_vertical_privilege_escalation(): void
    {
        $superAdmin = $this->createRoleUser('super_admin', 'superadmin@m2b.co.id');
        $director = $this->createRoleUser('director', 'director@m2b.co.id');
        $admin = $this->createRoleUser('admin', 'admin@m2b.co.id');
        $manager = $this->createRoleUser('manager', 'manager@m2b.co.id');
        $staff = $this->createRoleUser('staff_operations', 'ops@m2b.co.id');

        // 1. Manager hanya punya user.view: boleh lihat daftar, tapi ditolak 403 saat create/edit/save/delete
        Livewire::actingAs($manager)
            ->test(UserManagement::class)
            ->assertStatus(200)
            ->call('create')
            ->assertForbidden();

        Livewire::actingAs($manager)
            ->test(UserManagement::class)
            ->call('delete', $staff->id)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $staff->id]);

        // 2. Admin (level 60) tidak boleh mengangkat akun staf atau membuat akun baru menjadi super_admin / director
        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('edit', $staff->id)
            ->set('selectedRoles', ['super_admin'])
            ->call('save')
            ->assertForbidden();

        $this->assertEquals(['staff_operations'], $staff->fresh()->roles);

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('create')
            ->set('name', 'Escalated User')
            ->set('email', 'escalated@m2b.co.id')
            ->set('password', 'password123')
            ->set('selectedRoles', ['director'])
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'escalated@m2b.co.id']);

        // 3. Admin tidak boleh mengedit atau menghapus akun super_admin / director
        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('edit', $superAdmin->id)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('delete', $director->id)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $director->id]);

        // 4. Super Admin dapat mengedit dan menghapus staf biasa secara sah
        Livewire::actingAs($superAdmin)
            ->test(UserManagement::class)
            ->call('delete', $staff->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    /**
     * [FASE2-02] ShipmentManagement & ShipmentDetail: RBAC ketat & pencegahan IDOR dokumen lintas shipment.
     */
    public function test_fase2_02_shipment_management_and_shipment_detail_enforce_rbac_and_prevent_document_idor(): void
    {
        $admin = $this->createRoleUser('admin');
        $cashier = $this->createRoleUser('cashier');
        $auditor = $this->createRoleUser('auditor');
        $staffReadOnly = $this->createRoleUser('staff');
        $supervisor = $this->createRoleUser('supervisor');

        $customerUser = $this->createRoleUser('customer');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-SHP-01',
            'company_name' => 'PT Nusantara Logistik',
        ]);

        $shipmentA = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-261015-001',
            'origin' => 'Shanghai',
            'destination' => 'Belawan',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'pending',
            'weight' => 1000,
            'pieces' => 10,
        ]);

        $shipmentB = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-261015-002',
            'origin' => 'Busan',
            'destination' => 'Jakarta',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'pending',
            'weight' => 500,
            'pieces' => 5,
        ]);

        $docB = Document::create([
            'shipment_id' => $shipmentB->id,
            'document_type' => 'admin_upload',
            'filename' => 'BL_SHIPMENT_B.pdf',
            'file_path' => 'documents/public/BL_SHIPMENT_B.pdf',
            'description' => 'Bill of Lading B',
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $admin->id,
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($cashier)->get(route('admin.shipments.index'))->assertStatus(403);
        $this->actingAs($auditor)->get(route('admin.shipments.show', $shipmentA->id))->assertStatus(403);

        Livewire::actingAs($staffReadOnly)
            ->test(ShipmentManagement::class)
            ->assertStatus(200)
            ->call('quickStatusUpdate', $shipmentA->id, 'in_progress')
            ->assertForbidden();

        Livewire::actingAs($staffReadOnly)
            ->test(ShipmentManagement::class)
            ->call('openCancelModal', $shipmentA->id)
            ->assertForbidden();

        $this->assertEquals('pending', $shipmentA->fresh()->status);

        Livewire::actingAs($supervisor)
            ->test(ShipmentManagement::class)
            ->call('deleteShipment', $shipmentA->id)
            ->assertForbidden();

        $this->assertDatabaseHas('shipments', ['id' => $shipmentA->id]);

        $component = Livewire::actingAs($admin)->test(AdminShipmentDetail::class, ['id' => $shipmentA->id]);

        try {
            $component->call('deleteDocument', $docB->id);
            $this->fail('Seharusnya melempar ModelNotFoundException saat menghapus dokumen milik shipment lain.');
        } catch (ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        try {
            $component->call('openRenameModal', $docB->id);
            $this->fail('Seharusnya melempar ModelNotFoundException saat merename dokumen milik shipment lain.');
        } catch (ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseHas('documents', [
            'id' => $docB->id,
            'shipment_id' => $shipmentB->id,
            'filename' => 'BL_SHIPMENT_B.pdf',
        ]);
    }

    /**
     * [FASE2-03] QuotationManager: proteksi mutasi & penghapusan penawaran.
     */
    public function test_fase2_03_quotation_manager_blocks_unauthorized_mutations_and_delete(): void
    {
        $admin = $this->createRoleUser('admin');
        $staffReadOnly = $this->createRoleUser('staff');
        $sales = $this->createRoleUser('staff_sales');

        $quotation = Quotation::create([
            'quotation_number' => 'QT.2026.10.0001',
            'manual_company' => 'PT Calon Klien',
            'manual_pic' => 'Budi',
            'manual_email' => 'budi.klien@example.com',
            'manual_phone' => '08123456789',
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'origin' => 'Shanghai',
            'destination' => 'Jakarta',
            'service_type' => 'import',
            'quotation_type' => 'shipment',
            'status' => 'draft',
            'grand_total' => 5000000,
        ]);

        Livewire::actingAs($staffReadOnly)
            ->test(QuotationManager::class)
            ->assertStatus(200)
            ->call('create')
            ->assertForbidden();

        Livewire::actingAs($staffReadOnly)
            ->test(QuotationManager::class)
            ->call('edit', $quotation->id)
            ->assertForbidden();

        Livewire::actingAs($staffReadOnly)
            ->test(QuotationManager::class)
            ->call('convertToShipment', $quotation->id)
            ->assertForbidden();

        Livewire::actingAs($staffReadOnly)
            ->test(QuotationManager::class)
            ->call('delete', $quotation->id)
            ->assertForbidden();

        Livewire::actingAs($sales)
            ->test(QuotationManager::class)
            ->call('delete', $quotation->id)
            ->assertForbidden();

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);

        Livewire::actingAs($admin)
            ->test(QuotationManager::class)
            ->call('delete', $quotation->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
    }

    /**
     * [FASE2-04] VendorManagement: proteksi akses, perubahan rekening bank, dan penghapusan vendor.
     */
    public function test_fase2_04_vendor_management_enforces_rbac_for_view_manage_and_delete(): void
    {
        $admin = $this->createRoleUser('admin');
        $cashier = $this->createRoleUser('cashier');
        $auditor = $this->createRoleUser('auditor');
        $operations = $this->createRoleUser('staff_operations');
        $supervisor = $this->createRoleUser('supervisor');

        $vendor = Vendor::create([
            'code' => 'VEN-501',
            'name' => 'PT Pelayaran Nusantara',
            'category' => 'Shipping Line',
            'bank_details' => 'BCA 1112223334',
        ]);

        $this->actingAs($cashier)->get(route('admin.vendors.index'))->assertStatus(403);
        $this->actingAs($auditor)->get(route('admin.vendors.index'))->assertStatus(403);

        Livewire::actingAs($operations)
            ->test(VendorManagement::class)
            ->assertStatus(200)
            ->call('edit', $vendor->id)
            ->assertForbidden();

        Livewire::actingAs($operations)
            ->test(VendorManagement::class)
            ->call('delete', $vendor->id)
            ->assertForbidden();

        Livewire::actingAs($supervisor)
            ->test(VendorManagement::class)
            ->call('delete', $vendor->id)
            ->assertForbidden();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id]);

        Livewire::actingAs($admin)
            ->test(VendorManagement::class)
            ->call('delete', $vendor->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /**
     * [FASE2-05] JobCostingManager, BankReconciliation, & AuditLogManager: isolasi role read-only (auditor).
     */
    public function test_fase2_05_job_costing_bank_reconciliation_and_audit_log_isolate_read_only_roles(): void
    {
        $auditor = $this->createRoleUser('auditor');
        $operations = $this->createRoleUser('staff_operations');

        $customerUser = $this->createRoleUser('customer');
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-JC-01',
            'company_name' => 'PT Ekspor Jaya',
        ]);

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'EXP-261015-099',
            'origin' => 'Belawan',
            'destination' => 'Penang',
            'service_type' => 'export',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
        ]);

        $jobCost = JobCost::create([
            'shipment_id' => $shipment->id,
            'description' => 'Biaya Trucking',
            'amount' => 1500000,
            'status' => 'unpaid',
            'created_by' => $operations->id,
        ]);

        $bankTx = BankTransaction::create([
            'bank_name' => 'mandiri',
            'account_number' => '105008889999',
            'transaction_date' => now()->toDateString(),
            'description' => 'Transfer Masuk',
            'credit_amount' => 1500000,
            'debit_amount' => 0,
            'balance' => 10000000,
            'is_reconciled' => false,
        ]);

        Livewire::actingAs($auditor)
            ->test(JobCostingManager::class)
            ->assertStatus(200)
            ->call('saveCost')
            ->assertForbidden();

        Livewire::actingAs($auditor)
            ->test(JobCostingManager::class)
            ->call('toggleStatus', $jobCost->id)
            ->assertForbidden();

        Livewire::actingAs($auditor)
            ->test(JobCostingManager::class)
            ->call('markAsZeroCost', $shipment->id)
            ->assertForbidden();

        Livewire::actingAs($auditor)
            ->test(JobCostingManager::class)
            ->call('deleteCost', $jobCost->id)
            ->assertForbidden();

        $this->assertDatabaseHas('job_costs', ['id' => $jobCost->id, 'status' => 'unpaid']);

        Livewire::actingAs($auditor)
            ->test(BankReconciliation::class)
            ->assertStatus(200)
            ->call('runAutoMatch')
            ->assertForbidden();

        Livewire::actingAs($auditor)
            ->test(BankReconciliation::class)
            ->call('openCreateJournalModal', $bankTx->id)
            ->assertForbidden();

        Livewire::actingAs($auditor)
            ->test(BankReconciliation::class)
            ->call('deleteTransaction', $bankTx->id)
            ->assertForbidden();

        $this->assertDatabaseHas('bank_transactions', ['id' => $bankTx->id]);

        Livewire::actingAs($auditor)
            ->test(AuditLogManager::class)
            ->assertForbidden();

        Livewire::actingAs($operations)
            ->test(AuditLogManager::class)
            ->assertForbidden();
    }

    /**
     * [FASE2-06] Customer\ShipmentDetail & Customer\InvoiceController:
     * tutup celah bypass non-primary role ('customer_viewer' / 'field_uploader') & dukung multi-PIC.
     */
    public function test_fase2_06_customer_shipment_detail_and_invoice_preview_prevent_idor_and_support_multi_pic(): void
    {
        $ownerUserA = $this->createRoleUser('customer', 'owner.a@client.com');
        $customerA = Customer::create([
            'user_id' => $ownerUserA->id,
            'customer_code' => 'CUST-A-01',
            'company_name' => 'PT Customer A',
        ]);

        $viewerPicA = $this->createRoleUser('customer_viewer', 'viewer.a@client.com');
        DB::table('customer_user')->insert([
            'customer_id' => $customerA->id,
            'user_id' => $viewerPicA->id,
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownerUserB = $this->createRoleUser('customer', 'owner.b@client.com');
        $customerB = Customer::create([
            'user_id' => $ownerUserB->id,
            'customer_code' => 'CUST-B-01',
            'company_name' => 'PT Customer B',
        ]);

        $shipmentA = Shipment::create([
            'customer_id' => $customerA->id,
            'awb_number' => 'IMP-CUST-A',
            'origin' => 'Singapore',
            'destination' => 'Jakarta',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
        ]);

        $shipmentB = Shipment::create([
            'customer_id' => $customerB->id,
            'awb_number' => 'IMP-CUST-B',
            'origin' => 'Tokyo',
            'destination' => 'Surabaya',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
        ]);

        $invoiceA = Invoice::create([
            'customer_id' => $customerA->id,
            'shipment_id' => $shipmentA->id,
            'invoice_number' => 'INV-CUST-A-01',
            'type' => 'Commercial',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
            'grand_total' => 2500000,
        ]);

        $invoiceB = Invoice::create([
            'customer_id' => $customerB->id,
            'shipment_id' => $shipmentB->id,
            'invoice_number' => 'INV-CUST-B-01',
            'type' => 'Commercial',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'unpaid',
            'grand_total' => 3500000,
        ]);

        $this->assertSame($customerA->id, $ownerUserA->getCustomerId());
        $this->assertSame($customerA->id, (int) $invoiceA->fresh()->customer_id);

        $this->actingAs($ownerUserA)
            ->get(route('customer.invoices.preview', $invoiceA->id))
            ->assertOk();

        $this->actingAs($ownerUserA)
            ->get(route('customer.invoices.preview', $invoiceB->id))
            ->assertStatus(403);

        Livewire::actingAs($viewerPicA)
            ->test(CustomerShipmentDetail::class, ['id' => $shipmentA->id])
            ->assertStatus(200)
            ->assertSee('IMP-CUST-A');

        try {
            Livewire::actingAs($viewerPicA)->test(CustomerShipmentDetail::class, ['id' => $shipmentB->id]);
            $this->fail('Secondary PIC Customer A seharusnya tidak bisa membuka shipment milik Customer B.');
        } catch (ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        $fieldUploader = $this->createRoleUser('field_uploader');
        Livewire::actingAs($fieldUploader)
            ->test(CustomerShipmentDetail::class, ['id' => $shipmentA->id])
            ->assertForbidden();
    }
}
