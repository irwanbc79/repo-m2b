<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;

/**
 * Import legacy customers dari Accurate Accounting (data sebelum portal M2B).
 * Aman dijalankan berulang — skip jika nama sudah ada (case-insensitive).
 * Tidak mengganggu data existing.
 */
class OldDataCustomerSeeder extends Seeder
{
    // Daftar pelanggan dari Accurate (disaring: hanya PT/CV/UD yang relevan,
    // skip internal M2B, skip generic, skip individu/karyawan)
    private array $customers = [
        ['code' => 'C.00002', 'name' => 'PT. Fraser Masindo Boiler',            'phone' => '082361803211', 'pic' => 'PT. Fraser Masindo Boiler'],
        ['code' => 'C.00003', 'name' => 'PT. Murangan Marelan Jaya',             'phone' => '081260440282', 'pic' => 'M. Subhan'],
        ['code' => 'C.00007', 'name' => 'PT. Garuda Masindo Fan',                'phone' => '082363116027', 'pic' => ''],
        ['code' => 'C.00008', 'name' => 'PT. PRIMAJAYA MULTI TECHNOLOGY',        'phone' => '061-7369900',  'pic' => ''],
        ['code' => 'C.00010', 'name' => 'PT. Graha Segara',                      'phone' => '',             'pic' => ''],
        ['code' => 'C.00011', 'name' => 'PT. NASCO',                             'phone' => '',             'pic' => ''],
        ['code' => 'C.00012', 'name' => 'PT. Media Antar Nusa',                  'phone' => '',             'pic' => ''],
        ['code' => 'C.00013', 'name' => 'PT. Puncak Digital Sukses',             'phone' => '',             'pic' => ''],
        ['code' => 'C.00014', 'name' => 'CV. Barokah Jaya Logistik',             'phone' => '',             'pic' => ''],
        ['code' => 'C.00016', 'name' => 'PT. Rackh Lintas Asia',                 'phone' => '081375101433', 'pic' => ''],
        ['code' => 'C.00018', 'name' => 'CV. Try Jaya Samudera',                 'phone' => '',             'pic' => 'CV. Tri Jaya Samudera'],
        ['code' => 'C.00029', 'name' => 'Imperial Wallpaper & Flooring',         'phone' => '08116300790',  'pic' => ''],
        ['code' => 'C.00031', 'name' => 'CV. Mitra Usaha',                       'phone' => '',             'pic' => ''],
        ['code' => 'C.00032', 'name' => 'CV. Sumber Rezeki',                     'phone' => '',             'pic' => ''],
        ['code' => 'C.00036', 'name' => 'PT. Citra Persada Container',           'phone' => '',             'pic' => ''],
        ['code' => 'C.00037', 'name' => 'PT. Perusahaan Pelayaran Nusantara Panurjwan', 'phone' => '061-4518596', 'pic' => ''],
        ['code' => 'C.00044', 'name' => 'PT. GLOBAL TRANSPORTASI NUSANTARA',     'phone' => '',             'pic' => ''],
        ['code' => 'C.00045', 'name' => 'PT. Masaji Tatanan Kontainer Indonesia','phone' => '',             'pic' => ''],
        ['code' => 'C.00046', 'name' => 'PT. GANINDO OVERSEAS INTERNASIONAL',    'phone' => '',             'pic' => ''],
        ['code' => 'C.00047', 'name' => 'PT. NIAGA INDOGUNA YASA',               'phone' => '',             'pic' => ''],
        ['code' => 'C.00048', 'name' => 'RODA USAHANTA TRANS',                   'phone' => '',             'pic' => ''],
        ['code' => 'C.00050', 'name' => 'PT. INDO JAYA SINERGI',                 'phone' => '',             'pic' => ''],
        ['code' => 'C.00051', 'name' => 'PT. HEISZCO ATARA SINDO',               'phone' => '',             'pic' => ''],
        ['code' => 'C.00052', 'name' => 'PT. RAJAWALI BAHRUM PERKASA',           'phone' => '',             'pic' => ''],
        ['code' => 'C.00053', 'name' => 'PT. BERSATU NUSA CEMERLANG',            'phone' => '',             'pic' => ''],
        ['code' => 'C.00054', 'name' => 'CV. BINTANG PRIMA SEMESTA',             'phone' => '',             'pic' => ''],
        ['code' => 'C.00055', 'name' => 'PT. GUNUNG MAS SAKTI ABADI',            'phone' => '',             'pic' => ''],
        ['code' => 'C.00056', 'name' => 'CV. ANUGRAH KARYA SEJAHTERA',           'phone' => '',             'pic' => ''],
        ['code' => 'C.00057', 'name' => 'PT. INTAN SEJAHTERA ABADI',             'phone' => '',             'pic' => ''],
        ['code' => 'C.00058', 'name' => 'PT. BENSULI ASAM SAWIT',                'phone' => '',             'pic' => ''],
        ['code' => 'C.00059', 'name' => 'PT. INDOMAS TEKNIK PERKASA',            'phone' => '',             'pic' => ''],
        ['code' => 'C.00060', 'name' => 'PT. MULTI GANDA MEDAN',                 'phone' => '',             'pic' => ''],
        ['code' => 'C.00061', 'name' => 'PT. CLIPAN FINANCE INDONESIA',          'phone' => '',             'pic' => ''],
        ['code' => 'C.00062', 'name' => 'PT. CIPTA PIRANTI TEKNIK',              'phone' => '',             'pic' => ''],
        ['code' => 'C.00063', 'name' => 'CV. KARYA MANDIRI',                     'phone' => '',             'pic' => ''],
        ['code' => 'C.00064', 'name' => 'PT. SAMUDERA INDONESIA',                'phone' => '',             'pic' => ''],
        ['code' => 'C.00065', 'name' => 'CV. MAJU JAYA ABADI',                   'phone' => '',             'pic' => ''],
        ['code' => 'C.00066', 'name' => 'PT. SINAR MAS AGRI RESOURCES',          'phone' => '',             'pic' => ''],
        ['code' => 'C.00067', 'name' => 'PT. TRITON MARITIM NUSANTARA',          'phone' => '',             'pic' => ''],
        ['code' => 'C.00068', 'name' => 'CV. SENTOSA ABADI',                     'phone' => '',             'pic' => ''],
    ];

    public function run(): void
    {
        $imported = 0;
        $skipped  = 0;

        // Ambil semua nama existing (normalized uppercase) untuk cek duplikat
        $existingNames = Customer::pluck('company_name')
            ->map(fn($n) => $this->normalize($n))
            ->toArray();

        foreach ($this->customers as $data) {
            $normalized = $this->normalize($data['name']);

            // Skip jika sudah ada (by normalized name)
            if (in_array($normalized, $existingNames)) {
                $skipped++;
                continue;
            }

            $code = Customer::generateCustomerCode();

            Customer::create([
                'user_id'       => null,
                'customer_code' => $code,
                'company_name'  => $data['name'],
                'business_type' => 'Freight Forwarding [Accurate: ' . $data['code'] . ']',
                'address'       => '',
                'city'          => 'Medan',
                'country'       => 'Indonesia',
            ]);

            $existingNames[] = $normalized; // Prevent duplicate within this batch
            $imported++;
        }

        $this->command->info("OldDataCustomerSeeder: {$imported} imported, {$skipped} skipped (already exist).");
    }

    private function normalize(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        // Hapus prefix umum supaya "PT. X" == "PT X" == "X"
        $name = preg_replace('/^(PT\.|PT |CV\.|CV |UD\.|UD )/i', '', $name);
        return trim($name);
    }
}
