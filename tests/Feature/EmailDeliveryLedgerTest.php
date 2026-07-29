<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailDeliveryLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $company = 'PT Yossava'): Customer
    {
        $user = User::create([
            'name'     => 'Kontak ' . $company,
            'email'    => 'kontak-' . uniqid() . '@yossava.co.id',
            'password' => bcrypt('rahasia123'),
            'role'     => 'customer',
        ]);

        return Customer::create([
            'user_id'       => $user->id,
            'customer_code' => Customer::generateCustomerCode(),
            'company_name'  => $company,
        ]);
    }

    public function test_email_biasa_tercatat_tanpa_menyentuh_kode_pengirim(): void
    {
        Mail::raw('Isi pesan uji', function ($message) {
            $message->to('finance@yossava.co.id')->subject('Tagihan Juli');
        });

        $row = EmailDelivery::sole();

        $this->assertSame('finance@yossava.co.id', $row->recipient_email);
        $this->assertSame('Tagihan Juli', $row->subject);
        $this->assertSame(EmailDelivery::STATUS_QUEUED, $row->status);
        $this->assertNotNull($row->sent_at);
        $this->assertNull($row->provider_message_guid);
    }

    public function test_setiap_penerima_dapat_barisnya_sendiri(): void
    {
        // Peristiwa dari Kirim Email datang per penerima, jadi barisnya pun
        // harus per penerima supaya pencocokan nanti lurus.
        Mail::raw('Pengumuman', function ($message) {
            $message->to(['satu@m2b.co.id', 'dua@m2b.co.id'])->subject('Pengumuman');
        });

        $this->assertSame(2, EmailDelivery::count());
        $this->assertEqualsCanonicalizing(
            ['satu@m2b.co.id', 'dua@m2b.co.id'],
            EmailDelivery::pluck('recipient_email')->all()
        );
    }

    public function test_nama_class_mailable_ikut_tercatat(): void
    {
        Mail::to('ops@asiagrow.co.id')->send(new LedgerProbeMail());

        $this->assertSame(LedgerProbeMail::class, EmailDelivery::sole()->mailable_class);
    }

    public function test_email_tertaut_ke_entitas_bisnis_lewat_properti_publik_mailable(): void
    {
        // Inti fase 01: model yang jadi properti publik Mailable ikut terbawa
        // ke data event, jadi tautannya terbentuk tanpa mengubah kode pengirim.
        $customer = $this->customer();

        Mail::to('finance@yossava.co.id')->send(new LedgerProbeMail($customer));

        $row = EmailDelivery::sole();

        $this->assertSame(Customer::class, $row->related_type);
        $this->assertSame($customer->id, $row->related_id);
        $this->assertTrue($row->related->is($customer));
    }

    public function test_pengiriman_berbasis_view_juga_tertaut(): void
    {
        // Jalur INI yang dipakai pengiriman invoice sungguhan di portal
        // (InvoiceManager memakai Mail::send('emails.invoice-notification',
        // ['invoice' => $invoice, ...])), bukan Mailable. Datanya tetap
        // membawa model, jadi tautannya harus tetap terbentuk.
        $customer = $this->customer('PT Asia Grow');

        Mail::send([], ['customer' => $customer], function ($message) {
            $message->to('ops@asiagrow.co.id')
                ->subject('Tagihan via jalur view')
                ->html('<p>isi</p>');
        });

        $row = EmailDelivery::sole();

        $this->assertSame(Customer::class, $row->related_type);
        $this->assertSame($customer->id, $row->related_id);
        // Mailable tidak dipakai di jalur ini, jadi kolomnya memang kosong.
        $this->assertNull($row->mailable_class);
    }

    public function test_tanpa_entitas_yang_dikenali_tautan_dibiarkan_kosong(): void
    {
        Mail::to('umum@m2b.co.id')->send(new LedgerProbeMail());

        $row = EmailDelivery::sole();

        $this->assertNull($row->related_type);
        $this->assertNull($row->related_id);
    }

    public function test_kegagalan_pencatatan_tidak_boleh_menggagalkan_pengiriman(): void
    {
        // Jaring pengaman terpenting: listener berjalan di SETIAP pengiriman.
        // Bila tabelnya hilang sekalipun, email tetap harus terkirim.
        Schema::drop('email_deliveries');

        Mail::raw('Tetap harus terkirim', function ($message) {
            $message->to('penting@m2b.co.id')->subject('Jangan sampai gagal');
        });

        // Sampai di sini tanpa exception = lulus.
        $this->assertTrue(true);
    }

    public function test_status_tidak_pernah_mundur(): void
    {
        $row = new EmailDelivery(['status' => EmailDelivery::STATUS_OPENED]);

        $this->assertFalse(
            $row->canAdvanceTo(EmailDelivery::STATUS_DELIVERED),
            'peristiwa delivered yang telat datang tidak boleh menurunkan status'
        );
        $this->assertTrue($row->canAdvanceTo(EmailDelivery::STATUS_CLICKED));
        $this->assertTrue($row->canAdvanceTo(EmailDelivery::STATUS_BOUNCED));
    }
}

/**
 * Mailable minimal untuk menguji listener. Properti publik sengaja dipakai
 * karena justru itulah mekanisme yang diandalkan untuk menautkan entitas.
 */
class LedgerProbeMail extends Mailable
{
    public $customer;

    public function __construct($customer = null)
    {
        $this->customer = $customer;
    }

    public function build()
    {
        return $this->subject('Uji buku besar')->html('<p>Uji buku besar</p>');
    }
}
