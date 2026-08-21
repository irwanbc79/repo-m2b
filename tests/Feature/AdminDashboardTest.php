<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Support\NumberHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dashboard admin sebelumnya tidak punya tes sama sekali: halaman ini memakai
 * MONTH() yang tidak ada di SQLite sehingga selalu 500 saat diuji. Setelah
 * ekspresinya dibuat lintas driver, halaman yang paling sering dibuka akhirnya
 * bisa dijaga.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = Customer::create([
            'company_name' => 'PT. UJI COBA',
            'customer_code' => 'CUST-000099',
        ]);
    }

    private function buatShipment(array $atribut = []): Shipment
    {
        // created_at bukan kolom fillable di Shipment, jadi tanggalnya harus
        // dipasang setelah baris dibuat — kalau tidak, semuanya jadi hari ini.
        $dibuatPada = $atribut['created_at'] ?? null;
        unset($atribut['created_at']);

        $shipment = Shipment::create(array_merge([
            'awb_number' => 'IMP-' . fake()->unique()->numerify('######'),
            'customer_id' => $this->customer->id,
            'service_type' => 'import',
            'shipment_type' => 'sea',
            'status' => 'in_progress',
            'origin' => 'Shanghai',
            'destination' => 'Belawan',
        ], $atribut));

        if ($dibuatPada) {
            $shipment->forceFill(['created_at' => $dibuatPada])->save();
        }

        return $shipment;
    }

    public function test_halaman_dashboard_terbuka(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    }

    /** Kartu "shipment baru" harus mengikuti periode, bukan sepanjang waktu. */
    public function test_kartu_utama_mengikuti_periode_yang_dipilih(): void
    {
        $this->buatShipment(['created_at' => now()->subMonths(4)]);
        $this->buatShipment(['created_at' => now()]);
        $this->buatShipment(['created_at' => now()]);

        $stats = Livewire::actingAs($this->admin)->test(Dashboard::class)
            ->set('period', 'month')
            ->viewData('mainStats');

        $this->assertSame(2, $stats['current_shipments'], 'Hanya shipment bulan ini yang dihitung');
        $this->assertSame(3, $stats['total_shipments'], 'Sepanjang waktu tetap tersedia sebagai konteks');
    }

    /**
     * Hitungan pelanggan baru dulu tidak punya batas atas, sehingga rentang
     * lampau ikut menghitung pelanggan yang mendaftar sesudahnya.
     */
    public function test_pelanggan_baru_tidak_bocor_keluar_periode(): void
    {
        Customer::create(['company_name' => 'PT. LAMA', 'customer_code' => 'C-1', 'created_at' => now()->subMonths(3)]);
        Customer::create(['company_name' => 'PT. BARU', 'customer_code' => 'C-2', 'created_at' => now()]);

        $stats = Livewire::actingAs($this->admin)->test(Dashboard::class)
            ->set('period', 'custom')
            ->set('startDate', now()->subMonths(3)->startOfMonth()->format('Y-m-d'))
            ->set('endDate', now()->subMonths(3)->endOfMonth()->format('Y-m-d'))
            ->viewData('mainStats');

        $this->assertSame(1, $stats['new_customers'], 'Pelanggan sesudah rentang tidak boleh ikut terhitung');
    }

    /** Tanpa pembanding, badge pertumbuhan harus kosong — bukan "0%". */
    public function test_pertumbuhan_null_saat_periode_pembanding_kosong(): void
    {
        Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'grand_total' => 5_000_000,
            'status' => 'unpaid',
        ]);

        $stats = Livewire::actingAs($this->admin)->test(Dashboard::class)
            ->set('period', 'month')
            ->viewData('mainStats');

        $this->assertNull($stats['revenue_growth'], 'Pembanding nol berarti tidak ada pertumbuhan yang bisa dihitung');
    }

    public function test_perlu_tindakan_menangkap_eta_yang_terlewat(): void
    {
        $this->buatShipment(['status' => 'in_transit', 'estimated_arrival' => now()->subDays(5)]);
        // Yang sudah selesai tidak boleh ikut ditagih tindakan.
        $this->buatShipment(['status' => 'completed', 'estimated_arrival' => now()->subDays(9)]);

        $items = Livewire::actingAs($this->admin)->test(Dashboard::class)->viewData('actionItems');
        $eta = collect($items)->firstWhere('title', 'Shipment lewat estimasi');

        $this->assertNotNull($eta, 'ETA terlewat harus muncul di daftar tindakan');
        $this->assertSame(1, $eta['count']);
        $this->assertSame('danger', $eta['level']);
    }

    public function test_pipeline_hanya_menghitung_yang_belum_selesai(): void
    {
        $this->buatShipment(['status' => 'pending']);
        $this->buatShipment(['status' => 'in_transit']);
        $this->buatShipment(['status' => 'completed']);

        $pipeline = Livewire::actingAs($this->admin)->test(Dashboard::class)->viewData('pipeline');

        $this->assertSame(2, $pipeline['total']);
        $this->assertSame(1, $pipeline['completed']);
        $this->assertSame(['Menunggu', 'Dokumen', 'Proses', 'Perjalanan'], array_column($pipeline['stages'], 'label'));
    }

    /** Grafik berhenti di bulan berjalan, bukan menggambar bulan yang belum datang. */
    public function test_grafik_bulanan_berhenti_di_bulan_ini(): void
    {
        $data = Livewire::actingAs($this->admin)->test(Dashboard::class)->viewData('chartData');

        $this->assertCount(now()->month, $data['labels']);
        $this->assertCount(now()->month, $data['revenue']);
    }

    /**
     * "M" pernah berarti juta di satu kartu dan miliar di kartu sebelahnya —
     * pada layar yang sama, selisihnya seribu kali.
     */
    public function test_uang_memakai_satuan_indonesia(): void
    {
        $this->assertSame('Rp 2.04jt', NumberHelper::formatCurrencyCompact(2_040_000));
        $this->assertSame('Rp 834jt', NumberHelper::formatCurrencyCompact(834_000_000));
        $this->assertSame('Rp 1.3M', NumberHelper::formatCurrencyCompact(1_300_000_000));
        $this->assertSame('Rp 15rb', NumberHelper::formatCurrencyCompact(15_000));
    }
}
