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

        // Status should advance to export_released
        $this->assertEquals('export_released', $this->shipment->fresh()->status);
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
