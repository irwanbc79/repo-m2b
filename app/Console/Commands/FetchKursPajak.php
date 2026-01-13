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
    /**
     * Nama & namespace command
     */
    protected $signature = 'kurs:fetch-pajak';

    /**
     * Deskripsi command
     */
    protected $description = 'Fetch kurs pajak mingguan dari Fiskal Kemenkeu RI dan simpan ke database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = 'https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak';

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (M2B ERP Bot)',
                ])
                ->get($url);

            if ($response->failed()) {
                $this->error('Gagal mengambil data dari Fiskal Kemenkeu');
                Log::error('Fetch kurs pajak gagal: HTTP error');
                return Command::FAILURE;
            }

            libxml_use_internal_errors(true);

            $dom = new DOMDocument();
            $dom->loadHTML($response->body());
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);

            /**
             * Ambil periode berlaku
             */
            $periodNode = $xpath->query("//*[contains(text(),'Tanggal Berlaku') or contains(text(),'Berlaku')]")->item(0);

            if (!$periodNode) {
                $this->error('Periode kurs pajak tidak ditemukan');
                return Command::FAILURE;
            }

            preg_match(
                '/(\d{1,2}\s\w+\s\d{4})\s*(?:s\.d|-\s*)\s*(\d{1,2}\s\w+\s\d{4})/i',
                $periodNode->textContent,
                $matches
            );

            if (count($matches) < 3) {
                $this->error('Format periode kurs pajak tidak dikenali');
                return Command::FAILURE;
            }

            $validFrom  = Carbon::createFromLocaleFormat('d F Y', 'id', trim($matches[1]))->toDateString();
            $validUntil = Carbon::createFromLocaleFormat('d F Y', 'id', trim($matches[2]))->toDateString();

            /**
             * Ambil data kurs dari tabel
             */
            $rows = $xpath->query("//table//tbody/tr");

            if ($rows->length === 0) {
                $this->error('Tabel kurs pajak tidak ditemukan');
                return Command::FAILURE;
            }

            foreach ($rows as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length < 3) continue;

                $currencyName = trim($cols->item(1)->textContent);
                $rateRaw      = trim($cols->item(2)->textContent);

                preg_match('/\((.*?)\)/', $currencyName, $codeMatch);
                $currencyCode = strtoupper($codeMatch[1] ?? substr($currencyName, 0, 3));

                $rate = str_replace(['Rp', '.', ','], ['', '', '.'], $rateRaw);

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
            }

            cache()->forget('kurs_pajak_full_page');

            $this->info('Kurs pajak berhasil diperbarui');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('Fetch kurs pajak exception', ['error' => $e->getMessage()]);
            $this->error('Terjadi error saat fetch kurs pajak');
            return Command::FAILURE;
        }
    }
}
