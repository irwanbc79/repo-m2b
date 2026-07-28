<?php

return [
    'username' => env('KIRIMEMAIL_USERNAME'),
    'token' => env('KIRIMEMAIL_TOKEN'),
    'domain_api_key' => env('KIRIMEMAIL_DOMAIN_API_KEY'),
    'domain_api_secret' => env('KIRIMEMAIL_DOMAIN_API_SECRET'),
    'domain' => env('KIRIMEMAIL_DOMAIN'),
    'base_url' => env('KIRIMEMAIL_BASE_URL', 'https://smtp-app.kirim.email'),
];