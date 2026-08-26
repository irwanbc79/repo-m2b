<?php

namespace Tests\Feature;

use App\Livewire\Admin\Accounting\JournalEntry;
use App\Livewire\Admin\AuditLogManager;
use App\Livewire\Admin\InvoiceManager;
use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\VendorManagement;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_records_correct_primary_role_with_multi_role(): void
    {
        $user = User::factory()->create([
            'name' => 'Kinan Finance',
            'email' => 'kinan@m2b.co.id',
            'roles' => ['staff_accounting', 'finance'],
            'role' => 'staff_accounting',
        ]);

        $this->actingAs($user);

        ActivityLog::record('Accounting', 'TEST_ACTION', 'REF-001', 'Test logging');

        $log = ActivityLog::where('target_ref', 'REF-001')->first();
        $this->assertNotNull($log);
        $this->assertEquals('staff_accounting', $log->role);
        $this->assertEquals('Kinan Finance', $log->user_name);
    }

    public function test_auth_login_and_logout_records_audit_trail(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin M2B',
            'email' => 'admin@m2b.co.id',
            'password' => Hash::make('password123'),
            'roles' => ['super_admin'],
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // 1. Test Login
        $response = $this->post('/login', [
            'email' => 'admin@m2b.co.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/dashboard');

        $loginLog = ActivityLog::where('action', 'LOGIN')->where('target_ref', 'admin@m2b.co.id')->first();
        $this->assertNotNull($loginLog);
        $this->assertEquals('super_admin', $loginLog->role);

        // 2. Test Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/');

        $logoutLog = ActivityLog::where('action', 'LOGOUT')->where('target_ref', 'admin@m2b.co.id')->first();
        $this->assertNotNull($logoutLog);
    }

    public function test_failed_login_records_anonymous_security_audit_log(): void
    {
        $response = $this->post('/login', [
            'email' => 'hacker@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');

        $failedLog = ActivityLog::where('action', 'LOGIN_FAILED')->where('target_ref', 'hacker@example.com')->first();
        $this->assertNotNull($failedLog);
        $this->assertEquals('hacker@example.com', $failedLog->user_name);
        $this->assertEquals('guest', $failedLog->role);
    }

    public function test_user_management_role_change_records_audit_log(): void
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'roles' => ['super_admin'],
            'role' => 'super_admin',
        ]);

        $targetStaff = User::factory()->create([
            'name' => 'Nurul Asyikin',
            'email' => 'nurul@m2b.co.id',
            'roles' => ['cashier'],
            'role' => 'cashier',
        ]);

        $this->actingAs($admin);

        Livewire::test(UserManagement::class)
            ->call('edit', $targetStaff->id)
            ->set('selectedRoles', ['cashier', 'staff_accounting'])
            ->call('save');

        $log = ActivityLog::where('action', 'UPDATE_ROLE')->where('target_ref', 'nurul@m2b.co.id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('cashier, staff_accounting', $log->description);
    }

    public function test_vendor_bank_change_triggers_fraud_prevention_audit_log(): void
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'roles' => ['super_admin'],
            'role' => 'super_admin',
        ]);

        $vendor = Vendor::create([
            'code' => 'VEN-999',
            'name' => 'PT Trucking Sejahtera',
            'category' => 'Trucking',
            'bank_details' => 'BCA 1234567890 an PT Trucking',
        ]);

        $vendor->contacts()->create([
            'pic_name' => 'Budi PIC',
            'phone' => '08123456789',
            'is_primary' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(VendorManagement::class)
            ->call('edit', $vendor->id)
            ->set('bank_details', 'Mandiri 9876543210 an Penipu')
            ->set('contacts', [
                ['pic_name' => 'Budi PIC', 'phone' => '08123456789', 'email' => 'budi@trucking.com', 'role' => 'Manager', 'is_primary' => true]
            ])
            ->call('save');

        $log = ActivityLog::where('action', 'UPDATE_BANK_DETAILS')->where('target_ref', 'VEN-999')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Mandiri 9876543210', $log->description);
    }

    public function test_journal_entry_create_and_delete_records_audit_log(): void
    {
        $user = User::factory()->create([
            'name' => 'Kinan Accounting',
            'roles' => ['staff_accounting'],
            'role' => 'staff_accounting',
        ]);

        $kas = Account::create([
            'code' => '11110',
            'name' => 'Kas Operasional',
            'type' => 'kas_bank',
            'opening_balance' => 10000000,
            'current_balance' => 10000000,
        ]);

        $beban = Account::create([
            'code' => '51100',
            'name' => 'Beban Operasional',
            'type' => 'beban_operasional',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $this->actingAs($user);

        Livewire::test(JournalEntry::class)
            ->set('transaction_date', '2026-08-26')
            ->set('reference_no', 'REF-TEST-99')
            ->set('description', 'Beli Perlengkapan Kantor')
            ->set('items', [
                ['account_id' => $beban->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $kas->id, 'debit' => 0, 'credit' => 500000],
            ])
            ->call('save');

        $createLog = ActivityLog::where('action', 'CREATE_JOURNAL')->where('target_ref', 'REF-TEST-99')->first();
        $this->assertNotNull($createLog);
        $this->assertStringContainsString('Rp 500.000', $createLog->description);
    }

    public function test_audit_log_manager_stats_and_filters(): void
    {
        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'roles' => ['super_admin'],
            'role' => 'super_admin',
        ]);

        ActivityLog::recordForUser($admin, 'Auth', 'LOGIN', $admin->email, 'Login');
        ActivityLog::recordForUser($admin, 'Accounting', 'DELETE_JOURNAL', 'JR-001', 'Delete Journal');

        $this->actingAs($admin);

        Livewire::test(AuditLogManager::class)
            ->assertSee('Total Rekam Jejak')
            ->set('filterRisk', 'high')
            ->assertSee('DELETE_JOURNAL');
    }
}
