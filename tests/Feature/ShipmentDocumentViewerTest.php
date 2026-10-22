<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentDocumentViewerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private Customer $customer;
    private Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'roles' => ['admin'],
        ]);

        $this->customerUser = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_code' => 'CUST-001',
            'company_name' => 'PT Test Customer',
        ]);

        $this->shipment = Shipment::create([
            'customer_id' => $this->customer->id,
            'bl_number' => 'BL-DOCX-001',
            'awb_number' => 'BL-DOCX-001',
            'status' => 'draft',
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'origin' => 'Shanghai, China',
            'destination' => 'Tanjung Priok, Jakarta',
            'weight' => 1000,
        ]);
    }

    public function test_document_view_serves_docx_with_inline_disposition(): void
    {
        $path = 'documents/penjelasan_alur.docx';
        Storage::disk('public')->put($path, 'fake docx content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'penjelasan_alur.docx',
            'description' => 'Penjelasan Alur Nilai Pabean',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 1024,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('document.view', $doc->id));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('penjelasan_alur.docx', $response->headers->get('Content-Disposition'));
    }

    public function test_document_view_serves_xlsx_with_inline_disposition(): void
    {
        $path = 'documents/packing_list.xlsx';
        Storage::disk('public')->put($path, 'fake xlsx content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'packing_list.xlsx',
            'description' => 'Packing List Final',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 2048,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('document.view', $doc->id));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_document_view_serves_image_with_inline_disposition(): void
    {
        $path = 'documents/foto_kontainer.jpg';
        Storage::disk('public')->put($path, 'fake image content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'foto_kontainer.jpg',
            'description' => 'Foto Kontainer',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 3072,
            'mime_type' => 'image/jpeg',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('document.view', $doc->id));

        $response->assertOk();
        $this->assertStringContainsString('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_customer_can_view_public_docx_in_their_shipment(): void
    {
        $path = 'documents/surat_pernyataan.docx';
        Storage::disk('public')->put($path, 'fake docx content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'surat_pernyataan.docx',
            'description' => 'Surat Pernyataan',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->customerUser->id,
            'file_size' => 1500,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->customerUser)->get(route('document.view', $doc->id));
        $response->assertOk();
    }

    public function test_other_customer_cannot_view_docx(): void
    {
        $path = 'documents/rahasia.docx';
        Storage::disk('public')->put($path, 'fake docx content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'rahasia.docx',
            'description' => 'Dokumen Rahasia',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 1500,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_at' => now(),
        ]);

        $otherCustomerUser = User::factory()->create(['role' => 'customer', 'roles' => ['customer']]);
        Customer::create([
            'user_id' => $otherCustomerUser->id,
            'customer_code' => 'CUST-002',
            'company_name' => 'PT Other Customer',
        ]);

        $response = $this->actingAs($otherCustomerUser)->get(route('document.view', $doc->id));
        $response->assertForbidden();
    }

    public function test_admin_livewire_shipment_detail_opens_docx_preview(): void
    {
        $path = 'documents/alur_nilai_pabean.docx';
        Storage::disk('public')->put($path, 'fake docx content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'alur_nilai_pabean.docx',
            'description' => 'Penjelasan Alur Nilai Pabean',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 1234,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('viewDocument', $doc->id)
            ->assertSet('showDocPreview', true)
            ->assertSet('previewDoc.id', $doc->id)
            ->assertSeeHtml('docx-render-' . $doc->id);
    }

    public function test_customer_livewire_shipment_detail_opens_xlsx_preview(): void
    {
        $path = 'documents/packing_list.xlsx';
        Storage::disk('public')->put($path, 'fake xlsx content');

        $doc = Document::create([
            'shipment_id' => $this->shipment->id,
            'filename' => 'packing_list.xlsx',
            'description' => 'Detail Packing List',
            'file_path' => $path,
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 4567,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'uploaded_at' => now(),
        ]);

        Livewire::actingAs($this->customerUser)
            ->test(\App\Livewire\Customer\ShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('viewDocument', $doc->id)
            ->assertSet('showDocPreview', true)
            ->assertSet('previewDoc.id', $doc->id)
            ->assertSeeHtml('Memuat File Excel');
    }
}
