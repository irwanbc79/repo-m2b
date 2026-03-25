<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;

/**
 * Import legacy vendors (trucking/shipping/logistics) dari Accurate Accounting.
 * Hanya vendor yang relevan untuk operasional freight forwarding.
 * Aman dijalankan berulang — skip jika nama sudah ada.
 */
class OldDataVendorSeeder extends Seeder
{
    private array $vendors = [
        // Logistics & Trucking
        ['code' => 'V.00014', 'name' => 'CV. Barokah Jaya Logistik',                      'category' => 'Trucking',  'phone' => '0751499382'],
        ['code' => 'V.00018', 'name' => 'CV. Try Jaya Samudera',                           'category' => 'Trucking',  'phone' => ''],
        ['code' => 'V.00054', 'name' => 'PT. Citra Persada Container',                     'category' => 'Trucking',  'phone' => ''],
        ['code' => 'V.00055', 'name' => 'PT. Perusahaan Pelayaran Nusantara Panurjwan',    'category' => 'Shipping',  'phone' => '061-4518596'],
        ['code' => 'V.00062', 'name' => 'PT. NIAGA INDOGUNA YASA',                         'category' => 'Shipping',  'phone' => ''],
        ['code' => 'V.00072', 'name' => 'CV. Barokah Jaya Logistik',                       'category' => 'Trucking',  'phone' => '0751499382'],
        ['code' => 'V.00077', 'name' => 'PT. GLOBAL TRANSPORTASI NUSANTARA',               'category' => 'Trucking',  'phone' => ''],
        ['code' => 'V.00079', 'name' => 'MAJU BERSAMA KRAKATAU',                           'category' => 'Trucking',  'phone' => ''],
        ['code' => 'V.00082', 'name' => 'PT. Masaji Tatanan Kontainer Indonesia',          'category' => 'Depo',      'phone' => ''],
        ['code' => 'V.00085', 'name' => 'CV. BAROKAH JAYA LOGISTIK',                       'category' => 'Trucking',  'phone' => ''],
        ['code' => 'V.00091', 'name' => 'RODA USAHANTA TRANS',                             'category' => 'Trucking',  'phone' => ''],
        ['code' => 'V.00092', 'name' => 'PT. GANINDO OVERSEAS INTERNASIONAL',              'category' => 'Forwarder', 'phone' => ''],
        // Shipping Lines / Pelayaran
        ['code' => 'V.00002', 'name' => 'PT. Fraser Masindo Boiler',                       'category' => 'Other',     'phone' => '082361803211'],
        ['code' => 'V.00007', 'name' => 'PT. Garuda Masindo Fan',                          'category' => 'Other',     'phone' => '082363116027'],
        ['code' => 'V.00010', 'name' => 'PT. Graha Segara',                                'category' => 'Other',     'phone' => ''],
        ['code' => 'V.00011', 'name' => 'PT. Ganindo Overseas Internasional',              'category' => 'Forwarder', 'phone' => ''],
        ['code' => 'V.00016', 'name' => 'PT. Rackh Lintas Asia',                           'category' => 'Forwarder', 'phone' => '081375101433'],
        // Tax & Legal
        ['code' => 'V.00031', 'name' => 'Kantor Notaris - San Smith, S.H.',                'category' => 'Legal',     'phone' => '061-7332975'],
        ['code' => 'V.00084', 'name' => 'THE BEST KONSULTAN PAJAK',                        'category' => 'Tax',       'phone' => ''],
        // Bank
        ['code' => 'V.00074', 'name' => 'Bank Mandiri',                                    'category' => 'Bank',      'phone' => ''],
    ];

    public function run(): void
    {
        $imported = 0;
        $skipped  = 0;

        $existingNames = Vendor::pluck('name')
            ->map(fn($n) => $this->normalize($n))
            ->toArray();

        foreach ($this->vendors as $data) {
            $normalized = $this->normalize($data['name']);

            if (in_array($normalized, $existingNames)) {
                $skipped++;
                continue;
            }

            // Generate vendor code
            $lastVendor = Vendor::orderBy('id', 'desc')->first();
            $nextNum    = $lastVendor ? (intval(preg_replace('/[^0-9]/', '', $lastVendor->code)) + 1) : 1;
            $newCode    = 'VND-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            Vendor::create([
                'code'     => $newCode,
                'name'     => $data['name'],
                'category' => $data['category'],
                'phone'    => $data['phone'],
                'address'  => '',
                'notes'    => 'Diimpor dari data Accurate (legacy). Kode Accurate: ' . $data['code'],
            ]);

            $existingNames[] = $normalized;
            $imported++;
        }

        $this->command->info("OldDataVendorSeeder: {$imported} imported, {$skipped} skipped (already exist).");
    }

    private function normalize(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        $name = preg_replace('/^(PT\.|PT |CV\.|CV |UD\.|UD )/i', '', $name);
        return trim($name);
    }
}
