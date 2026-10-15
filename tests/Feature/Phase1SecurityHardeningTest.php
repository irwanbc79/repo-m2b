<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\BankReconciliationExportController;
use App\Http\Controllers\Admin\EmailAttachmentController;
use App\Livewire\Admin\Accounting\CashierManagement;
use App\Livewire\Admin\CustomerManagement;
use App\Livewire\Admin\InvoiceManager;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\SimpleCashier;
use App\Livewire\Customer\InvoiceList;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\SimpleInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase1SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_kritis_01_customer_management_blocks_privilege_escalation_and_unauthorized_mutations(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'roles' => ['admin']]);
        $auditor = User::factory()->create(['role' => 'auditor', 'roles' => ['auditor']]);
        $sales = User::factory()->create(['role' => 'staff_sales', 'roles' => ['staff_sales']]);

        Livewire::actingAs($admin)
            ->test(CustomerManagement::class)
            ->call('create')
            ->set('company_name', 'PT Aman Sentosa')
            ->set('name', 'Budi Customer')
            ->set('email', 'budi.aman@example.com')
            ->set('password', 'secret123')
            ->set('role', 'super_admin')
            ->call('save')
            ->assertHasNoErrors();

        $createdUser = User::where('email', 'budi.aman@example.com')->firstOrFail();
        $this->assertSame('customer', $createdUser->role);
        $this->assertSame(['customer'], $createdUser->roles);

        $customer = Customer::where('company_name', 'PT Aman Sentosa')->firstOrFail();

        // Auditor ditolak saat mount/render CustomerManagement
        Livewire::actingAs($auditor)
            ->test(CustomerManagement::class)
            ->assertForbidden();

        // Sales boleh akses & manage customer tapi dilarang delete customer
        Livewire::actingAs($sales)
            ->test(CustomerManagement::class)
            ->call('delete', $customer->id)
            ->assertForbidden();
    }

    public function test_kritis_02_simple_invoice_customer_search_and_document_routes_enforce_rbac(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'roles' => ['admin']]);
        $customerUser = User::factory()->create(['role' => 'customer', 'roles' => ['customer']]);
        $fieldUploader = User::factory()->create(['role' => 'field_uploader', 'roles' => ['field_uploader']]);
        $auditor = User::factory()->create(['role' => 'auditor', 'roles' => ['auditor']]);

        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'customer_code' => 'CUST-SEC-01',
            'company_name' => 'PT Customer Satu',
        ]);

        $simpleInvoice = SimpleInvoice::create([
            'invoice_number' => 'SINV-TEST-001',
            'customer_name' => 'PT Customer Satu',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 100000,
            'total' => 100000,
            'status' => 'unpaid',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($customerUser)
            ->get(route('finance.simple-invoice.index'))
            ->assertForbidden();

        $this->actingAs($customerUser)
            ->get(route('finance.simple-invoice.detail', $simpleInvoice->id))
            ->assertForbidden();

        $this->actingAs($customerUser)
            ->get('/api/customers/search?q=PT')
            ->assertForbidden();

        $this->actingAs($auditor)
            ->get(route('finance.simple-invoice.detail', $simpleInvoice->id))
            ->assertOk();

        $this->actingAs($auditor)
            ->get(route('finance.simple-invoice.create'))
            ->assertForbidden();

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'awb_number' => 'AWB-SEC-001',
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'status' => 'in_transit',
            'origin' => 'SIN',
            'destination' => 'BLW',
            'weight' => 100,
            'pieces' => 5,
        ]);

        $doc = Document::create([
            'shipment_id' => $shipment->id,
            'document_type' => 'other',
            'filename' => 'public.pdf',
            'file_path' => 'documents/public.pdf',
            'is_internal' => false,
            'is_public' => true,
            'uploaded_by' => $admin->id,
            'file_size' => 120,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($fieldUploader)
            ->get(route('document.view', $doc->id))
            ->assertForbidden();

        $this->actingAs($fieldUploader)
            ->get(route('document.download', $doc->id))
            ->assertForbidden();
    }

    public function test_tinggi_03_customer_invoice_list_prevents_idor_across_customers(): void
    {
        $userA = User::factory()->create(['role' => 'customer', 'roles' => ['customer']]);
        $userB = User::factory()->create(['role' => 'customer', 'roles' => ['customer']]);

        Customer::create([
            'user_id' => $userA->id,
            'customer_code' => 'CUST-A',
            'company_name' => 'PT Customer A',
        ]);
        $customerB = Customer::create([
            'user_id' => $userB->id,
            'customer_code' => 'CUST-B',
            'company_name' => 'PT Customer B',
        ]);

        $shipmentB = Shipment::create([
            'customer_id' => $customerB->id,
            'awb_number' => 'AWB-B-001',
            'shipment_type' => 'sea',
            'service_type' => 'import',
            'status' => 'in_transit',
            'origin' => 'SHA',
            'destination' => 'BLW',
            'weight' => 100,
            'pieces' => 1,
        ]);

        $invoiceB = Invoice::create([
            'invoice_number' => 'INV-B-001',
            'customer_id' => $customerB->id,
            'shipment_id' => $shipmentB->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 500000,
            'grand_total' => 500000,
            'status' => 'unpaid',
        ]);

        Livewire::actingAs($userA)
            ->test(InvoiceList::class)
            ->call('openUploadModal', $invoiceB->id)
            ->assertForbidden();

        Livewire::actingAs($userA)
            ->test(InvoiceList::class)
            ->call('openPaymentHistory', $invoiceB->id)
            ->assertForbidden();

        Livewire::actingAs($userA)
            ->test(InvoiceList::class)
            ->call('openBupotModal', $invoiceB->id)
            ->assertForbidden();

        Livewire::actingAs($userA)
            ->test(InvoiceList::class)
            ->set('selectedInvoiceId', $invoiceB->id)
            ->call('uploadPaymentProof')
            ->assertForbidden();
    }

    public function test_tinggi_04_email_body_sanitizes_xss_and_sets_strict_csp(): void
    {
        $rawHtml = '<div>Halo <script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:evil()">Klik</a><iframe src="https://evil.test"></iframe></div>';
        $sanitized = EmailAttachmentController::sanitizeEmailHtml($rawHtml);

        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('onerror=', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('<iframe', $sanitized);

        $admin = User::factory()->create(['role' => 'admin', 'roles' => ['admin']]);
        $logId = DB::table('emails')->insertGetId([
            'mailbox' => 'sales',
            'uid' => 999,
            'message_id' => '<xss-test@m2b.co.id>',
            'from_email' => 'attacker@example.com',
            'subject' => 'XSS Test',
            'body' => $rawHtml,
            'email_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.inbox.body', ['id' => $logId]));

        $response->assertOk();
        $this->assertStringContainsString("script-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('<script>', $response->getContent());
        $this->assertStringNotContainsString('onerror=', $response->getContent());
    }

    public function test_tinggi_05_and_sedang_06_07_08_09_rbac_webhook_and_sso_hardening(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'roles' => ['admin'], 'is_active' => true]);
        $token = 'test_sso_token_12345';
        Cache::put("sso_token:{$token}", [
            'user_id' => $admin->id,
            'redirect' => '//evil.example.com/phish',
        ], now()->addMinute());

        $ssoRequest = \Illuminate\Http\Request::create('/sso/login', 'GET', ['token' => $token]);
        $ssoRequest->setLaravelSession($this->app['session.store']);
        $ssoResponse = app(\App\Http\Controllers\SsoWebController::class)->login($ssoRequest);
        $this->assertSame(url('/admin/dashboard'), $ssoResponse->getTargetUrl());

        config(['services.mora.portal_secret' => '']);
        $this->postJson('/api/mora-lead-incoming', [
            'company_name' => 'PT Test',
            'pic_name' => 'Test',
        ])->assertStatus(401);

        $auditor = User::factory()->create(['role' => 'auditor', 'roles' => ['auditor']]);
        $konsultanPajak = User::factory()->create(['role' => 'konsultan_pajak', 'roles' => ['konsultan_pajak']]);
        $cashier = User::factory()->create(['role' => 'cashier', 'roles' => ['cashier']]);

        Livewire::actingAs($auditor)
            ->test(InvoiceManager::class)
            ->call('deletePayment', 1)
            ->assertForbidden();

        Livewire::actingAs($konsultanPajak)
            ->test(InvoiceManager::class)
            ->call('deleteFakturPajak', 1)
            ->assertForbidden();

        $this->actingAs($auditor);
        try {
            app(BankReconciliationExportController::class)->exportExcel(request());
            $this->fail('Auditor seharusnya ditolak 403 pada BankReconciliationExportController::exportExcel');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->actingAs($konsultanPajak);
        try {
            app(BankReconciliationExportController::class)->exportPdf(request());
            $this->fail('Konsultan pajak seharusnya ditolak 403 pada BankReconciliationExportController::exportPdf');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        Livewire::actingAs($cashier)
            ->test(Reports::class)
            ->assertForbidden();

        Livewire::actingAs($konsultanPajak)
            ->test(Reports::class)
            ->assertForbidden();

        Livewire::actingAs($auditor)
            ->test(CashierManagement::class)
            ->assertForbidden();

        Livewire::actingAs($konsultanPajak)
            ->test(SimpleCashier::class)
            ->assertForbidden();
    }
}
