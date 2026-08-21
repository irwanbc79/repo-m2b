<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShipmentManagement;
use App\Livewire\Customer\Dashboard;
use App\Livewire\Customer\ShipmentDetail as CustomerShipmentDetail;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Shipment;
use App\Models\ShipmentEtaRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentEtaRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customerUser;
    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-26 14:30:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customerUser = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_code' => 'CUST-DIRA',
            'company_name' => 'PT Dira Baraka Mulia',
        ]);

        $this->shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'IMP-260725-001',
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'status' => 'in_transit',
            'origin' => 'Shanghai, China',
            'destination' => 'Belawan, Indonesia',
            'weight' => 1000,
            'pieces' => 10,
            'estimated_arrival' => '2026-07-25',
        ]);
    }

    public function test_admin_can_record_append_only_eta_revisions(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShipmentManagement::class)
            ->call('openEtaRevisionModal', $this->shipment->id)
            ->set('etaRevisedDate', '2026-07-27')
            ->set('etaReasonCode', 'carrier_schedule')
            ->set('etaReasonNotes', 'Revised schedule dari shipping line.')
            ->set('etaSourceParty', 'Shipping Line')
            ->set('etaInformationReceivedAt', '2026-07-26T14:00')
            ->set('etaCustomerVisible', true)
            ->set('etaCustomerMessage', 'Estimasi tiba diperbarui berdasarkan jadwal shipping line.')
            ->call('saveEtaRevision')
            ->assertHasNoErrors()
            ->assertSet('showEtaRevisionModal', false);

        $this->assertDatabaseHas('shipment_eta_revisions', [
            'shipment_id' => $this->shipment->id,
            'reason_code' => 'carrier_schedule',
            'change_days' => 2,
            'customer_visible' => 1,
            'created_by' => $this->admin->id,
        ]);
        $this->assertSame('2026-07-27', $this->shipment->fresh()->estimated_arrival->toDateString());

        Livewire::test(ShipmentManagement::class)
            ->call('openEtaRevisionModal', $this->shipment->id)
            ->set('etaRevisedDate', '2026-07-29')
            ->set('etaReasonCode', 'port_congestion')
            ->set('etaSourceParty', 'Terminal')
            ->set('etaInformationReceivedAt', '2026-07-27T09:00')
            ->call('saveEtaRevision')
            ->assertHasNoErrors();

        $revisions = ShipmentEtaRevision::where('shipment_id', $this->shipment->id)
            ->oldest()
            ->get();

        $this->assertCount(2, $revisions);
        $this->assertSame('2026-07-25', $revisions[0]->previous_eta->toDateString());
        $this->assertSame('2026-07-27', $revisions[0]->revised_eta->toDateString());
        $this->assertSame('2026-07-27', $revisions[1]->previous_eta->toDateString());
        $this->assertSame('2026-07-29', $revisions[1]->revised_eta->toDateString());
        $this->assertSame('2026-07-29', $this->shipment->fresh()->estimated_arrival->toDateString());
        $this->assertSame($this->admin->id, $revisions[0]->created_by);
        $this->assertSame($this->admin->id, $revisions[1]->created_by);
    }

    public function test_same_eta_is_rejected_without_overwriting_history(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ShipmentManagement::class)
            ->call('openEtaRevisionModal', $this->shipment->id)
            ->set('etaRevisedDate', '2026-07-25')
            ->set('etaReasonCode', 'carrier_schedule')
            ->set('etaInformationReceivedAt', '2026-07-26T14:00')
            ->call('saveEtaRevision')
            ->assertHasErrors(['etaRevisedDate']);

        $this->assertDatabaseCount('shipment_eta_revisions', 0);
        $this->assertSame('2026-07-25', $this->shipment->fresh()->estimated_arrival->toDateString());
    }

    public function test_eta_evidence_is_linked_to_revision_document(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        Livewire::test(ShipmentManagement::class)
            ->call('openEtaRevisionModal', $this->shipment->id)
            ->set('etaRevisedDate', '2026-07-27')
            ->set('etaReasonCode', 'carrier_schedule')
            ->set('etaSourceParty', 'Shipping Line')
            ->set('etaInformationReceivedAt', '2026-07-26T14:00')
            ->set('etaCustomerVisible', true)
            ->set('etaCustomerMessage', 'Estimasi tiba diperbarui berdasarkan jadwal shipping line.')
            ->set('etaEvidenceCustomerVisible', true)
            ->set('etaEvidence', UploadedFile::fake()->create('revised-schedule.pdf', 200, 'application/pdf'))
            ->call('saveEtaRevision')
            ->assertHasNoErrors();

        $revision = ShipmentEtaRevision::firstOrFail();

        $this->assertNotNull($revision->source_document_id);
        $this->assertTrue($revision->sourceDocument->is_public);
        $this->assertSame('other', $revision->sourceDocument->document_type);
        Storage::disk('public')->assertExists($revision->sourceDocument->file_path);
    }

    public function test_customer_sees_published_update_and_can_acknowledge_it(): void
    {
        $revision = ShipmentEtaRevision::create([
            'shipment_id' => $this->shipment->id,
            'previous_eta' => '2026-07-25',
            'revised_eta' => '2026-07-27',
            'change_days' => 2,
            'reason_code' => 'carrier_schedule',
            'information_received_at' => now(),
            'customer_visible' => true,
            'customer_message' => 'Jadwal shipping line berubah.',
            'published_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->customerUser);

        Livewire::test(Dashboard::class)
            ->assertSee('Pembaruan Jadwal Pengiriman')
            ->assertSee('Jadwal shipping line berubah.')
            ->call('acknowledgeEtaRevision', $revision->id);

        $this->assertNotNull($revision->fresh()->viewed_at);
    }

    public function test_customer_cannot_download_internal_eta_evidence(): void
    {
        $document = Document::create([
            'shipment_id' => $this->shipment->id,
            'document_type' => 'other',
            'filename' => 'internal.pdf',
            'file_path' => 'documents/eta_revisions/internal.pdf',
            'is_internal' => true,
            'is_public' => false,
            'uploaded_by' => $this->admin->id,
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($this->customerUser)
            ->get(route('document.download', $document->id))
            ->assertForbidden();
    }

    public function test_admin_can_publish_existing_eta_revision_without_changing_history(): void
    {
        $document = Document::create([
            'shipment_id' => $this->shipment->id,
            'document_type' => 'other',
            'filename' => 'schedule.pdf',
            'file_path' => 'documents/eta_revisions/schedule.pdf',
            'is_internal' => true,
            'is_public' => false,
            'uploaded_by' => $this->admin->id,
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $revision = ShipmentEtaRevision::create([
            'shipment_id' => $this->shipment->id,
            'previous_eta' => '2026-07-25',
            'revised_eta' => '2026-07-27',
            'change_days' => 2,
            'reason_code' => 'carrier_schedule',
            'information_received_at' => now(),
            'source_document_id' => $document->id,
            'customer_visible' => false,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ShipmentManagement::class)
            ->call('openEtaPublicationModal', $revision->id)
            ->assertSet('publicationCustomerMessage', fn ($message) =>
                str_contains($message, 'IMP-260725-001')
                && str_contains($message, '25 Jul 2026')
                && str_contains($message, '27 Jul 2026')
                && str_contains($message, 'shipping line')
            )
            ->set('publicationCustomerVisible', true)
            ->set('publicationCustomerMessage', 'Jadwal tiba diperbarui oleh shipping line.')
            ->set('publicationEvidenceVisible', true)
            ->call('saveEtaPublication')
            ->assertHasNoErrors()
            ->assertSet('showEtaPublicationModal', false);

        $revision->refresh();
        $this->assertTrue($revision->customer_visible);
        $this->assertSame('2026-07-25', $revision->previous_eta->toDateString());
        $this->assertSame('2026-07-27', $revision->revised_eta->toDateString());
        $this->assertNotNull($revision->published_at);
        $this->assertTrue($document->fresh()->is_public);
        $this->assertFalse($document->fresh()->is_internal);
    }

    public function test_customer_can_preview_published_eta_evidence_in_modal(): void
    {
        $document = Document::create([
            'shipment_id' => $this->shipment->id,
            'document_type' => 'other',
            'filename' => 'revised-schedule.pdf',
            'file_path' => 'documents/eta_revisions/revised-schedule.pdf',
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $this->admin->id,
            'file_size' => 100,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($this->customerUser);

        Livewire::test(CustomerShipmentDetail::class, ['id' => $this->shipment->id])
            ->call('viewDocument', $document->id)
            ->assertSet('showDocPreview', true)
            ->assertSet('previewDoc.id', $document->id);
    }
}
