<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShipmentDetail;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
        ]);

        $customer = Customer::create([
            'user_id' => $this->admin->id,
            'company_name' => 'PT. INDO BINTANG REZKI',
            'customer_code' => 'CUST-000101',
        ]);

        $this->shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'EXP-260821-512',
            'shipment_type' => 'sea',
            'service_type' => 'export',
            'status' => 'document_collection',
            'origin' => 'BELAWAN, INDONESIA',
            'destination' => 'KARACHI, PAKISTAN',
        ]);
    }

    public function test_admin_can_upload_npe_document_and_updates_status(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->create("NPE PT. IBR 2X40'.pdf", 500, 'application/pdf');

        Livewire::test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->set('doc_type', 'NPE')
            ->set('file_upload', $file)
            ->call('uploadPublic')
            ->assertHasNoErrors()
            ->assertSee('Dokumen publik diunggah');

        // Document should be created
        $this->assertDatabaseHas('documents', [
            'shipment_id' => $this->shipment->id,
            'description' => 'NPE',
            'is_internal' => false,
        ]);

        // Status should advance to export_released and lane_status to green
        $fresh = $this->shipment->fresh();
        $this->assertEquals('export_released', $fresh->status);
        $this->assertEquals('green', $fresh->lane_status);
        $this->assertEquals('green', $fresh->effective_lane_status);
    }

    public function test_admin_can_upload_ppb_document_and_sets_red_lane(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->create("PPB PT. IBR.pdf", 500, 'application/pdf');

        Livewire::test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->set('doc_type', 'PPB')
            ->set('file_upload', $file)
            ->call('uploadPublic')
            ->assertHasNoErrors();

        $fresh = $this->shipment->fresh();
        $this->assertEquals('physical_inspection', $fresh->status);
        $this->assertEquals('red', $fresh->lane_status);
        $this->assertEquals('red', $fresh->effective_lane_status);
    }

    public function test_compute_lane_status_from_documents_fallback(): void
    {
        // Shipment without lane_status in DB, but with NPE document
        $this->shipment->documents()->create([
            'description' => 'NPE Dokumen Bea Cukai',
            'file_path' => 'docs/npe.pdf',
            'filename' => 'npe.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'uploaded_at' => now(),
        ]);

        $this->shipment->update(['lane_status' => null]);
        $fresh = $this->shipment->fresh(['documents']);

        $this->assertNull($fresh->lane_status);
        $this->assertEquals('green', $fresh->computeLaneStatusFromDocuments());
        $this->assertEquals('green', $fresh->effective_lane_status);
    }

    public function test_upload_without_file_shows_clear_indonesian_error(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShipmentDetail::class, ['id' => $this->shipment->id])
            ->set('doc_type', 'NPE')
            ->set('file_upload', null)
            ->call('uploadPublic')
            ->assertHasErrors(['file_upload']);
    }
}
