<?php

namespace App\Services\Mandiri;

use App\Models\BankStatement;
use App\Models\Invoice;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MandiriApiService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $privateKey;
    protected string $accountNumber;
    protected string $partnerId;
    protected string $channelId;

    public function __construct()
    {
        $this->baseUrl       = rtrim(config('mandiri.base_url', 'https://openapi-sandbox.bankmandiri.co.id'), '/');
        $this->clientId      = config('mandiri.client_id', '');
        $this->clientSecret  = config('mandiri.client_secret', '');
        $this->privateKey    = config('mandiri.private_key', '');
        $this->accountNumber = config('mandiri.account_number', '');
        $this->partnerId     = config('mandiri.partner_id', '');
        $this->channelId     = config('mandiri.channel_id', '95051');
    }

    /**
     * 1. Mendapatkan B2B Access Token dari Bank Mandiri (dengan Caching)
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'mandiri_b2b_access_token_' . md5($this->clientId);

        return Cache::remember($cacheKey, config('mandiri.token_cache_ttl', 800), function () {
            $timestamp    = now()->format('Y-m-d\TH:i:sP');
            $signature    = MandiriSignatureEngine::generateAuthSignature($this->clientId, $timestamp, $this->privateKey);
            $endpointPath = '/openapi/auth/v2.0/access-token/b2b';

            $headers = [
                'X-CLIENT-KEY' => $this->clientId,
                'X-TIMESTAMP'  => $timestamp,
                'X-SIGNATURE'  => $signature,
                'Content-Type' => 'application/json',
            ];

            $body = [
                'grantType' => 'client_credentials',
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($this->baseUrl . $endpointPath, $body);

            if ($response->failed()) {
                Log::error('Gagal mengambil Access Token Bank Mandiri', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new Exception('Mandiri API Token Failure: HTTP ' . $response->status() . ' - ' . $response->body());
            }

            $accessToken = $response->json('accessToken');
            if (empty($accessToken)) {
                throw new Exception('Mandiri API Token Response kosong.');
            }

            return $accessToken;
        });
    }

    /**
     * 2. Tarik Mutasi Rekening (Bank Statement)
     *
     * @param string|Carbon|null $startDate Format YYYY-MM-DD
     * @param string|Carbon|null $endDate Format YYYY-MM-DD
     * @return array List transaksi mutasi
     */
    public function getBankStatement($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : now()->subDays(1)->format('Y-m-d');
        $endDate   = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : now()->format('Y-m-d');

        $accessToken  = $this->getAccessToken();
        $timestamp    = now()->format('Y-m-d\TH:i:sP');
        $externalId   = (string) Str::uuid();
        $endpointPath = '/openapi/v1.0/bank-statement';

        $body = [
            'bankCardToken' => $this->accountNumber,
            'startingDate'  => $startDate,
            'endingDate'    => $endDate,
        ];

        $signature = MandiriSignatureEngine::generateServiceSignature(
            'POST',
            $endpointPath,
            $accessToken,
            $body,
            $timestamp,
            $this->clientSecret
        );

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'X-TIMESTAMP'     => $timestamp,
            'X-SIGNATURE'     => $signature,
            'X-PARTNER-ID'    => $this->partnerId ?: $this->clientId,
            'X-EXTERNAL-ID'   => $externalId,
            'CHANNEL-ID'      => $this->channelId,
            'Content-Type'    => 'application/json',
        ];

        $response = Http::withHeaders($headers)
            ->timeout(45)
            ->post($this->baseUrl . $endpointPath, $body);

        if ($response->failed()) {
            Log::error('Mandiri Get Bank Statement Gagal', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new Exception('Mandiri Bank Statement API Error: ' . $response->body());
        }

        $responseData = $response->json();
        $detailData   = $responseData['detailData'] ?? $responseData['data'] ?? [];

        return $this->processAndStoreStatements($detailData);
    }

    /**
     * 3. Menyimpan & Memproses Mutasi Rekening ke Database + Trigger Rekonsiliasi Otomatis
     */
    public function processAndStoreStatements(array $items): array
    {
        $savedStatements = [];

        foreach ($items as $item) {
            $refNo = $item['referenceNumber'] ?? $item['transactionId'] ?? $item['detailTransactionId'] ?? null;
            if (!$refNo) {
                // Generate fallback reference jika bank tidak memberikan ID unik
                $refNo = md5(($item['transactionDate'] ?? '') . ($item['amount']['value'] ?? '0') . ($item['description'] ?? ''));
            }

            $amount = (float) ($item['amount']['value'] ?? $item['amount'] ?? 0);
            $type   = strtoupper($item['type'] ?? $item['transactionType'] ?? 'CR'); // 'CR' atau 'DB'
            if (str_contains(strtolower($item['description'] ?? ''), 'kredit') || str_contains(strtolower($type), 'cr')) {
                $type = 'CR';
            }

            $statement = BankStatement::updateOrCreate(
                ['reference_number' => $refNo],
                [
                    'bank_name'        => 'MANDIRI',
                    'account_number'   => $this->accountNumber,
                    'transaction_date' => Carbon::parse($item['transactionDate'] ?? now()),
                    'booking_date'     => isset($item['bookingDate']) ? Carbon::parse($item['bookingDate']) : null,
                    'type'             => $type,
                    'amount'           => $amount,
                    'balance'          => isset($item['balance']['value']) ? (float) $item['balance']['value'] : null,
                    'description'      => $item['description'] ?? $item['remark'] ?? '',
                    'raw_payload'      => $item,
                ]
            );

            $savedStatements[] = $statement;
        }

        // Jalankan pencocokan otomatis invoice (Auto Reconciliation)
        $this->autoReconcileUnmatched();

        return $savedStatements;
    }

    /**
     * 4. Logika Auto-Reconciliation Pembayaran Invoice M2B
     */
    public function autoReconcileUnmatched(): int
    {
        $unreconciled = BankStatement::credit()->unreconciled()->get();
        $reconciledCount = 0;

        foreach ($unreconciled as $statement) {
            // A. Cari Invoice berdasarkan Kode Unik / Nominal Exact (Unpaid / Partial)
            $invoice = Invoice::whereIn('status', ['unpaid', 'partially_paid', 'pending'])
                ->where('grand_total', $statement->amount)
                ->first();

            // B. Jika tidak ketemu dari nominal, cari via Nomor Invoice di Description (misal: "INV-2026-001")
            if (!$invoice && !empty($statement->description)) {
                preg_match('/INV[-\/]?\d{4}[-\/]?\d{3,5}/i', $statement->description, $matches);
                if (!empty($matches[0])) {
                    $matchedInvNo = $matches[0];
                    $invoice = Invoice::where('invoice_number', 'LIKE', '%' . $matchedInvNo . '%')
                        ->whereIn('status', ['unpaid', 'partially_paid', 'pending'])
                        ->first();
                }
            }

            // C. Jika Invoice ditemukan, eksekusi Pelunasan Otomatis
            if ($invoice) {
                $statement->update([
                    'is_reconciled' => true,
                    'invoice_id'    => $invoice->id,
                    'reconciled_at' => now(),
                ]);

                $invoice->update([
                    'status'        => 'paid',
                    'total_paid'    => $invoice->grand_total,
                    'payment_date'  => $statement->transaction_date,
                    'payment_notes' => 'Otomatis dilunasi via Mutasi Mandiri (Ref: ' . $statement->reference_number . ')',
                ]);

                Log::info("Auto Reconciliation Mandiri Berhasil! Invoice {$invoice->invoice_number} dilunasi via Mutasi Ref: {$statement->reference_number}");
                $reconciledCount++;
            }
        }

        return $reconciledCount;
    }
}
