<?php
require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== BTKI 2022 Data Importer (PHP) ===\n\n";

$excelFile = __DIR__ . '/scripts/python/E-BTKI 2022 v1 - 1 April 2022.xlsx';

if (!file_exists($excelFile)) {
    die("❌ ERROR: File not found: {$excelFile}\n");
}

echo "📂 Reading Excel file...\n";
$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getSheetByName('Table 1') ?: $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();

echo "📊 Found {$highestRow} rows\n";
echo "⚙️  Processing data...\n\n";

$batchId = date('YmdHis');
$imported = 0;
$batch = [];
$batchSize = 500;
$startTime = microtime(true);

// Start from row 2 (skip header)
for ($row = 2; $row <= $highestRow; $row++) {
    $hsCode = trim($sheet->getCell("A{$row}")->getValue() ?? '');
    $descId = trim($sheet->getCell("B{$row}")->getValue() ?? '');
    $descEn = trim($sheet->getCell("C{$row}")->getValue() ?? '');
    
    if (empty($hsCode)) continue;
    
    // Detect HS level from code format
    $cleanCode = str_replace(['.', ' '], '', $hsCode);
    $codeLen = strlen($cleanCode);
    
    // Determine level: 2,4,6,8,10 digits
    if ($codeLen <= 2) {
        $level = 2;
    } elseif ($codeLen <= 4) {
        $level = 4;
    } elseif ($codeLen <= 6) {
        $level = 6;
    } elseif ($codeLen <= 8) {
        $level = 8;
    } else {
        $level = 10;
    }
    
    // Calculate parent code
    $parentCode = null;
    if ($level > 2) {
        $parentLen = $level - 2;
        $parentClean = substr($cleanCode, 0, $parentLen);
        
        // Format with dots
        if ($parentLen == 2) {
            $parentCode = $parentClean;
        } elseif ($parentLen == 4) {
            $parentCode = substr($parentClean, 0, 2) . '.' . substr($parentClean, 2, 2);
        } elseif ($parentLen == 6) {
            $parentCode = substr($parentClean, 0, 2) . '.' . substr($parentClean, 2, 2) . '.' . substr($parentClean, 4, 2);
        } elseif ($parentLen == 8) {
            $parentCode = substr($parentClean, 0, 2) . '.' . substr($parentClean, 2, 2) . '.' . substr($parentClean, 4, 2) . '.' . substr($parentClean, 6, 2);
        }
    }
    
    $chapterNumber = substr($cleanCode, 0, 2);
    
    $batch[] = [
        'hs_code' => $hsCode,
        'hs_level' => $level,
        'parent_code' => $parentCode,
        'description_id' => $descId ?: '',
        'description_en' => $descEn ?: '',
        'chapter_number' => $chapterNumber,
        'section_number' => null,
        'is_active' => 1,
        'import_batch_id' => $batchId,
        'created_at' => now(),
        'updated_at' => now()
    ];
    
    if (count($batch) >= $batchSize) {
        DB::table('hs_codes')->insert($batch);
        $imported += count($batch);
        $elapsed = round(microtime(true) - $startTime, 1);
        $progress = round(($row / $highestRow) * 100, 1);
        echo "📥 [{$progress}%] Imported {$imported} rows... ({$elapsed}s)\n";
        $batch = [];
    }
}

// Insert remaining batch
if (!empty($batch)) {
    DB::table('hs_codes')->insert($batch);
    $imported += count($batch);
}

$totalTime = round(microtime(true) - $startTime, 2);

echo "\n✅ [SUCCESS] Import completed!\n";
echo "📊 Total imported: {$imported} HS Codes\n";
echo "⏱️  Time elapsed: {$totalTime} seconds\n";
echo "🆔 Batch ID: {$batchId}\n\n";

// Summary statistics
$stats = DB::table('hs_codes')
    ->select('hs_level', DB::raw('COUNT(*) as count'))
    ->groupBy('hs_level')
    ->orderBy('hs_level')
    ->get();

echo "📈 Distribution by level:\n";
foreach ($stats as $stat) {
    $levelName = match($stat->hs_level) {
        2 => 'Chapter',
        4 => 'Heading',
        6 => 'Subheading',
        8 => 'Detail',
        10 => 'Subdetail',
        default => 'Unknown'
    };
    echo "   Level {$stat->hs_level} ({$levelName}): {$stat->count}\n";
}

echo "\n🎉 Done!\n\n";
