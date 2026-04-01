<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    private string $token;
    private string $endpoint = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->token = config('services.fonnte.token', env('FONNTE_TOKEN', ''));
    }

    /**
     * Send a WhatsApp message via Fonnte API.
     * Failures are logged and swallowed — never throw.
     */
    public function send(string $phone, string $message): bool
    {
        if (empty($this->token)) {
            Log::warning('FonnteService: FONNTE_TOKEN is not set. Message not sent.', [
                'phone' => $phone,
            ]);
            return false;
        }

        // Normalise phone: strip leading 0, add country code 62 if needed
        $phone = $this->normalisePhone($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->endpoint, [
                'target'  => $phone,
                'message' => $message,
            ]);

            if (! $response->successful()) {
                Log::warning('FonnteService: HTTP error sending WA message.', [
                    'phone'  => $phone,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            $json = $response->json();

            if (isset($json['status']) && $json['status'] === false) {
                Log::warning('FonnteService: Fonnte API returned failure.', [
                    'phone'    => $phone,
                    'response' => $json,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FonnteService: Exception sending WA message.', [
                'phone'   => $phone,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function normalisePhone(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // Convert leading 0 → 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Add 62 if no country code
        if (! str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
