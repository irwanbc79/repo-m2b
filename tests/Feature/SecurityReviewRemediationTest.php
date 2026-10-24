<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityReviewRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_customer_cannot_search_shipments_or_upload_via_field_docs_api()
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
            'is_active' => true,
        ]);

        $token = $customerUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/field-docs/shipments/search?q=test');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized: Hanya petugas lapangan atau staf internal yang diizinkan mengakses dokumentasi lapangan.',
        ]);

        $uploadResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/field-docs/upload', [
                'shipment_id' => 1,
            ]);

        $uploadResponse->assertStatus(403);
        $uploadResponse->assertJson([
            'success' => false,
            'message' => 'Unauthorized: Hanya petugas lapangan atau staf internal yang diizinkan mengakses dokumentasi lapangan.',
        ]);
    }

    public function test_field_uploader_can_search_shipments_via_api()
    {
        $fieldUser = User::factory()->create([
            'role' => 'field_uploader',
            'roles' => ['field_uploader'],
            'is_active' => true,
        ]);

        $token = $fieldUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/field-docs/shipments/search?q=abc');

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_customer_cannot_access_mobile_field_upload_web_route()
    {
        $customerUser = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($customerUser)->get('/mobile/upload');
        $response->assertStatus(403);
    }

    public function test_inactive_user_is_rejected_at_web_login()
    {
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@m2b.co.id',
            'password' => Hash::make('secret123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@m2b.co.id',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Akun Anda tidak aktif atau sedang menunggu persetujuan administrator.',
        ]);
        $this->assertGuest();
    }

    public function test_inactive_user_token_is_rejected_on_api_requests()
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'roles' => ['staff'],
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile-token')->plainTextToken;

        // Verify active token works
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/profile');
        $response->assertStatus(200);

        // Deactivate user and clear cached guard state
        $user->update(['is_active' => false]);
        $this->app['auth']->forgetGuards();

        // Attempt API request with existing token
        $responseDeactivated = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/profile');

        $responseDeactivated->assertStatus(403);
        $responseDeactivated->assertJson([
            'success' => false,
            'message' => 'Akun tidak aktif. Hubungi administrator.',
        ]);
    }

    public function test_email_attachment_mailbox_mismatch_returns_404()
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'roles' => ['super_admin'],
            'is_active' => true,
        ]);

        $emailId = DB::table('emails')->insertGetId([
            'mailbox' => 'ops@m2b.co.id',
            'uid' => 101,
            'subject' => 'Shipment Documents',
            'from_email' => 'sender@example.com',
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('local')->put('email_attachments/ops@m2b.co.id/101/invoice.pdf', "%PDF-1.4\n%...\n%%EOF");

        $attachmentId = DB::table('email_attachments')->insertGetId([
            'email_id' => $emailId,
            'filename' => 'invoice.pdf',
            'file_path' => 'email_attachments/ops@m2b.co.id/101/invoice.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'created_at' => now(),
        ]);

        // Request with spoofed mailbox parameter
        $response = $this->actingAs($admin)->get("/admin/inbox/attachment/spoofed@m2b.co.id/{$attachmentId}");
        $response->assertStatus(404);

        // Request with correct mailbox succeeds
        $correctResponse = $this->actingAs($admin)->get("/admin/inbox/attachment/ops@m2b.co.id/{$attachmentId}");
        $correctResponse->assertStatus(200);
        $correctResponse->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_email_attachment_html_is_never_served_inline()
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'roles' => ['super_admin'],
            'is_active' => true,
        ]);

        $emailId = DB::table('emails')->insertGetId([
            'mailbox' => 'info@m2b.co.id',
            'uid' => 202,
            'subject' => 'Malicious file test',
            'from_email' => 'hacker@example.com',
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('local')->put('email_attachments/info@m2b.co.id/202/payload.html', '<html><script>alert(1)</script></html>');

        $attachmentId = DB::table('email_attachments')->insertGetId([
            'email_id' => $emailId,
            'filename' => 'payload.html',
            'file_path' => 'email_attachments/info@m2b.co.id/202/payload.html',
            'mime_type' => 'text/html',
            'file_size' => 45,
            'created_at' => now(),
        ]);

        // Request with mode=inline must NOT serve inline HTML
        $response = $this->actingAs($admin)->get("/admin/inbox/attachment/info@m2b.co.id/{$attachmentId}?mode=inline");
        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringNotContainsString('inline', $disposition);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_document_download_resolves_via_storage_disk_path()
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'company_name' => 'PT Test Customer',
            'customer_code' => 'CUST-001',
        ]);

        $user->update(['customer_id' => $customer->id]);

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'AWB-TEST-001',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'service_type' => 'domestic',
            'shipment_type' => 'land',
            'status' => 'in_progress',
        ]);

        Storage::disk('local')->put('documents/test_doc.pdf', 'DUMMY PDF CONTENT');

        $doc = Document::create([
            'shipment_id' => $shipment->id,
            'uploaded_by' => $user->id,
            'document_type' => 'invoice',
            'filename' => 'test_doc.pdf',
            'file_path' => 'documents/test_doc.pdf',
            'file_size' => 16,
            'mime_type' => 'application/pdf',
            'is_public' => true,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('document.download', $doc->id));
        $response->assertStatus(200);
        $this->assertStringContainsString('test_doc.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_customer_document_upload_component_processes_upload_successfully()
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'roles' => ['customer'],
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'company_name' => 'PT Test Upload Cust',
            'customer_code' => 'CUST-002',
        ]);

        DB::table('customer_user')->insert([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->refresh();

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'AWB-TEST-002',
            'origin' => 'Singapore',
            'destination' => 'Jakarta',
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
        ]);

        $file = UploadedFile::fake()->create('customs_invoice.pdf', 150, 'application/pdf');

        $test = Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\DocumentUpload::class, ['shipmentId' => $shipment->id])
            ->set('documentType', 'invoice')
            ->set('description', 'Commercial Invoice for Shipment')
            ->set('files', [$file])
            ->call('uploadDocuments');

        $test->assertHasNoErrors()
            ->assertDispatched('documentsUploaded')
            ->assertSee('document(s) uploaded successfully');

        $this->assertDatabaseHas('documents', [
            'shipment_id' => $shipment->id,
            'document_type' => 'invoice',
            'filename' => 'customs_invoice.pdf',
        ]);
    }
}
