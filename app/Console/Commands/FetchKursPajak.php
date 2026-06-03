<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TaxExchangeRate;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class FetchKursPajak extends Command
{
    protected $signature = 'kurs:fetch-pajak {--debug : Tampilkan raw HTML dan teks untuk diagnosa}';

    protected $description = 'Fetch kurs pajak mingguan dari Fiskal Kemenkeu RI dan simpan ke database';

    // Mapping bulan Indonesia ke nomor (fallback jika locale Carbon tidak tersedia)
    private array $bulanId = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        // Singkatan
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'jun' => 6, 'jul' => 7, 'agu' => 8, 'ags' => 8,
        'sep' => 9, 'okt' => 10, 'nov' => 11, 'des' => 12,
    ];

    public function handle(): int
    {
        $url = 'https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak';

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (M2B Portal Bot/1.0)'])
                ->get($url);

            if ($response->failed()) {
                $this->error('Gagal mengambil data dari Fiskal Kemenkeu (HTTP ' . $response->status() . ')');
                Log::error('Fetch kurs pajak gagal: HTTP ' . $response->status());
                return Command::FAILURE;
            }

            $html = $response->body();

            if ($this->option('debug')) {
                $this->info('=== DEBUG: Response length: ' . strlen($html) . ' bytes ===');
            }

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);

            // --- CARI TEKS PERIODE ---
            $periodText = $this->findPeriodText($xpath);

            if ($this->option('debug')) {
                $this->info('=== DEBUG: Period text found: ' . ($periodText ?? 'NULL') . ' ===');
            }

            if (!$periodText) {
                $this->error('Periode kurs pajak tidak ditemukan di halaman Kemenkeu');
                Log::error('Fetch kurs pajak: periode tidak ditemukan', ['url' => $url]);
                return Command::FAILURE;
            }

            // --- PARSE TANGGAL ---
            [$validFrom, $validUntil] = $this->parsePeriod($periodText);

            if (!$validFrom || !$validUntil) {
                $this->error('Format periode tidak dikenali: ' . substr($periodText, 0, 200));
                Log::error('Fetch kurs pajak: format periode gagal di-parse', ['text' => $periodText]);
                return Command::FAILURE;
            }

            $this->info("Periode: {$validFrom} s.d. {$validUntil}");

            // --- AMBIL DATA TABEL ---
            $rows = $xpath->query("//table//tbody/tr");

            if ($rows->length === 0) {
                $this->error('Tabel kurs pajak tidak ditemukan di halaman');
                return Command::FAILURE;
            }

            $count = 0;
            foreach ($rows as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length < 3) continue;

                $currencyName = trim($cols->item(1)->textContent);
                $rateRaw      = trim($cols->item(2)->textContent);

                if (empty($currencyName) || empty($rateRaw)) continue;

                preg_match('/\((.*?)\)/', $currencyName, $codeMatch);
                $currencyCode = strtoupper($codeMatch[1] ?? substr($currencyName, 0, 3));

                $rate = (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9.,]/', '', $rateRaw));

                if ($rate <= 0) continue;

                TaxExchangeRate::updateOrCreate(
                    [
                        'currency_code' => $currencyCode,
                        'valid_from'    => $validFrom,
                        'valid_until'   => $validUntil,
                    ],
                    [
                        'currency_name' => $currencyName,
                        'rate'          => $rate,
                    ]
                );

                $count++;
            }

            cache()->forget('kurs_pajak_full_page');
            cache()->forget('kurs_pajak_customer_active_' . now()->format('Y-m-d'));
            cache()->forget('admin_usd_rate');
            cache()->forget('customer_usd_rate_header');

            $this->info("Kurs pajak berhasil diperbarui: {$count} mata uang.");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Fetch kurs pajak exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Cari teks periode dari berbagai kemungkinan struktur HTML Kemenkeu.
     */
    private function findPeriodText(DOMXPath $xpath): ?string
    {
        // Strategi 1: elemen yang langsung mengandung teks "Berlaku" + angka tahun
        $selectors = [
            "//*[contains(text(),'Tanggal Berlaku')]",
            "//*[contains(text(),'Berlaku')]",
            "//*[contains(translate(text(),'BERLAKU','berlaku'),'berlaku')]",
            "//*[contains(text(),'Periode')]",
        ];

        foreach ($selectors as $sel) {
            $nodes = $xpath->query($sel);
            foreach ($nodes as $node) {
                $text = preg_replace('/\s+/', ' ', trim($node->textContent));
                // Hanya ambil jika ada angka tahun (misal 2026)
                if (preg_match('/20\d{2}/', $text)) {
                    return $text;
                }
                // Coba parent node juga
                if ($node->parentNode) {
                    $parentText = preg_replace('/\s+/', ' ', trim($node->parentNode->textContent));
                    if (preg_match('/20\d{2}/', $parentText) && strlen($parentText) < 300) {
                        return $parentText;
                    }
                }
            }
        }

        // Strategi 2: cari teks yang langsung mengandung pola tanggal Indonesia
        $allNodes = $xpath->query("//*[not(self::script) and not(self::style)]");
        foreach ($allNodes as $node) {
            if ($node->childNodes->length > 5) continue; // skip container besar
            $text = preg_replace('/\s+/', ' ', trim($node->textContent));
            if (
                strlen($text) < 200 &&
                preg_match('/\d{1,2}\s+\w+\s+20\d{2}/i', $text) &&
                preg_match('/(?:s\.d|s\/d|sampai|hingga|–|—|-)/i', $text)
            ) {
                return $text;
            }
        }

        return null;
    }

    /**
     * Parse teks periode menjadi [valid_from, valid_until] dalam format Y-m-d.
     * Menangani berbagai format separator dan bulan.
     */
    private function parsePeriod(string $text): array
    {
        // Normalisasi: ganti newline/tab jadi spasi, compress multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);

        // Pola tanggal: "3 Juni 2026" atau "03 Juni 2026" atau "03/06/2026"
        $datePart = '(\d{1,2}[\s\/\-]\w+[\s\/\-]\d{4})';

        // Separator: s.d. / s.d / s/d / – / — / - / sampai / hingga
        $sep = '(?:s\.d\.?|s\/d|sampai\s+dengan|sampai|hingga|[–—]|\s-\s)';

        $pattern = '/' . $datePart . '\s*' . $sep . '\s*' . $datePart . '/iu';

        if (!preg_match($pattern, $text, $matches)) {
            return [null, null];
        }

        $from  = $this->parseDate(trim($matches[1]));
        $until = $this->parseDate(trim($matches[2]));

        return [$from, $until];
    }

    /**
     * Parse satu string tanggal ke format Y-m-d.
     * Mendukung: "3 Juni 2026", "03/06/2026", "3-6-2026", dll.
     */
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);

        // Format "03 Juni 2026" atau "3 Juni 2026"
        if (preg_match('/^(\d{1,2})\s+(\w+)\s+(\d{4})$/iu', $raw, $m)) {
            $bulan = $this->bulanId[mb_strtolower($m[2])] ?? null;
            if ($bulan) {
                return Carbon::createFromDate((int)$m[3], $bulan, (int)$m[1])->toDateString();
            }
            // Fallback: Carbon locale
            try {
                return Carbon::createFromLocaleFormat('d F Y', 'id', $raw)->toDateString();
            } catch (\Throwable) {}
        }

        // Format "03/06/2026" atau "3-6-2026"
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $raw, $m)) {
            return Carbon::createFromDate((int)$m[3], (int)$m[2], (int)$m[1])->toDateString();
        }

        // Last resort: Carbon::parse
        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {}

        return null;
    }
}
