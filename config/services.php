<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'https://portal.m2b.co.id/auth/google/callback'),
    ],

    'mora' => [
        'portal_secret' => env('MORA_PORTAL_WEBHOOK_SECRET'),
    ],

    // Webhook peristiwa pengiriman dari Kirim Email.
    // Token ditaruh di path URL (bukan header) karena API mereka tidak bisa
    // menangani custom header — lihat KirimEmailWebhookController.
    'kirimemail' => [
        'webhook_token' => env('KIRIMEMAIL_WEBHOOK_TOKEN'),
    ],

    // ===== AI Lartas (F4) — HYBRID multi-provider =====
    // Isi salah satu / beberapa key. Sistem auto-pakai provider yang tersedia
    // (urutan 'order'), dengan fallback bila satu gagal. Degrade aman bila kosong.
    'ai_lartas' => [
        // 'auto' = pilih provider pertama yang ada key-nya (urutan di bawah).
        // Atau paksa: 'anthropic' | 'openai' | 'gemini' | 'deepseek'.
        'provider' => env('AI_LARTAS_PROVIDER', 'auto'),
        'order'    => ['anthropic', 'openai', 'gemini', 'deepseek'],
        'fallback' => (bool) env('AI_LARTAS_FALLBACK', true),
    ],

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
    ],
    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'gemini' => [
        'key'   => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],
    'deepseek' => [
        'key'   => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
    ],

];
