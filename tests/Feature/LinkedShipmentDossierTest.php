<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShipmentDetail as AdminShipmentDetail;
use App\Livewire\Customer\ShipmentDetail as CustomerShipmentDetail;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LinkedShipmentDossierTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomerWithUser(string $code, string $company): array
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'company_name' => $company,
        ]);

        return [$user, $customer];
    }

    public function test_admin_can_create_linked_re_export_from_rejected_import_and_documents_are_unified(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff_ppjk',
            'roles' => ['staff_ppjk'],
            'is_active' => true,
        ]);
        [, $customer] = $this->createCustomerWithUser('CUST-RE-01', 'PT Importir Utama');

        $importShipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-2026-001',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'Shanghai, CN',
            'destination' => 'Tanjung Priok, ID',
            'shipper_name' => 'Shanghai Machine Co.',
            'consignee_name' => 'PT Importir Utama',
            'commodity' => 'Pompa Industri',
            'hs_code' => '8413.70.11',
            'weight' => 4500,
            'volume' => 18.5,
            'pieces' => 10,
            'status' => Shipment::STATUS_IN_PROGRESS,
        ]);

        $importDoc = Document::create([
            'shipment_id' => $importShipment->id,
            'document_type' => 'pib',
            'filename' => 'SPBL_IMP_001.pdf',
            'file_path' => 'documents/SPBL_IMP_001.pdf',
            'description' => 'SPBL Penolakan Lartas',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $staff->id,
            'uploaded_at' => now(),
        ]);

        Livewire::actingAs($staff)
            ->test(AdminShipmentDetail::class, ['id' => $importShipment->id])
            ->call('openFollowUpModal')
            ->assertSet('showFollowUpModal', true)
            ->assertSet('followUpRegime', Shipment::REGIME_RE_EXPORT_REJECTION)
            ->assertSet('followUpOrigin', 'Tanjung Priok, ID')
            ->assertSet('followUpDestination', 'Shanghai, CN')
            ->assertSet('markParentReExport', true)
            ->set('followUpReference', 'S-123/KPU.01/2026')
            ->call('createFollowUpShipment')
            ->assertHasNoErrors()
            ->assertSet('showFollowUpModal', false);

        $importShipment->refresh();
        $this->assertSame(Shipment::STATUS_RE_EXPORT, $importShipment->status);

        $reExport = Shipment::where('parent_shipment_id', $importShipment->id)->firstOrFail();
        $this->assertSame('REX-2026-001', $reExport->awb_number);
        $this->assertSame('export', $reExport->service_type);
        $this->assertSame(Shipment::REGIME_RE_EXPORT_REJECTION, $reExport->customs_regime);
        $this->assertSame($customer->id, $reExport->customer_id);
        $this->assertSame('Tanjung Priok, ID', $reExport->origin);
        $this->assertSame('Shanghai, CN', $reExport->destination);
        $this->assertSame('PT Importir Utama', $reExport->shipper_name);
        $this->assertSame('Shanghai Machine Co.', $reExport->consignee_name);
        $this->assertSame('8413.70.11', $reExport->hs_code);
        $this->assertSame('S-123/KPU.01/2026', $reExport->customs_reference_number);

        // Dari halaman Re-Ekspor, staf bisa langsung preview dokumen dari shipment impor asal
        Livewire::actingAs($staff)
            ->test(AdminShipmentDetail::class, ['id' => $reExport->id])
            ->assertSee('IMP-2026-001')
            ->assertSee('SPBL Penolakan Lartas')
            ->call('viewLinkedDocument', $importDoc->id)
            ->assertSet('showDocPreview', true)
            ->assertSet('previewDoc.id', $importDoc->id);
    }

    public function test_admin_can_tag_temporary_import_with_deadline_and_create_partial_re_exports(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff_operations',
            'roles' => ['staff_operations'],
            'is_active' => true,
        ]);
        [, $customer] = $this->createCustomerWithUser('CUST-TMP-01', 'PT Pameran Nusantara');

        $tempImport = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-TMP-2026',
            'service_type' => 'import',
            'shipment_type' => 'air',
            'origin' => 'Tokyo, JP',
            'destination' => 'Soekarno-Hatta, ID',
            'commodity' => 'Mesin Demo Pameran',
            'pieces' => 10,
            'weight' => 1000,
            'status' => Shipment::STATUS_IN_PROGRESS,
        ]);

        // Tandai sebagai Impor Sementara + tenggat 15 hari lagi
        $deadline = now()->addDays(15)->format('Y-m-d');
        Livewire::actingAs($staff)
            ->test(AdminShipmentDetail::class, ['id' => $tempImport->id])
            ->call('openRegimeModal')
            ->set('regimeType', Shipment::REGIME_TEMPORARY_IMPORT)
            ->set('regimeDeadline', $deadline)
            ->set('regimeReference', 'SKEP-99/WBC.04/2026')
            ->set('regimeNotes', 'Bank Garansi Rp 200jt')
            ->call('saveRegime')
            ->assertHasNoErrors();

        $tempImport->refresh();
        $this->assertSame(Shipment::REGIME_TEMPORARY_IMPORT, $tempImport->customs_regime);
        $this->assertTrue($tempImport->isCustomsDeadlineApproaching(30));
        $this->assertFalse($tempImport->isCustomsDeadlineOverdue());

        // Buat 2 re-ekspor parsial (6 unit + 4 unit)
        Livewire::actingAs($staff)
            ->test(AdminShipmentDetail::class, ['id' => $tempImport->id])
            ->call('openFollowUpModal')
            ->assertSet('followUpRegime', Shipment::REGIME_RE_EXPORT_TEMP)
            ->assertSet('markParentReExport', false)
            ->set('followUpPieces', 6)
            ->set('followUpWeight', 600)
            ->call('createFollowUpShipment')
            ->assertHasNoErrors();

        Livewire::actingAs($staff)
            ->test(AdminShipmentDetail::class, ['id' => $tempImport->id])
            ->call('openFollowUpModal')
            ->set('followUpPieces', 4)
            ->set('followUpWeight', 400)
            ->call('createFollowUpShipment')
            ->assertHasNoErrors();

        $this->assertSame(2, $tempImport->childShipments()->count());
        $this->assertSame(10, (int) $tempImport->childShipments()->sum('pieces'));
    }

    public function test_admin_can_create_re_import_from_export_shipment(): void
    {
        $staff = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
            'is_active' => true,
        ]);
        [, $customer] = $this->createCustomerWithUser('CUST-EXP-01', 'PT Eksportir Jaya');

        $exportShipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'EXP-2026-010',
            'service_type' => 'export',
            'shipment_type' => 'sea',
            'origin' => 'Tanjung Priok, ID',
            'destination' => 'Singapore, SG',
            'shipper_name' => 'PT Eksportir Jaya',
            'consignee_name' => 'SG Buyer Pte Ltd',
            'commodity' => 'Komponen Elektronik',
            'hs_code' => '8504.40.90',
            'status' => Shipment::STATUS_COMPLETED,
        ]);

        Livewire::actingAs($staff)
            ->test(AdminShipmentDetail::class, ['id' => $exportShipment->id])
            ->call('openFollowUpModal')
            ->assertSet('followUpRegime', Shipment::REGIME_RE_IMPORT_RETURN)
            ->assertSet('followUpOrigin', 'Singapore, SG')
            ->assertSet('followUpDestination', 'Tanjung Priok, ID')
            ->call('createFollowUpShipment')
            ->assertHasNoErrors();

        $reImport = Shipment::where('parent_shipment_id', $exportShipment->id)->firstOrFail();
        $this->assertSame('RIM-2026-010', $reImport->awb_number);
        $this->assertSame('import', $reImport->service_type);
        $this->assertSame(Shipment::REGIME_RE_IMPORT_RETURN, $reImport->customs_regime);
        $this->assertSame('SG Buyer Pte Ltd', $reImport->shipper_name);
        $this->assertSame('PT Eksportir Jaya', $reImport->consignee_name);
    }

    public function test_customer_sees_linked_public_documents_but_not_internal_docs(): void
    {
        [$customerUser, $customer] = $this->createCustomerWithUser('CUST-DOC-01', 'PT Aman Logistik');

        $parent = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-CUST-01',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'Busan, KR',
            'destination' => 'Tanjung Priok, ID',
            'status' => Shipment::STATUS_RE_EXPORT,
        ]);

        $child = Shipment::create([
            'customer_id' => $customer->id,
            'parent_shipment_id' => $parent->id,
            'awb_number' => 'IMP-CUST-01-RE',
            'service_type' => 'export',
            'shipment_type' => 'sea',
            'customs_regime' => Shipment::REGIME_RE_EXPORT_REJECTION,
            'origin' => 'Tanjung Priok, ID',
            'destination' => 'Busan, KR',
            'status' => Shipment::STATUS_PENDING,
        ]);

        $publicDoc = Document::create([
            'shipment_id' => $parent->id,
            'document_type' => 'bl',
            'filename' => 'BL_ASAL.pdf',
            'file_path' => 'documents/BL_ASAL.pdf',
            'description' => 'Bill of Lading Asal',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $customerUser->id,
            'uploaded_at' => now(),
        ]);

        $internalDoc = Document::create([
            'shipment_id' => $parent->id,
            'document_type' => 'internal_note',
            'filename' => 'INTERNAL_BIAYA.pdf',
            'file_path' => 'documents/INTERNAL_BIAYA.pdf',
            'description' => 'Catatan Internal M2B',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'is_internal' => true,
            'is_public' => false,
            'uploaded_by' => $customerUser->id,
            'uploaded_at' => now(),
        ]);

        // Customer membuka shipment Re-Ekspor: melihat dokumen publik induk, TIDAK melihat dokumen internal
        Livewire::actingAs($customerUser)
            ->test(CustomerShipmentDetail::class, ['id' => $child->id])
            ->assertSee('IMP-CUST-01')
            ->assertSee('Bill of Lading Asal')
            ->assertDontSee('Catatan Internal M2B')
            ->call('viewLinkedDocument', $publicDoc->id)
            ->assertSet('showDocPreview', true)
            ->assertSet('previewDoc.id', $publicDoc->id);

        // Mencoba akses dokumen internal induk via viewLinkedDocument harus 404
        Livewire::actingAs($customerUser)
            ->test(CustomerShipmentDetail::class, ['id' => $child->id])
            ->call('viewLinkedDocument', $internalDoc->id)
            ->assertNotFound();
    }

    public function test_shipment_management_filters_by_customs_regime_and_shows_linked_chip(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
            'is_active' => true,
        ]);
        [, $customer] = $this->createCustomerWithUser('CUST-FLT-01', 'PT Dira Baraka Mulia');

        $regular = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-REG-001',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'Shanghai',
            'destination' => 'Belawan',
            'status' => Shipment::STATUS_IN_PROGRESS,
        ]);

        $parent = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-261114-143',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'origin' => 'Ningbo',
            'destination' => 'Belawan',
            'status' => Shipment::STATUS_RE_EXPORT,
        ]);

        $reExport = Shipment::create([
            'customer_id' => $customer->id,
            'parent_shipment_id' => $parent->id,
            'awb_number' => 'REX-261114-143',
            'service_type' => 'export',
            'shipment_type' => 'sea',
            'customs_regime' => Shipment::REGIME_RE_EXPORT_REJECTION,
            'customs_reference_number' => 'SPBL-88/KPU/2026',
            'origin' => 'Belawan',
            'destination' => 'Ningbo',
            'status' => Shipment::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ShipmentManagement::class)
            ->set('filterCustomsRegime', 're_export')
            ->assertSee('261114-143')
            ->assertSee('SPBL-88/KPU/2026')
            ->assertDontSee('REG-001');
    }
}
