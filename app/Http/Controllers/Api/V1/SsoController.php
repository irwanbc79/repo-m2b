<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Generate short-lived SSO token untuk mobile app → web portal.
     * Token valid 60 detik, sekali pakai.
     */
    public function generateToken(Request $request): JsonResponse
    {
        $request->validate([
            'redirect' => 'required|string|max:255',
        ]);

        $user  = $request->user();
        $token = Str::random(64);
        $redirect = $request->input('redirect');

        // Simpan di cache 60 detik — sekali pakai
        Cache::put("sso_token:{$token}", [
            'user_id'  => $user->id,
            'redirect' => $redirect,
        ], now()->addSeconds(60));

        $url = url("/sso/login?token={$token}");

        return response()->json([
            'success' => true,
            'url'     => $url,
        ]);
    }
}
