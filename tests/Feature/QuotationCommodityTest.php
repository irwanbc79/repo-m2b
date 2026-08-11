<?php

namespace Tests\Feature;

use App\Livewire\Admin\QuotationManager;
use App\Models\HsCode;
use App\Models\Quotation;
use App\Models\QuotationCommodity;
use App\Models\QuotationHsLog;
use App\Models\Shipment;
use App\Models\User;
use App\Services\HsCodeFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Komoditi & rekomendasi HS Code pada quotation.
 *
 * Titik beratnya bukan "apakah tersimpan", melainkan hal-hal yang diam-diam
 * salah dan baru ketahuan berbulan-bulan kemudian: bentuk kode yang tidak
 * seragam, uraian yang berubah saat BTKI diimpor ulang, jejak audit yang
 * bolong, dan konversi ke shipment yang membuang data tanpa suara.
 */
class QuotationCommodityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Bab & seksi induk wajib ada lebih dulu: hs_codes punya foreign key ke
     * keduanya di skema BTKI asli. Dibuat seperlunya supaya data uji sama
     * bentuknya dengan production, bukan bentuk longgar yang tidak pernah ada.
     */
    private function babBtki(string $kode): void
    {
        $bab = substr(HsCodeFormatter::digit($kode), 0, 2);

        $seksi = DB::table('hs_sections')->where('section_number', '11')->first()
            ?: DB::table('hs_sections')->insertGetId([
                'section_number' => '11', 'title_id' => 'Seksi Uji', 'title_en' => 'Test Section',
                'created_at' => now(), 'updated_at' => now(),
            ]);

        $seksiId = is_object($seksi) ? $seksi->id : $seksi;

        if (! DB::table('hs_chapters')->where('chapter_number', $bab)->exists()) {
            DB::table('hs_chapters')->insert([
                'chapter_number' => $bab, 'title_id' => 'Bab Uji', 'title_en' => 'Test Chapter',
                'section_id' => $seksiId, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function btki(string $kode, string $id, string $en): HsCode
    {
        $this->babBtki($kode);

        return HsCode::create([
            'hs_code'        => $kode,
            'hs_level'       => strlen(HsCodeFormatter::digit($kode)),
            'description_id' => $id,
            'description_en' => $en,
            // chapter_number & section_number NOT NULL di skema BTKI asli —
            // diisi supaya data uji sama bentuknya dengan production.
            'chapter_number' => substr(HsCodeFormatter::digit($kode), 0, 2),
            'section_number' => '11',
            'is_active'      => true,
        ]);
    }

    /** Isi form minimal yang sah, supaya test fokus ke komoditi saja. */
    private function form($komponen, array $commodities)
    {
        return $komponen
            ->set('is_new_customer', true)
            ->set('manual_company', 'PT Uji Coba')
            ->set('manual_pic', 'Ari')
            ->set('manual_email', 'ari@uji.test')
            ->set('quotation_date', now()->format('Y-m-d'))
            ->set('valid_until', now()->addDays(14)->format('Y-m-d'))
            ->set('origin', 'Shanghai')
            ->set('destination', 'Jakarta')
            ->set('items', [['item_type' => 'service', 'description' => 'Freight', 'qty' => 1, 'price' => 1000]])
            ->set('commodities', $commodities);
    }

    // ── Bentuk kode ────────────────────────────────────────────────────

    public function test_kode_diketik_tanpa_titik_dinormalkan_ke_bentuk_baku(): void
    {
        // Irwan menegaskan staf boleh mengetik tanpa titik. Yang tersimpan
        // tetap harus satu bentuk, sebab 60 shipment production memakai
        // bentuk bertitik dan nilainya disalin apa adanya saat konversi.
        $this->assertSame('6006.32.90', HsCodeFormatter::baku('60063290'));
        $this->assertSame('6006.32.90', HsCodeFormatter::baku('6006.32.90'));
        $this->assertSame('6006.32.90', HsCodeFormatter::baku('6006 32 90'));
        $this->assertSame('6006.32',    HsCodeFormatter::baku('600632'));
        $this->assertSame('6006',       HsCodeFormatter::baku('6006'));
    }

    public function test_panjang_selain_4_6_8_digit_ditolak(): void
    {
        foreach (['600', '60063', '6006329', '600632901'] as $salah) {
            $this->assertFalse(HsCodeFormatter::sah($salah), "{$salah} seharusnya tidak sah");
            $this->assertNull(HsCodeFormatter::baku($salah));
        }
    }

    // ── Simpan ─────────────────────────────────────────────────────────

    public function test_menyimpan_beberapa_komoditi_sekaligus(): void
    {
        $this->btki('6006.32.90', 'Kain rajutan lainnya', 'Other knitted fabrics');
        $this->btki('3924.90.90', 'Perkakas rumah tangga plastik', 'Plastic household goods');

        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric',        'hs_code' => '60063290'],
            ['commodity' => 'Plastic household goods', 'hs_code' => '3924.90.90'],
        ])->call('save')->assertHasNoErrors();

        $k = Quotation::first()->commodities;

        $this->assertCount(2, $k);
        $this->assertSame('6006.32.90', $k[0]->hs_code);
        $this->assertSame('3924.90.90', $k[1]->hs_code);
        $this->assertSame([0, 1], $k->pluck('sort_order')->all());
    }

    public function test_uraian_btki_disalin_bukan_diambil_lewat_relasi(): void
    {
        // Kalau uraian di-join saat cetak, memperbarui data BTKI akan diam-diam
        // mengubah bunyi quotation yang SUDAH disetujui pelanggan.
        $hs = $this->btki('6006.32.90', 'Kain rajutan lainnya', 'Other knitted fabrics');

        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
        ])->call('save');

        $hs->update(['description_en' => 'URAIAN SUDAH BERUBAH']);

        $k = Quotation::first()->commodities->first();

        $this->assertSame('Other knitted fabrics', $k->hs_description_en);
        $this->assertTrue($k->found_in_btki);
    }

    public function test_kode_tak_ada_di_btki_tetap_bisa_disimpan_tapi_ditandai(): void
    {
        // Keputusan Irwan: peringatkan, jangan blokir. Data BTKI bisa
        // tertinggal dari tarif baru; memblokir menghentikan staf saat benar.
        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Barang baru', 'hs_code' => '99999999'],
        ])->call('save')->assertHasNoErrors();

        $k = Quotation::first()->commodities->first();

        $this->assertSame('9999.99.99', $k->hs_code);
        $this->assertFalse($k->found_in_btki);
        $this->assertNull($k->hs_description_id);
    }

    public function test_format_salah_ditolak_dengan_pesan_jelas(): void
    {
        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Barang', 'hs_code' => '60063'],
        ])->call('save')->assertHasErrors('commodities.0.hs_code');

        $this->assertSame(0, QuotationCommodity::count());
    }

    public function test_hs_code_tanpa_nama_komoditi_ditolak(): void
    {
        // HS Code tanpa nama barang tidak berarti apa-apa di PDF pelanggan.
        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => '', 'hs_code' => '60063290'],
        ])->call('save')->assertHasErrors('commodities.0.commodity');
    }

    public function test_baris_kosong_dibuang_diam_diam(): void
    {
        // Staf sering menekan "+ Tambah Komoditi" lalu berubah pikiran; baris
        // itu tidak boleh muncul sebagai komoditi kosong di PDF.
        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
            ['commodity' => '',               'hs_code' => ''],
        ])->call('save')->assertHasNoErrors();

        $this->assertCount(1, Quotation::first()->commodities);
    }

    public function test_komoditi_boleh_tanpa_hs_code(): void
    {
        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Barang belum diklasifikasi', 'hs_code' => ''],
        ])->call('save')->assertHasNoErrors();

        $k = Quotation::first()->commodities->first();

        $this->assertSame('Barang belum diklasifikasi', $k->commodity);
        $this->assertNull($k->hs_code);
    }

    // ── Jejak audit ────────────────────────────────────────────────────

    public function test_perubahan_hs_code_terekam_lengkap_dengan_nilai_lama(): void
    {
        $admin = $this->admin();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
        ])->call('save');

        $q = Quotation::first();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class)->call('edit', $q->id), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063210'],
        ])->call('save');

        $ubah = QuotationHsLog::where('action', 'diubah')->first();

        $this->assertNotNull($ubah, 'perubahan HS Code harus terekam');
        $this->assertSame('6006.32.90', $ubah->hs_code_lama);
        $this->assertSame('6006.32.10', $ubah->hs_code_baru);
        $this->assertSame($admin->name, $ubah->user_name);
    }

    public function test_penghapusan_komoditi_ikut_terekam(): void
    {
        $admin = $this->admin();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
            ['commodity' => 'Plastic goods',  'hs_code' => '39249090'],
        ])->call('save');

        $q = Quotation::first();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class)->call('edit', $q->id), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
        ])->call('save');

        $hapus = QuotationHsLog::where('action', 'dihapus')->first();

        $this->assertNotNull($hapus, 'penghapusan justru yang paling perlu terekam');
        $this->assertSame('Plastic goods', $hapus->commodity_lama);
        $this->assertSame('3924.90.90', $hapus->hs_code_lama);
    }

    public function test_nama_pelaku_disalin_bukan_hanya_id(): void
    {
        // Pengguna bisa dihapus; jejak audit harus tetap terbaca.
        $admin = $this->admin();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
        ])->call('save');

        $nama = $admin->name;
        $admin->delete();

        $this->assertSame($nama, QuotationHsLog::first()->user_name);
    }

    // ── Copy ke Shipment ───────────────────────────────────────────────

    public function test_konversi_menyalin_komoditi_dan_hs_code_pertama(): void
    {
        $admin = $this->admin();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
            ['commodity' => 'Plastic goods',  'hs_code' => '39249090'],
        ])->call('save');

        $q = Quotation::first();

        Livewire::actingAs($admin)->test(QuotationManager::class)->call('convertToShipment', $q->id);

        $s = Shipment::where('quotation_id', $q->id)->first();

        $this->assertNotNull($s);
        $this->assertSame('6006.32.90', $s->hs_code, 'hs_code diambil dari komoditi pertama');
        $this->assertStringContainsString('Textile fabric', $s->commodity);
        $this->assertStringContainsString('Plastic goods', $s->commodity);

        // Tidak ada yang boleh hilang diam-diam: rincian lengkap + disclaimer
        // harus ikut ke catatan shipment.
        $this->assertStringContainsString('6006.32.90', $s->notes);
        $this->assertStringContainsString('3924.90.90', $s->notes);
        $this->assertStringContainsString('wajib diverifikasi', $s->notes);
    }

    public function test_nama_komoditi_panjang_dipotong_agar_konversi_tidak_meledak(): void
    {
        // shipments.commodity hanya 200 karakter. Tanpa pemotongan, konversi
        // gagal dengan "Data too long" — jenis kegagalan yang sama seperti
        // bug berat shipment sebelumnya.
        $admin = $this->admin();

        $panjang = array_map(
            fn ($i) => ['commodity' => "Komoditi nomor {$i} dengan nama panjang sekali", 'hs_code' => ''],
            range(1, 12),
        );

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class), $panjang)->call('save');

        $q = Quotation::first();
        Livewire::actingAs($admin)->test(QuotationManager::class)->call('convertToShipment', $q->id);

        $s = Shipment::where('quotation_id', $q->id)->first();

        $this->assertNotNull($s, 'konversi tidak boleh gagal karena nama terlalu panjang');
        $this->assertLessThanOrEqual(200, mb_strlen($s->commodity));
    }

    public function test_quotation_tanpa_komoditi_tetap_bisa_dikonversi(): void
    {
        // 27 quotation production dibuat sebelum fitur ini ada.
        $admin = $this->admin();

        $this->form(Livewire::actingAs($admin)->test(QuotationManager::class), [
            ['commodity' => '', 'hs_code' => ''],
        ])->call('save');

        $q = Quotation::first();
        Livewire::actingAs($admin)->test(QuotationManager::class)->call('convertToShipment', $q->id);

        $s = Shipment::where('quotation_id', $q->id)->first();

        $this->assertNotNull($s);
        $this->assertNull($s->hs_code);
    }

    // ── Pencarian BTKI ─────────────────────────────────────────────────

    public function test_saran_bisa_dicari_lewat_nama_barang(): void
    {
        $this->btki('6006.32.90', 'Kain rajutan lainnya', 'Other knitted fabrics');
        $this->btki('3924.90.90', 'Perkakas rumah tangga plastik', 'Plastic household goods');

        $hasil = HsCodeFormatter::saran('rajutan');

        $this->assertCount(1, $hasil);
        $this->assertSame('6006.32.90', $hasil->first()->hs_code);
    }

    public function test_saran_bisa_dicari_lewat_potongan_kode(): void
    {
        $this->btki('6006.32.90', 'Kain rajutan lainnya', 'Other knitted fabrics');
        $this->btki('3924.90.90', 'Perkakas rumah tangga plastik', 'Plastic household goods');

        $hasil = HsCodeFormatter::saran('6006');

        $this->assertSame('6006.32.90', $hasil->first()->hs_code);
    }

    // ── Cetak ──────────────────────────────────────────────────────────

    public function test_pdf_memuat_blok_rekomendasi_beserta_peringatannya(): void
    {
        // Disclaimer wajib ikut tercetak: begitu HS Code muncul di dokumen
        // berkop perusahaan, pembacanya menganggapnya klasifikasi final.
        $this->btki('6006.32.90', 'Kain rajutan lainnya', 'Other knitted fabrics');

        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => 'Textile fabric', 'hs_code' => '60063290'],
        ])->call('save');

        $quotation = Quotation::with('commodities')->first();

        $html = view('admin.quotation-print', [
            'quotation'     => $quotation,
            'terbilangText' => 'seribu rupiah',
            'signer'        => null,
            'signatureType' => null,
        ])->render();

        $this->assertStringContainsString('COMMODITY &amp; HS CODE RECOMMENDATION', $html);
        $this->assertStringContainsString('Textile fabric', $html);
        $this->assertStringContainsString('6006.32.90', $html);
        $this->assertStringContainsString('wajib diverifikasi', $html);
    }

    public function test_pdf_tanpa_komoditi_tidak_memunculkan_blok_kosong(): void
    {
        $this->form(Livewire::actingAs($this->admin())->test(QuotationManager::class), [
            ['commodity' => '', 'hs_code' => ''],
        ])->call('save');

        $html = view('admin.quotation-print', [
            'quotation'     => Quotation::with('commodities')->first(),
            'terbilangText' => 'seribu rupiah',
            'signer'        => null,
            'signatureType' => null,
        ])->render();

        $this->assertStringNotContainsString('COMMODITY &amp; HS CODE RECOMMENDATION', $html);
    }
}
