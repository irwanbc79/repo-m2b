<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppCloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppCloudService $whatsapp
    ) {
    }

    /**
     * GET /api/webhooks/whatsapp — Verifikasi Webhook Resmi Meta Cloud API.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $expectedToken = (string) config('whatsapp.verify_token', '');

        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, (string) $token)) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * POST /api/webhooks/whatsapp — Terima pesan masuk, Coexistence echoes, & status delivery Meta.
     */
    public function handle(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');

        if (!$this->whatsapp->verifySignature($rawBody, $signature)) {
            Log::warning('WhatsAppWebhook: invalid X-Hub-Signature-256');
            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            $processed = $this->whatsapp->ingestWebhookPayload($payload);
            return response()->json([
                'status'    => 'ok',
                'processed' => $processed,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('WhatsAppWebhook error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            // Kembalikan 200 agar Meta tidak retry loop tanpa henti pada payload bermasalah
            return response()->json(['status' => 'error_logged'], 200);
        }
    }
}
