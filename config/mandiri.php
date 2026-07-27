<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bank Mandiri SNAP Open API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi integrasi API Bank Mandiri (Mandiri Cash Management / SNAP BI)
    | untuk sinkronisasi saldo dan mutasi rekening M2B secara otomatis.
    |
    */

    'base_url' => env('MANDIRI_API_BASE_URL', 'https://openapi-sandbox.bankmandiri.co.id'),

    'client_id'     => env('MANDIRI_CLIENT_ID', ''),
    'client_secret' => env('MANDIRI_CLIENT_SECRET', ''),

    /*
    | Partner RSA Private Key (Format PEM string atau Base64)
    | Digunakan untuk membuat X-SIGNATURE Asymmetric pada endpoint Token B2B.
    */
    'private_key'   => env('MANDIRI_PRIVATE_KEY', ''),
    
    /*
    | Rekening Utama M2B
    */
    'account_number' => env('MANDIRI_ACCOUNT_NUMBER', ''),
    'partner_id'     => env('MANDIRI_PARTNER_ID', ''),
    'channel_id'     => env('MANDIRI_CHANNEL_ID', '95051'),

    /*
    | Cache duration untuk Access Token (dalam detik). Max lifetime Mandiri = 900s (15 menit).
    */
    'token_cache_ttl' => env('MANDIRI_TOKEN_CACHE_TTL', 800),

    /*
    | Mode Pengujian / Production
    */
    'debug' => env('MANDIRI_DEBUG', true),
];
