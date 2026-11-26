<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API & Business App Coexistence Configuration
    |--------------------------------------------------------------------------
    | Isi nilai di .env setelah akun Meta Business Portfolio PT Mora Multi Berkah
    | selesai diverifikasi. Selama belum diisi, MORA Leads tetap berjalan normal
    | dengan mode fallback wa.me + Shared Inbox siap menerima webhook kapan saja.
    */
    'enabled'         => (bool) env('WHATSAPP_CLOUD_ENABLED', false),
    'api_version'     => env('WHATSAPP_API_VERSION', 'v21.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    'access_token'    => env('WHATSAPP_ACCESS_TOKEN', ''),
    'verify_token'    => env('WHATSAPP_VERIFY_TOKEN', ''),
    'app_secret'      => env('WHATSAPP_APP_SECRET', ''),
    'business_number' => env('WHATSAPP_BUSINESS_NUMBER', ''),
];
