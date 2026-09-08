<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $finance;
    private User $accounting;
    private User $cashier;
    private User $sales;
    private User $operations;
    private User $staff;
    private User $auditor;
    private PayrollPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'roles' => ['super_admin'],
        ]);

        $this->finance = User::factory()->create([
            'role' => 'finance',
            'roles' => ['finance'],
        ]);

        $this->accounting = User::factory()->create([
            'role' => 'staff_accounting',
            'roles' => ['staff_accounting'],
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'roles' => ['cashier'],
        ]);

        $this->sales = User::factory()->create([
            'role' => 'staff_sales',
            'roles' => ['staff_sales'],
        ]);

        $this->operations = User::factory()->create([
            'role' => 'staff_operations',
            'roles' => ['staff_operations'],
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'roles' => ['staff'],
        ]);

        $this->auditor = User::factory()->create([
            'role' => 'auditor',
            'roles' => ['auditor'],
        ]);

        $this->period = PayrollPeriod::create([
            'bulan' => 8,
            'tahun' => 2026,
            'status' => 'draft',
        ]);
    }

    /**
     * Uji Otorisasi Modul Penggajian (HRD & Payroll)
     */
    public function test_staf_sales_dan_operasional_dan_kasir_dilarang_akses_penggajian(): void
    {
        $unauthorizedUsers = [$this->sales, $this->operations, $this->cashier, $this->staff, $this->accounting];

        foreach ($unauthorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('admin.hrd.payroll-periods'));
            $this->assertEquals(403, $response->status(), "User {$user->role} seharusnya ditolak (403) di payroll-periods");

            $responseSlip = $this->actingAs($user)->get(route('admin.hrd.payroll-slips', ['periodId' => $this->period->id]));
            $this->assertEquals(403, $responseSlip->status(), "User {$user->role} seharusnya ditolak (403) di payroll-slips");
        }
    }

    public function test_finance_dan_super_admin_boleh_akses_penggajian(): void
    {
        $authorizedUsers = [$this->superAdmin, $this->finance];

        foreach ($authorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('admin.hrd.payroll-periods'));
            $response->assertOk();

            $responseSlip = $this->actingAs($user)->get(route('admin.hrd.payroll-slips', ['periodId' => $this->period->id]));
            $responseSlip->assertOk();
        }
    }

    /**
     * Uji Otorisasi Modul User Management
     */
    public function test_staf_biasa_dan_kasir_dilarang_akses_user_management(): void
    {
        $unauthorizedUsers = [$this->sales, $this->operations, $this->cashier, $this->staff, $this->accounting];

        foreach ($unauthorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('admin.users.index'));
            $this->assertEquals(403, $response->status(), "User {$user->role} seharusnya ditolak (403) di users.index");
        }
    }

    public function test_super_admin_boleh_akses_user_management(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.users.index'));
        $response->assertOk();
    }

    /**
     * Uji Otorisasi Modul Akuntansi & Jurnal Umum (Segregation of Duties)
     */
    public function test_kasir_dan_sales_dilarang_akses_jurnal_umum(): void
    {
        $unauthorizedUsers = [$this->cashier, $this->sales, $this->operations, $this->staff];

        foreach ($unauthorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('accounting.journal'));
            $this->assertEquals(403, $response->status(), "User {$user->role} seharusnya ditolak (403) di accounting.journal");
        }
    }

    public function test_accounting_dan_super_admin_boleh_akses_jurnal_umum(): void
    {
        $authorizedUsers = [$this->superAdmin, $this->accounting];

        foreach ($authorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('accounting.journal'));
            $response->assertOk();
        }
    }

    /**
     * Uji Otorisasi Laporan Keuangan & Buku Besar
     */
    public function test_kasir_dilarang_akses_buku_besar_dan_laba_rugi(): void
    {
        $responseLedger = $this->actingAs($this->cashier)->get(route('accounting.ledger'));
        $this->assertEquals(403, $responseLedger->status(), "Kasir seharusnya ditolak (403) di accounting.ledger");

        $responseProfitLoss = $this->actingAs($this->cashier)->get(route('accounting.profit_loss'));
        $this->assertEquals(403, $responseProfitLoss->status(), "Kasir seharusnya ditolak (403) di accounting.profit_loss");
    }

    /**
     * Uji Otorisasi Modul Job Costing
     */
    public function test_kasir_dan_sales_dilarang_akses_job_costing(): void
    {
        $unauthorizedUsers = [$this->cashier, $this->sales, $this->staff];

        foreach ($unauthorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('admin.job-costing.index'));
            $this->assertEquals(403, $response->status(), "User {$user->role} seharusnya ditolak (403) di job-costing.index");
        }
    }

    /**
     * Uji Otorisasi Modul Bagan Akun (Chart of Accounts)
     */
    public function test_kasir_dan_sales_dilarang_akses_coa(): void
    {
        $unauthorizedUsers = [$this->cashier, $this->sales, $this->operations, $this->staff];

        foreach ($unauthorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('accounting.coa'));
            $this->assertEquals(403, $response->status(), "User {$user->role} seharusnya ditolak (403) di accounting.coa");
        }
    }

    public function test_auditor_dan_accounting_boleh_akses_coa(): void
    {
        $authorizedUsers = [$this->superAdmin, $this->accounting, $this->auditor];

        foreach ($authorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('accounting.coa'));
            $response->assertOk();
        }
    }

    /**
     * Uji Otorisasi Kasir & Kas Kecil
     */
    public function test_sales_dan_ops_dilarang_akses_kasir_dan_kas_kecil(): void
    {
        $unauthorizedUsers = [$this->sales, $this->operations];

        foreach ($unauthorizedUsers as $user) {
            $responseCashier = $this->actingAs($user)->get(route('simple-cashier'));
            $this->assertEquals(403, $responseCashier->status(), "User {$user->role} seharusnya ditolak (403) di simple-cashier");

            $responsePetty = $this->actingAs($user)->get(route('admin.petty-cash'));
            $this->assertEquals(403, $responsePetty->status(), "User {$user->role} seharusnya ditolak (403) di admin.petty-cash");
        }

        // Staf umum dilarang akses loket kasir POS
        $responseCashierStaff = $this->actingAs($this->staff)->get(route('simple-cashier'));
        $this->assertEquals(403, $responseCashierStaff->status(), "User staff seharusnya ditolak (403) di simple-cashier");
    }

    /**
     * Uji Otorisasi Profit Report
     */
    public function test_sales_dan_kasir_dilarang_akses_profit_report(): void
    {
        $unauthorizedUsers = [$this->sales, $this->cashier, $this->staff];

        foreach ($unauthorizedUsers as $user) {
            $response = $this->actingAs($user)->get(route('admin.profit-report'));
            $this->assertEquals(403, $response->status(), "User {$user->role} seharusnya ditolak (403) di profit-report");
        }
    }

    /**
     * Uji Pembersihan Route Debugging
     */
    public function test_route_debug_test_inbox_sudah_dihapus(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/test-inbox');
        $response->assertNotFound();
    }
}
