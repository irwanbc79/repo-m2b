<?php
/**
 * BTKI 2022 Re-Import - CORRECT COLUMN MAPPING
 * Fix: Column I (bukan B/C) untuk description
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$startTime = microtime(true);
$batchSize = 500;
$batchId = 'BTKI_2022_' . date('YmdHis');

echo "=== BTKI 2022 Re-Import - CORRECT COLUMN MAPPING ===\n\n";

$excelFile = __DIR__ . '/scripts/python/E-BTKI 2022 v1 - 1 April 2022.xlsx';

if (!file_exists($excelFile)) {
    die("❌ Excel file not found: $excelFile\n");
}

echo "📂 Reading Excel file...\n";
$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getSheetByName('Table 1') ?: $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();
echo "📊 Found $highestRow rows\n\n";

// Keywords untuk skip
$skipKeywords = [
    'Copyright', 'BTKI', 'POS TARIF', 'Ketentuan', 'General', 
    'Struktur', 'Bagian', 'Chapter', 'Bab', 'Contents', 'Notes', 'HS CODE'
];

// ============================================
// STEP 1: Clear existing data from failed import
// ============================================
echo "=== STEP 1: Clearing existing empty data ===\n";

$deleted = DB::table('hs_codes')
    ->where(function($q) {
        $q->whereNull('description_id')
          ->orWhere('description_id', '');
    })
    ->delete();

echo "✅ Deleted $deleted empty records\n\n";

// ============================================
// STEP 2: Extract Chapter Numbers
// ============================================
echo "=== STEP 2: Extracting Chapter Numbers ===\n";

$chapterNumbers = [];
for ($row = 36; $row <= $highestRow; $row++) {
    $hsCode = trim($sheet->getCell("A$row")->getValue() ?? '');
    if (empty($hsCode)) continue;
    
    $cleanCode = preg_replace('/[^0-9]/', '', $hsCode);
    if (strlen($cleanCode) >= 2) {
        $chapterNumber = substr($cleanCode, 0, 2);
        $chapterNumbers[$chapterNumber] = true;
    }
}

$chapterNumbers = array_keys($chapterNumbers);
sort($chapterNumbers);
echo "✅ Found " . count($chapterNumbers) . " unique chapters\n\n";

// ============================================
// STEP 3: Import HS Codes with CORRECT MAPPING
// ============================================
echo "=== STEP 3: Importing HS Codes (Column I for description) ===\n";

$allRecords = [];
$uniqueCodes = [];
$totalSkipped = 0;
$duplicatesRemoved = 0;

for ($row = 36; $row <= $highestRow; $row++) {
    $hsCode = trim($sheet->getCell("A$row")->getValue() ?? '');
    
    // ✅ CORRECT MAPPING: Column I untuk description
    $descriptionId = trim($sheet->getCell("I$row")->getValue() ?? '');
    
    // Skip empty rows
    if (empty($hsCode)) {
        continue;
    }
    
    // Skip keyword rows
    $shouldSkip = false;
    foreach ($skipKeywords as $keyword) {
        if (stripos($hsCode, $keyword) !== false || stripos($descriptionId, $keyword) !== false) {
            $shouldSkip = true;
            break;
        }
    }
    
    if ($shouldSkip) {
        $totalSkipped++;
        continue;
    }
    
    // Clean HS code
    $cleanCode = preg_replace('/[^0-9]/', '', $hsCode);
    
    if (empty($cleanCode) || !ctype_digit($cleanCode)) {
        continue;
    }
    
    $codeLength = strlen($cleanCode);
    
    // Valid HS code length: 2, 4, 6, 8, 10
    if (!in_array($codeLength, [2, 4, 6, 8, 10])) {
        continue;
    }
    
    // Format kode hierarkis
    $formattedCode = '';
    $parentCode = null;
    
    if ($codeLength == 2) {
        $formattedCode = $cleanCode;
        $parentCode = null;
    } elseif ($codeLength == 4) {
        $formattedCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2);
        $parentCode = substr($cleanCode, 0, 2);
    } elseif ($codeLength == 6) {
        $formattedCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2) . '.' . substr($cleanCode, 4, 2);
        $parentCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2);
    } elseif ($codeLength == 8) {
        $formattedCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2);
        $parentCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2) . '.' . substr($cleanCode, 4, 2);
    } elseif ($codeLength == 10) {
        $formattedCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2) . '.' . substr($cleanCode, 8, 2);
        $parentCode = substr($cleanCode, 0, 2) . '.' . substr($cleanCode, 2, 2) . '.' . substr($cleanCode, 4, 2) . '.' . substr($cleanCode, 6, 2);
    }
    
    // Deduplication
    if (isset($uniqueCodes[$formattedCode])) {
        $duplicatesRemoved++;
        continue;
    }
    
    $uniqueCodes[$formattedCode] = true;
    
    $chapterNumber = substr($cleanCode, 0, 2);
    
    // Prepare record
    $allRecords[] = [
        'hs_code' => $formattedCode,
        'hs_level' => $codeLength,
        'parent_code' => $parentCode,
        'description_id' => mb_substr($descriptionId, 0, 500),
        'description_en' => mb_substr($descriptionId, 0, 500), // Same as ID karena Excel hanya ada 1 bahasa
        'chapter_number' => $chapterNumber,
        'section_number' => null,
        'is_active' => true,
        'import_batch_id' => $batchId,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];
}

echo "✅ Total unique records to import: " . count($allRecords) . "\n";
echo "🔄 Duplicates removed: $duplicatesRemoved\n";
echo "⏭️  Rows skipped: $totalSkipped\n\n";

// ============================================
// STEP 4: Batch Insert
// ============================================
echo "=== STEP 4: Batch Insert to Database ===\n";

$totalImported = 0;
$totalErrors = 0;
$chunks = array_chunk($allRecords, $batchSize);

foreach ($chunks as $index => $chunk) {
    try {
        DB::table('hs_codes')->insert($chunk);
        $totalImported += count($chunk);
        
        $percentage = round((($index + 1) / count($chunks)) * 100, 1);
        $elapsed = round(microtime(true) - $startTime, 1);
        echo "📥 [$percentage%] Imported $totalImported rows... ({$elapsed}s)\n";
        
    } catch (\Exception $e) {
        $totalErrors += count($chunk);
        echo "❌ Error in batch " . ($index + 1) . ": " . $e->getMessage() . "\n";
    }
}

// ============================================
// FINAL SUMMARY
// ============================================
$totalTime = round(microtime(true) - $startTime, 1);

echo "\n=== Import Summary ===\n";
echo "✅ Total imported: $totalImported\n";
echo "🔄 Duplicates removed: $duplicatesRemoved\n";
echo "⏭️  Total skipped: $totalSkipped\n";
echo "❌ Total errors: $totalErrors\n";
echo "⏱️  Total time: {$totalTime}s\n";
echo "🆔 Batch ID: $batchId\n\n";

// Distribution by level
$distribution = DB::table('hs_codes')
    ->where('import_batch_id', $batchId)
    ->select('hs_level', DB::raw('count(*) as count'))
    ->groupBy('hs_level')
    ->orderBy('hs_level')
    ->get();

echo "=== Distribution by Level ===\n";
foreach ($distribution as $dist) {
    $levelName = [
        2 => 'Chapter',
        4 => 'Heading',
        6 => 'Sub-heading',
        8 => 'Detail',
        10 => 'Sub-detail'
    ][$dist->hs_level] ?? 'Unknown';
    
    echo "Level {$dist->hs_level} ($levelName): {$dist->count}\n";
}

// Sample descriptions
echo "\n=== Sample Descriptions (first 5) ===\n";
$samples = DB::table('hs_codes')
    ->where('import_batch_id', $batchId)
    ->limit(5)
    ->get(['hs_code', 'description_id']);

foreach ($samples as $sample) {
    echo "  {$sample->hs_code}: " . mb_substr($sample->description_id, 0, 60) . "...\n";
}

echo "\n✅ Import completed successfully!\n";
