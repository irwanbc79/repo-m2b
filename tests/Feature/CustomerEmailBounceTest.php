<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\User;
use App\Services\EmailDeliveryTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerEmailBounceTest extends TestCase
{
    use RefreshDatabase;

    private EmailDeliveryTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = app(EmailDeliveryTracker::class);
    }

    private function customer(string $email = 'finance@yossava.co.id'): Customer
    {
        $user = User::factory()->create(['email' => $email, 'role' => 'customer']);

        return Customer::create([
            'user_id'       => $user->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name'  => 'PT Yossava',
            // Data lain sengaja dilengkapi supaya skornya tinggi — dengan
            // begitu penurunan yang diuji benar-benar berasal dari email
            // mental, bukan dari kolom yang kebetulan kosong.
            'npwp'          => '123456789012345',
            'phone'         => '081234567890',
            'address'       => 'Jl. Pelabuhan No. 1',
            'city'          => 'Surabaya',
            'trade_type'    => 'import',
            'position'      => 'Manager',
        ]);
    }

    private function kirim(string $email): EmailDelivery
    {
        return EmailDelivery::create([
            'recipient_email' => $email,
            'subject'         => 'Invoice INV/2607/0012',
            'sent_at'         => now()->subMinutes(5),
            'status'          => EmailDelivery::STATUS_QUEUED,
            'mailer'          => 'kirimemail',
        ]);
    }

    private function peristiwa(string $email, string $tipe, ?string $detail = null): array
    {
        return [
            'provider_event_id' => 'evt-' . uniqid(),
            'message_guid'      => 'guid-' . uniqid(),
            'event_type'        => $tipe,
            'recipient'         => $email,
            'subject'           => 'Invoice INV/2607/0012',
            'occurred_at'       => now()->timestamp,
            'detail'            => $detail,
        ];
    }

    public function test_email_mental_menandai_customer(): void
    {
        $customer = $this->customer();
        $this->kirim('finance@yossava.co.id');

        $this->tracker->record($this->peristiwa(
            'finance@yossava.co.id',
            EmailDelivery::STATUS_BOUNCED,
            '550 5.1.1 alamat tidak ditemukan'
        ));

        $customer->refresh();

        $this->assertNotNull($customer->email_bounced_at);
        $this->assertStringContainsString('550', $customer->email_bounce_reason);
    }

    public function test_penanda_menjatuhkan_kualitas_data(): void
    {
        $customer = $this->customer();

        // Sebelum: data lengkap, kualitasnya baik.
        $sebelum = $customer->dataQuality();
        $this->assertSame('good', $sebelum['level']);

        $this->kirim('finance@yossava.co.id');
        $this->tracker->record($this->peristiwa('finance@yossava.co.id', EmailDelivery::STATUS_BOUNCED, '550'));

        $sesudah = $customer->fresh()->dataQuality();

        $this->assertSame('bad', $sesudah['level']);
        $this->assertLessThan($sebelum['score'], $sesudah['score']);
        $this->assertContains('Email mental — alamat tidak dapat dijangkau', $sesudah['issues']);
    }

    public function test_customer_bermasalah_ikut_tersaring(): void
    {
        $this->customer();

        // Data lengkap → belum masuk daftar perlu perhatian.
        $this->assertSame(0, Customer::needsAttention()->count());

        $this->kirim('finance@yossava.co.id');
        $this->tracker->record($this->peristiwa('finance@yossava.co.id', EmailDelivery::STATUS_BOUNCED, '550'));

        $this->assertSame(1, Customer::needsAttention()->count());
    }

    public function test_penanda_sembuh_sendiri_saat_email_berhasil_sampai(): void
    {
        // Inti perilakunya: begitu staf memperbaiki alamat dan email berikutnya
        // sampai, penandanya hilang sendiri — tanpa perlu dibersihkan manual.
        $customer = $this->customer();

        $this->kirim('finance@yossava.co.id');
        $this->tracker->record($this->peristiwa('finance@yossava.co.id', EmailDelivery::STATUS_BOUNCED, '550'));
        $this->assertNotNull($customer->fresh()->email_bounced_at);

        $this->kirim('finance@yossava.co.id');
        $this->tracker->record($this->peristiwa('finance@yossava.co.id', EmailDelivery::STATUS_DELIVERED, '250 OK'));

        $customer->refresh();

        $this->assertNull($customer->email_bounced_at);
        $this->assertNull($customer->email_bounce_reason);
        $this->assertSame('good', $customer->dataQuality()['level']);
    }

    public function test_customer_lain_tidak_ikut_tertandai(): void
    {
        $yossava = $this->customer('finance@yossava.co.id');

        $userLain = User::factory()->create(['email' => 'ops@asiagrow.co.id', 'role' => 'customer']);
        $asiaGrow = Customer::create([
            'user_id'       => $userLain->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name'  => 'PT Asia Grow',
        ]);

        $this->kirim('finance@yossava.co.id');
        $this->tracker->record($this->peristiwa('finance@yossava.co.id', EmailDelivery::STATUS_BOUNCED, '550'));

        $this->assertNotNull($yossava->fresh()->email_bounced_at);
        $this->assertNull($asiaGrow->fresh()->email_bounced_at);
    }

    public function test_email_mental_ke_alamat_bukan_customer_tidak_meledak(): void
    {
        // Alert internal ke finance@m2b.co.id juga bisa mental; tidak ada
        // customer yang cocok, dan itu tidak boleh menggagalkan pencatatan.
        $this->kirim('finance@m2b.co.id');

        $this->tracker->record($this->peristiwa('finance@m2b.co.id', EmailDelivery::STATUS_BOUNCED, '550'));

        $this->assertSame(
            EmailDelivery::STATUS_BOUNCED,
            EmailDelivery::where('recipient_email', 'finance@m2b.co.id')->first()->status
        );
    }
}
