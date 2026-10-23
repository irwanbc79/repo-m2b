<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportBtkiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btki:import {--file= : Path to BTKI CSV file} {--force : Truncate and fresh import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and synchronize BTKI 2022 tariff data, chapters, and KUM HS into database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('  BTKI 2022 Data Importer & Synchronizer');
        $this->info('====================================================');

        // 1. Seed KUM HS
        $this->seedKumHs();

        // 2. Seed Chapters
        $this->seedChapters();

        // 3. Import HS Codes
        $filePath = $this->option('file') ?: base_path('database/data/btki_2022_data.csv');
        if (!file_exists($filePath)) {
            // Check fallback path
            $fallback = '/Users/irwanbece/Documents/portal-m2b/btki_2022_data.csv';
            if (file_exists($fallback)) {
                $filePath = $fallback;
            } else {
                $this->error("File not found: {$filePath}");
                return 1;
            }
        }

        $this->importHsCodes($filePath, $this->option('force'));

        $this->info("\n✨ BTKI 2022 import completed successfully!");
        return 0;
    }

    /**
     * Seed Ketentuan Umum Menginterpretasikan HS (KUM HS 1 - 6)
     */
    protected function seedKumHs(): void
    {
        $this->line('📚 Seeding KUM HS (Rules 1 - 6)...');

        $rules = [
            [
                'rule_number' => 1,
                'title' => 'Aturan 1 - Judul Bagian, Bab dan Sub-bab',
                'content' => 'Judul dari Bagian, Bab dan Sub-bab dimaksudkan hanya untuk mempermudah referensi saja; untuk keperluan hukum, klasifikasi harus ditentukan berdasarkan uraian yang terdapat dalam pos dan berbagai Catatan Bagian atau Bab yang berkaitan serta berdasarkan ketentuan berikut ini, asalkan pos atau Catatan tersebut tidak menentukan lain.'
            ],
            [
                'rule_number' => 2,
                'title' => 'Aturan 2 - Barang Tidak Lengkap / Belum Dirakit',
                'content' => '(a) Setiap referensi pada suatu barang dalam suatu pos harus dianggap meliputi juga referensi pada barang tersebut yang tidak lengkap atau belum selesai, asalkan pada waktu diajukan barang tidak lengkap atau belum selesai itu mempunyai karakter esensial dari barang yang lengkap atau selesai. Pos tersebut juga meliputi barang yang lengkap atau selesai (atau diklasifikasikan sebagai barang lengkap atau selesai berdasarkan Aturan ini), yang diajukan belum dirakit atau dibongkar. (b) Setiap referensi pada suatu bahan atau zat dalam suatu pos harus dianggap meliputi referensi pada campuran atau kombinasi dari bahan atau zat tersebut dengan bahan atau zat lain. Klasifikasi barang yang terdiri dari lebih dari satu bahan atau zat harus sesuai dengan prinsip-prinsip dalam Aturan 3.'
            ],
            [
                'rule_number' => 3,
                'title' => 'Aturan 3 - Klasifikasi Dua Pos atau Lebih (Karakter Esensial)',
                'content' => 'Apabila barang seolah-olah dapat diklasifikasikan dalam dua pos atau lebih: (a) Pos yang memberikan uraian paling spesifik harus dipilih daripada pos yang memberikan uraian lebih umum. (b) Barang campuran, barang komposit yang terdiri dari bahan berbeda atau dibuat dari komponen berbeda, dan barang yang disiapkan dalam set untuk penjualan eceran, harus diklasifikasikan seolah-olah terdiri dari bahan atau komponen yang memberikan karakter esensialnya. (c) Apabila barang tidak dapat diklasifikasikan dengan mengacu pada 3(a) atau 3(b), barang harus diklasifikasikan dalam pos yang muncul terakhir dalam urutan numerik di antara pos-pos yang sama-sama patut dipertimbangkan.'
            ],
            [
                'rule_number' => 4,
                'title' => 'Aturan 4 - Barang yang Paling Mirip (Kinship Principle)',
                'content' => 'Barang yang tidak dapat diklasifikasikan sesuai dengan Aturan 1 sampai dengan 3, harus diklasifikasikan dalam pos yang sesuai dengan barang yang paling mirip (serupa) dengannya.'
            ],
            [
                'rule_number' => 5,
                'title' => 'Aturan 5 - Kemasan dan Wadah Khusus',
                'content' => '(a) Kotak kamera, kotak alat musik, kotak senjata, kotak alat gambar, kotak kalung dan wadah serupa, yang dibentuk atau disesuaikan khusus untuk menampung suatu barang atau set tertentu, cocok untuk penggunaan jangka panjang dan diajukan bersama barang yang dimaksudkan untuk ditampung, harus diklasifikasikan dengan barang tersebut apabila jenis ini biasa dijual bersama barang tersebut. (b) Tunduk pada ketentuan Aturan 5(a), bahan pengemas dan wadah pengemas yang diajukan bersama barang di dalamnya harus diklasifikasikan bersama apabila biasa digunakan untuk mengemas barang tersebut, kecuali bila wadah tersebut jelas cocok untuk penggunaan berulang.'
            ],
            [
                'rule_number' => 6,
                'title' => 'Aturan 6 - Klasifikasi Tingkat Subpos',
                'content' => 'Untuk keperluan hukum, klasifikasi barang dalam subpos suatu pos harus ditentukan menurut uraian subpos dan Catatan Subpos yang berkaitan serta mutatis mutandis Aturan di atas, dengan pengertian bahwa hanya subpos pada tingkat yang sama yang dapat diperbandingkan. Untuk keperluan Aturan ini, Catatan Bagian dan Bab yang berkaitan juga berlaku, kecuali apabila konteksnya menentukan lain.'
            ],
        ];

        foreach ($rules as $r) {
            DB::table('hs_kum')->updateOrInsert(
                ['rule_number' => $r['rule_number']],
                [
                    'title' => $r['title'],
                    'content' => $r['content'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->info('  ✓ 6 aturan KUM HS tersimpan.');
    }

    /**
     * Seed Chapters (Bab 01 - 99)
     */
    protected function seedChapters(): void
    {
        $this->line('📁 Seeding BTKI Chapters (Bab 01 - 99)...');

        $jsonPath = base_path('database/data/btki-2022-bab.json');
        $chaptersData = [];
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            $chaptersData = $json['chapters'] ?? [];
        }

        // Section mappings
        $sectionMapping = [
            '01' => 1, '02' => 1, '03' => 1, '04' => 1, '05' => 1,
            '06' => 2, '07' => 2, '08' => 2, '09' => 2, '10' => 2, '11' => 2, '12' => 2, '13' => 2, '14' => 2,
            '15' => 3,
            '16' => 4, '17' => 4, '18' => 4, '19' => 4, '20' => 4, '21' => 4, '22' => 4, '23' => 4, '24' => 4,
            '25' => 5, '26' => 5, '27' => 5,
            '28' => 6, '29' => 6, '30' => 6, '31' => 6, '32' => 6, '33' => 6, '34' => 6, '35' => 6, '36' => 6, '37' => 6, '38' => 6,
            '39' => 7, '40' => 7,
            '41' => 8, '42' => 8, '43' => 8,
            '44' => 9, '45' => 9, '46' => 9,
            '47' => 10, '48' => 10, '49' => 10,
            '50' => 11, '51' => 11, '52' => 11, '53' => 11, '54' => 11, '55' => 11, '56' => 11, '57' => 11, '58' => 11, '59' => 11, '60' => 11, '61' => 11, '62' => 11, '63' => 11,
            '64' => 12, '65' => 12, '66' => 12, '67' => 12,
            '68' => 13, '69' => 13, '70' => 13,
            '71' => 14,
            '72' => 15, '73' => 15, '74' => 15, '75' => 15, '76' => 15, '78' => 15, '79' => 15, '80' => 15, '81' => 15, '82' => 15, '83' => 15,
            '84' => 16, '85' => 16,
            '86' => 17, '87' => 17, '88' => 17, '89' => 17,
            '90' => 18, '91' => 18, '92' => 18,
            '93' => 19,
            '94' => 20, '95' => 20, '96' => 20,
            '97' => 21, '98' => 21, '99' => 21
        ];

        // Ensure default sections exist if empty
        if (DB::table('hs_sections')->count() === 0) {
            $sections = [
                ['section_number' => 'I', 'title_id' => 'Binatang Hidup; Produk Hewani', 'title_en' => 'Live Animals; Animal Products'],
                ['section_number' => 'II', 'title_id' => 'Produk Nabati', 'title_en' => 'Vegetable Products'],
                ['section_number' => 'III', 'title_id' => 'Lemak dan Minyak Hewani, Nabati atau Mikroba', 'title_en' => 'Animal, Vegetable or Microbial Fats and Oils'],
                ['section_number' => 'IV', 'title_id' => 'Bahan Makanan Olahan; Minuman, Alkohol dan Cuka; Tembakau', 'title_en' => 'Prepared Foodstuffs; Beverages, Spirits, and Vinegar; Tobacco'],
                ['section_number' => 'V', 'title_id' => 'Produk Mineral', 'title_en' => 'Mineral Products'],
                ['section_number' => 'VI', 'title_id' => 'Produk Industri Kimia atau Industri Terkait', 'title_en' => 'Products of the Chemical or Allied Industries'],
                ['section_number' => 'VII', 'title_id' => 'Plastik dan Barang Daripadanya; Karet dan Barang Daripadanya', 'title_en' => 'Plastics and Articles Thereof; Rubber and Articles Thereof'],
                ['section_number' => 'VIII', 'title_id' => 'Kulit Mentah, Kulit Samak, Kulit Berbulu dan Barang Daripadanya', 'title_en' => 'Raw Hides and Skins, Leather, Furskins and Articles Thereof'],
                ['section_number' => 'IX', 'title_id' => 'Kayu dan Barang dari Kayu; Arang Kayu; Gabus; Barang Anyaman', 'title_en' => 'Wood and Articles of Wood; Wood Charcoal; Cork; Basketware'],
                ['section_number' => 'X', 'title_id' => 'Pulp dari Kayu atau Bahan Selulosa Lainnya; Kertas dan Karton', 'title_en' => 'Pulp of Wood; Paper and Paperboard and Articles Thereof'],
                ['section_number' => 'XI', 'title_id' => 'Tekstil dan Barang Tekstil', 'title_en' => 'Textiles and Textile Articles'],
                ['section_number' => 'XII', 'title_id' => 'Alas Kaki, Tutup Kepala, Payung; Bunga Artifisial', 'title_en' => 'Footwear, Headgear, Umbrellas; Prepared Feathers and Articles'],
                ['section_number' => 'XIII', 'title_id' => 'Barang dari Batu, Plester, Semen, Asbes, Mika; Keramik; Kaca', 'title_en' => 'Articles of Stone, Plaster, Cement; Ceramic Products; Glass'],
                ['section_number' => 'XIV', 'title_id' => 'Mutiara Alam, Permata, Logam Mulia dan Barang Daripadanya; Perhiasan Tiruan', 'title_en' => 'Natural Pearls, Precious Stones, Precious Metals and Articles; Imitation Jewellery'],
                ['section_number' => 'XV', 'title_id' => 'Logam Tidak Mulia dan Barang dari Logam Tidak Mulia', 'title_en' => 'Base Metals and Articles of Base Metal'],
                ['section_number' => 'XVI', 'title_id' => 'Mesin dan Peralatan Mekanis; Peralatan Listrik; Bagiannya', 'title_en' => 'Machinery and Mechanical Appliances; Electrical Equipment; Parts'],
                ['section_number' => 'XVII', 'title_id' => 'Kendaraan, Pesawat Udara, Kapal dan Peralatan Transportasi Terkait', 'title_en' => 'Vehicles, Aircraft, Vessels and Associated Transport Equipment'],
                ['section_number' => 'XVIII', 'title_id' => 'Instrumen Optik, Fotografi, Sinematografi, Medis, Bedah; Jam; Alat Musik', 'title_en' => 'Optical, Photographic, Cinematographic, Measuring, Medical Instruments; Clocks; Musical Instruments'],
                ['section_number' => 'XIX', 'title_id' => 'Senjata dan Amunisi; Bagian dan Aksesorinya', 'title_en' => 'Arms and Ammunition; Parts and Accessories Thereof'],
                ['section_number' => 'XX', 'title_id' => 'Aneka Barang Hasil Pabrikasi', 'title_en' => 'Miscellaneous Manufactured Articles'],
                ['section_number' => 'XXI', 'title_id' => 'Karya Seni, Barang Koleksi dan Barang Antik; Ketentuan Khusus', 'title_en' => 'Works of Art, Collectors\' Pieces and Antiques; Special Provisions']
            ];

            foreach ($sections as $i => $sec) {
                DB::table('hs_sections')->insert([
                    'id' => $i + 1,
                    'section_number' => $sec['section_number'],
                    'title_id' => $sec['title_id'],
                    'title_en' => $sec['title_en'],
                    'display_order' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $countChapters = 0;
        foreach ($chaptersData as $chapNum => $titles) {
            $chapNum = str_pad((string)$chapNum, 2, '0', STR_PAD_LEFT);
            $secId = $sectionMapping[$chapNum] ?? null;

            DB::table('hs_chapters')->updateOrInsert(
                ['chapter_number' => $chapNum],
                [
                    'title_id' => $titles['id'] ?? "Bab {$chapNum}",
                    'title_en' => $titles['en'] ?? "Chapter {$chapNum}",
                    'section_id' => $secId,
                    'display_order' => (int)$chapNum,
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
            $countChapters++;
        }

        $this->info("  ✓ {$countChapters} Bab BTKI tersimpan.");
    }

    /**
     * Import HS Codes from CSV
     */
    protected function importHsCodes(string $filePath, bool $force): void
    {
        $this->line("📦 Reading and importing HS codes from: {$filePath}...");

        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->error('Failed to open CSV file.');
            return;
        }

        // Header: hs_code,chapter,description_id,description_en,import_duty,export_duty,ppn,ppnbm,hs_level
        $header = fgetcsv($file);

        if ($force) {
            $this->warn('  Truncating existing hs_codes table...');
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::table('hs_codes')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $batch = [];
        $batchSize = 500;
        $totalImported = 0;
        $startTime = microtime(true);

        // Pre-build headings cache for fast parent mapping
        $headingMap = [];

        while (($row = fgetcsv($file)) !== false) {
            if (empty($row[0])) continue;

            $hsCode = trim($row[0]);
            $chapter = trim($row[1] ?? substr(str_replace('.', '', $hsCode), 0, 2));
            $chapter = str_pad($chapter, 2, '0', STR_PAD_LEFT);
            $descId = trim($row[2] ?? '');
            $descEn = trim($row[3] ?? '');
            $importDuty = trim($row[4] ?? '');
            $exportDuty = trim($row[5] ?? '');
            $ppn = trim($row[6] ?? '');
            $ppnbm = trim($row[7] ?? '');
            $level = (int)($row[8] ?? 8);

            // Format standard HS Code: xxxx, xxxx.xx, xxxx.xx.xx
            $cleanCode = preg_replace('/\D+/', '', $hsCode);
            $codeLen = strlen($cleanCode);
            $parentCode = null;

            if ($codeLen === 4) {
                $standardHsCode = $cleanCode; // Pos 4-digit: xxxx (tanpa titik)
                $level = 4;
                $parentCode = $chapter;
                $headingMap[substr($cleanCode, 0, 4)] = $standardHsCode;
            } elseif ($codeLen === 6) {
                $standardHsCode = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2); // Subpos 6-digit: xxxx.xx
                $level = 6;
                $parentCode = substr($cleanCode, 0, 4); // Pos 4-digit induk: xxxx
                $headingMap[substr($cleanCode, 0, 6)] = $standardHsCode;
            } elseif ($codeLen === 8) {
                $standardHsCode = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2); // 8-digit: xxxx.xx.xx
                $level = 8;
                $sub6 = substr($cleanCode, 0, 4) . '.' . substr($cleanCode, 4, 2);
                if (isset($headingMap[substr($cleanCode, 0, 6)])) {
                    $parentCode = $sub6;
                } else {
                    $parentCode = substr($cleanCode, 0, 4);
                }
            } else {
                $standardHsCode = $hsCode;
            }

            // Standardize duties
            // If empty or '-', keep clean
            $bm = ($importDuty === '' || $importDuty === null) ? null : $importDuty;
            $bk = ($exportDuty === '' || $exportDuty === null) ? '-' : $exportDuty;
            $ppnVal = ($ppn === '' || $ppn === null) ? ($level == 8 ? '11' : null) : $ppn;
            $ppnbmVal = ($ppnbm === '' || $ppnbm === null) ? '-' : $ppnbm;

            // Default PPh Impor for national 8-digit tariff lines
            $pphApi = null;
            $pphNonApi = null;
            if ($level == 8) {
                $pphApi = '2.5';
                $pphNonApi = '7.5';
            }

            $batch[] = [
                'hs_code' => $standardHsCode,
                'hs_level' => $level,
                'parent_code' => $parentCode,
                'description_id' => $descId,
                'description_en' => $descEn,
                'description_full' => $descId,
                'description_full_en' => $descEn,
                'chapter_number' => $chapter,
                'section_number' => null,
                'import_duty' => $bm,
                'export_duty' => $bk,
                'ppn' => $ppnVal,
                'ppnbm' => $ppnbmVal,
                'pph_api' => $pphApi,
                'pph_non_api' => $pphNonApi,
                'is_active' => true,
                'import_batch_id' => 'BTKI_2022',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('hs_codes')->upsert(
                    $batch,
                    ['hs_code'],
                    [
                        'hs_level', 'parent_code', 'description_id', 'description_en',
                        'description_full', 'description_full_en', 'chapter_number',
                        'import_duty', 'export_duty', 'ppn', 'ppnbm', 'pph_api', 'pph_non_api',
                        'is_active', 'updated_at'
                    ]
                );
                $totalImported += count($batch);
                $this->output->write("\r  📥 Imported/Updated {$totalImported} records...");
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('hs_codes')->upsert(
                $batch,
                ['hs_code'],
                [
                    'hs_level', 'parent_code', 'description_id', 'description_en',
                    'description_full', 'description_full_en', 'chapter_number',
                    'import_duty', 'export_duty', 'ppn', 'ppnbm', 'pph_api', 'pph_non_api',
                    'is_active', 'updated_at'
                ]
            );
            $totalImported += count($batch);
        }

        fclose($file);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->line("\n  ✓ Total {$totalImported} records processed in {$elapsed} seconds.");
    }
}
