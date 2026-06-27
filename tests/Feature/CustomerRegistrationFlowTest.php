<?php

namespace Tests\Feature;

use App\Livewire\Admin\CustomerManagement;
use App\Livewire\Admin\MoraLeadManager;
use App\Mail\LeadFollowupMail;
use App\Models\Customer;
use App\Models\MoraLeadNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    /** Lead tanpa HP valid: hasValidPhone() false; dengan HP: true. */
    public function test_lead_has_valid_phone_helper(): void
    {
        $noPhone = new MoraLeadNotification(['phone' => '-']);
        $withPhone = new MoraLeadNotification(['phone' => '081234567890']);

        $this->assertFalse($noPhone->hasValidPhone());
        $this->assertTrue($withPhone->hasValidPhone());
    }

    /** Follow-up via email mengirim mail + menandai lead 'contacted'. */
    public function test_send_followup_email_for_phoneless_lead(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = MoraLeadNotification::create([
            'name' => 'Clara Oke',
            'phone' => '-',
            'email' => 'clara@example.com',
            'source' => 'portal_signup',
            'score' => 'cold',
            'status' => 'new',
        ]);

        Livewire::actingAs($admin)
            ->test(MoraLeadManager::class)
            ->call('selectLead', $lead->id)
            ->call('sendFollowupEmail');

        Mail::assertSent(LeadFollowupMail::class, fn ($m) => $m->hasTo('clara@example.com'));
        $this->assertEquals('contacted', $lead->fresh()->status);
    }

    /** Template 'portal' tersedia untuk sambutan signup portal. */
    public function test_portal_signup_wa_template_exists(): void
    {
        $templates = MoraLeadNotification::getWaTemplates('Clara');
        $this->assertArrayHasKey('portal', $templates);
        $this->assertStringContainsString('mendaftar di Portal M2B', $templates['portal']);
    }

    /** Heuristik nama: placeholder/iseng terdeteksi, nama sah lolos. */
    public function test_suspect_company_name_detection(): void
    {
        $this->assertTrue((new Customer(['company_name' => 'Doyyan xD']))->hasSuspectName());
        $this->assertTrue((new Customer(['company_name' => 'test']))->hasSuspectName());
        $this->assertTrue((new Customer(['company_name' => 'PT']))->hasSuspectName());
        $this->assertTrue((new Customer(['company_name' => 'CV Maju 🚀']))->hasSuspectName());

        $this->assertFalse((new Customer(['company_name' => 'PT Funda Konstruksi Engineering']))->hasSuspectName());
        $this->assertFalse((new Customer(['company_name' => 'Koperasi Tani Sejahtera']))->hasSuspectName());
    }

    /** Skor kualitas: profil lengkap mendekati 100, profil minim jatuh rendah. */
    public function test_data_quality_score(): void
    {
        $lengkap = new Customer([
            'company_name' => 'PT Maju Jaya',
            'npwp' => '123456789012345',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
            'city' => 'Jakarta',
            'trade_type' => 'import',
            'position' => 'Direktur',
            'business_type' => 'VIP',
        ]);
        $this->assertEquals('good', $lengkap->dataQuality()['level']);
        $this->assertGreaterThanOrEqual(75, $lengkap->dataQuality()['score']);

        $minim = new Customer(['company_name' => 'Doyyan xD']);
        $this->assertEquals('bad', $minim->dataQuality()['level']);
        $this->assertNotEmpty($minim->dataQuality()['issues']);
    }

    /** Customer bisa melengkapi data perusahaan sendiri -> skor naik jadi 'good'. */
    public function test_customer_can_complete_own_company_data(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'Doyyan xD',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\Profile::class)
            ->set('company_name', 'PT Doyyan Sejahtera')
            ->set('npwp', '123456789012345')
            ->set('phone', '081234567890')
            ->set('address', 'Jl. Merdeka No. 1, Jakarta Pusat')
            ->set('city', 'Jakarta')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $fresh = $customer->fresh();
        $this->assertEquals('PT Doyyan Sejahtera', $fresh->company_name);
        $this->assertEquals('good', $fresh->dataQuality()['level']);
    }

    /** Nama perusahaan asal isi ditolak saat customer simpan profil. */
    public function test_customer_profile_rejects_suspect_company_name(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        Customer::create([
            'user_id' => $user->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Lama Valid',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Customer\Profile::class)
            ->set('company_name', 'Doyyan xD')
            ->call('updateProfile')
            ->assertHasErrors('company_name');
    }

    /** Scope needsAttention + filter Livewire hanya menampilkan data bermasalah. */
    public function test_needs_attention_filter_excludes_complete_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $bad = User::factory()->create(['role' => 'customer']);
        Customer::create([
            'user_id' => $bad->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'Doyyan xD', // tanpa npwp/phone/address
        ]);

        $good = User::factory()->create(['role' => 'customer']);
        Customer::create([
            'user_id' => $good->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name' => 'PT Lengkap Sentosa',
            'npwp' => '123456789012345',
            'phone' => '081234567890',
            'address' => 'Jl. Lengkap No. 9',
            'city' => 'Surabaya',
        ]);

        $this->assertEquals(1, Customer::needsAttention()->count());

        Livewire::actingAs($admin)
            ->test(CustomerManagement::class)
            ->set('filterQuality', 'attention')
            ->assertSee('Doyyan xD')
            ->assertDontSee('PT Lengkap Sentosa');
    }
}
