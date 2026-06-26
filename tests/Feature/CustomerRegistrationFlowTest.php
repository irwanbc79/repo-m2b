<?php

namespace Tests\Feature;

use App\Livewire\Admin\CustomerManagement;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Form lengkapi data Google butuh session pendaftaran. */
    public function test_complete_profile_page_requires_google_session(): void
    {
        $this->get(route('register.complete'))
            ->assertRedirect(route('login'));
    }

    /** Data valid -> buat akun PENDING + baris Customer dengan field kualifikasi. */
    public function test_google_complete_profile_creates_pending_customer_with_qualification(): void
    {
        $session = [
            'google_register' => [
                'google_id' => '1234567890',
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'google_token' => 'tok',
                'google_refresh_token' => null,
            ],
        ];

        $this->withSession($session)
            ->post(route('register.complete.store'), [
                'company_name' => 'PT Maju Jaya',
                'position' => 'Direktur',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 1, Jakarta Pusat',
                'city' => 'Jakarta',
                'npwp' => '123456789012345',
                'trade_type' => 'import',
                'trade_plan' => 'Impor mesin tekstil dari China rutin tiap bulan.',
            ])
            ->assertRedirect(route('login'));

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_active, 'Akun harus pending (nonaktif).');
        $this->assertEquals('customer', $user->role);

        $customer = Customer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer, 'Baris Customer wajib ada.');
        $this->assertEquals('import', $customer->trade_type);
        $this->assertEquals('Direktur', $customer->position);
        $this->assertNotEmpty($customer->trade_plan);

        // User tidak ter-login otomatis
        $this->assertGuest();
    }

    /** Domestik adalah pilihan layanan yang valid. */
    public function test_domestic_trade_type_is_accepted(): void
    {
        $this->withSession(['google_register' => [
            'google_id' => '999',
            'name' => 'Citra',
            'email' => 'citra@example.com',
            'google_token' => 't',
            'google_refresh_token' => null,
        ]])->post(route('register.complete.store'), [
            'company_name' => 'CV Nusantara',
            'position' => 'Owner',
            'phone' => '081200001111',
            'address' => 'Jl. Pahlawan No. 10, Surabaya',
            'city' => 'Surabaya',
            'trade_type' => 'domestic',
            'trade_plan' => 'Kirim hasil bumi antar pulau secara reguler.',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('customers', ['trade_type' => 'domestic']);
    }

    /** Data tidak lengkap / tidak valid ditolak, akun tidak dibuat. */
    public function test_invalid_data_is_rejected_and_no_account_created(): void
    {
        $this->withSession(['google_register' => [
            'google_id' => '111',
            'name' => 'Spam',
            'email' => 'spam@example.com',
            'google_token' => 't',
            'google_refresh_token' => null,
        ]])->post(route('register.complete.store'), [
            'company_name' => '',
            'position' => '',
            'phone' => 'bukan-nomor',
            'address' => 'pendek',
            'city' => '',
            'trade_type' => 'xxx',
            'trade_plan' => '',
        ])->assertSessionHasErrors(['company_name', 'position', 'phone', 'address', 'city', 'trade_type', 'trade_plan']);

        $this->assertDatabaseMissing('users', ['email' => 'spam@example.com']);
    }

    /** Customer nonaktif (pending) diblokir middleware portal. */
    public function test_inactive_customer_is_blocked_from_customer_portal(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** Admin dapat menyetujui (mengaktifkan) customer pending. */
    public function test_admin_can_approve_pending_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pending = User::factory()->create(['role' => 'customer', 'is_active' => false]);
        $customer = Customer::create([
            'user_id' => $pending->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Tunggu Review',
        ]);

        Livewire::actingAs($admin)
            ->test(CustomerManagement::class)
            ->call('approveCustomer', $customer->id);

        $this->assertTrue((bool) $pending->fresh()->is_active);
    }

    /** Admin dapat mendorong customer ke MORA Leads (sekali, tanpa duplikat). */
    public function test_admin_can_convert_customer_to_mora_lead(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u = User::factory()->create(['role' => 'customer', 'name' => 'Tommy Hu', 'email' => 'tommy@example.com']);
        $customer = Customer::create([
            'user_id' => $u->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'Tommy Trading',
            'trade_type' => 'domestic',
        ]);

        $component = Livewire::actingAs($admin)->test(CustomerManagement::class);
        $component->call('convertToLead', $customer->id);

        $this->assertDatabaseHas('mora_lead_notifications', [
            'email' => 'tommy@example.com',
            'source' => 'portal_signup',
            'status' => 'new',
            'service_interest' => 'door_to_door',
        ]);

        // Panggilan kedua tidak boleh menduplikasi
        $component->call('convertToLead', $customer->id);
        $this->assertEquals(1, \App\Models\MoraLeadNotification::where('email', 'tommy@example.com')->count());
    }
}
