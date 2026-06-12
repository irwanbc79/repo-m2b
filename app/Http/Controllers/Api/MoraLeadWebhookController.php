<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MoraLeadNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MoraLeadWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $secret = config('services.mora.portal_secret');
        if ($secret && $request->header('X-Mora-Secret') !== $secret) {
            Log::warning('MORA webhook: invalid secret', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'remote_lead_id'   => 'nullable|integer',
            'name'             => 'required|string|max:100',
            'company'          => 'nullable|string|max:100',
            'phone'            => 'required|string|max:20',
            'email'            => 'nullable|email|max:100',
            'score'            => 'nullable|string|in:hot,warm,cold',
            'source'           => 'nullable|string|in:mora_chat,cs_form',
            'service_interest' => 'nullable|string|max:30',
            'summary'          => 'nullable|string',
            'chat_history'     => 'nullable|array',
        ]);

        MoraLeadNotification::create($data);

        return response()->json(['success' => true]);
    }
}
