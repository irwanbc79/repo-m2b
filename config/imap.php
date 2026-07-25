<?php

return [

    'default' => env('IMAP_DEFAULT_ACCOUNT', 'sales'),

    'date_format' => 'd-M-Y',

    'accounts' => [

        'sales' => [
            'host'  => env('IMAP_SALES_HOST'),
            'port'  => env('IMAP_SALES_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_SALES_USER'),
            'password' => env('IMAP_SALES_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_SALES_HOST', env('MAIL_HOST', 'mx.kerjamail.co')),
                'port' => env('SMTP_SALES_PORT', env('MAIL_PORT', 587)),
                'encryption' => env('SMTP_SALES_ENCRYPTION', 'tls'),
            ],
        ],

        'import' => [
            'host'  => env('IMAP_IMPORT_HOST'),
            'port'  => env('IMAP_IMPORT_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_IMPORT_USER'),
            'password' => env('IMAP_IMPORT_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_IMPORT_HOST', env('MAIL_HOST', 'mx.kerjamail.co')),
                'port' => env('SMTP_IMPORT_PORT', env('MAIL_PORT', 587)),
                'encryption' => env('SMTP_IMPORT_ENCRYPTION', 'tls'),
            ],
        ],

        'export' => [
            'host'  => env('IMAP_EXPORT_HOST'),
            'port'  => env('IMAP_EXPORT_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_EXPORT_USER'),
            'password' => env('IMAP_EXPORT_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_EXPORT_HOST', env('MAIL_HOST', 'mx.kerjamail.co')),
                'port' => env('SMTP_EXPORT_PORT', env('MAIL_PORT', 587)),
                'encryption' => env('SMTP_EXPORT_ENCRYPTION', 'tls'),
            ],
        ],

        'finance' => [
            'host'  => env('IMAP_FINANCE_HOST'),
            'port'  => env('IMAP_FINANCE_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_FINANCE_USER'),
            'password' => env('IMAP_FINANCE_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_FINANCE_HOST', env('MAIL_HOST', 'mx.kerjamail.co')),
                'port' => env('SMTP_FINANCE_PORT', env('MAIL_PORT', 587)),
                'encryption' => env('SMTP_FINANCE_ENCRYPTION', 'tls'),
            ],
        ],
        'gmail' => [
            'host'  => env('IMAP_GMAIL_HOST'),
            'port'  => env('IMAP_GMAIL_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_GMAIL_USER'),
            'password' => env('IMAP_GMAIL_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            // Gmail SMTP beda host dari IMAP-nya (imap.gmail.com) — WAJIB pakai App Password
            // (bukan password akun biasa) kalau 2FA aktif di akun ini, dipakai jg oleh IMAP di atas.
            'smtp' => [
                'host' => env('SMTP_GMAIL_HOST', 'smtp.gmail.com'),
                'port' => env('SMTP_GMAIL_PORT', 587),
                'encryption' => env('SMTP_GMAIL_ENCRYPTION', 'tls'),
            ],
        ],
        'pajak' => [
            'host'  => env('IMAP_PAJAK_HOST'),
            'port'  => env('IMAP_PAJAK_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_PAJAK_USER'),
            'password' => env('IMAP_PAJAK_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_PAJAK_HOST', 'smtp.gmail.com'),
                'port' => env('SMTP_PAJAK_PORT', 587),
                'encryption' => env('SMTP_PAJAK_ENCRYPTION', 'tls'),
            ],
        ],
        'outlook' => [
            'host'  => env('IMAP_OUTLOOK_HOST'),
            'port'  => env('IMAP_OUTLOOK_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_OUTLOOK_USER'),
            'password' => env('IMAP_OUTLOOK_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_OUTLOOK_HOST', 'smtp.office365.com'),
                'port' => env('SMTP_OUTLOOK_PORT', 587),
                'encryption' => env('SMTP_OUTLOOK_ENCRYPTION', 'tls'),
            ],
        ],
        'shipping' => [
            'host'  => env('IMAP_SHIPPING_HOST'),
            'port'  => env('IMAP_SHIPPING_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_SHIPPING_USER'),
            'password' => env('IMAP_SHIPPING_PASS'),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => 30,
            'extensions' => [],
            'smtp' => [
                'host' => env('SMTP_SHIPPING_HOST', env('MAIL_HOST', 'mx.kerjamail.co')),
                'port' => env('SMTP_SHIPPING_PORT', env('MAIL_PORT', 587)),
                'encryption' => env('SMTP_SHIPPING_ENCRYPTION', 'tls'),
            ],
        ],
    ],

    'options' => [
        'delimiter' => '/',
        'fetch' => \Webklex\PHPIMAP\IMAP::FT_UID,
        'fetch_body' => true,
        'fetch_attachment' => true,
        'fetch_flags' => true,
        'message_key' => 'id',
        'fetch_order' => 'asc',
        'open' => [
            'DISABLE_AUTHENTICATOR' => ['GSSAPI','NTLM'],
        ],
        'decoder' => [
            'message' => [
                'subject' => 'utf-8',
                'from' => 'utf-8',
                'to' => 'utf-8',
            ],
            'attachment' => [
                'name' => 'utf-8',
            ]
        ],
        'events' => [
            'message' => [
                'new' => \Webklex\IMAP\Events\MessageNewEvent::class,
                'moved' => \Webklex\IMAP\Events\MessageMovedEvent::class,
                'copied' => \Webklex\IMAP\Events\MessageCopiedEvent::class,
                'deleted' => \Webklex\IMAP\Events\MessageDeletedEvent::class,
                'restored' => \Webklex\IMAP\Events\MessageRestoredEvent::class,
            ],
            'folder' => [
                'new' => \Webklex\IMAP\Events\FolderNewEvent::class,
                'moved' => \Webklex\IMAP\Events\FolderMovedEvent::class,
                'deleted' => \Webklex\IMAP\Events\FolderDeletedEvent::class,
            ],
            'flag' => [
                'new' => \Webklex\IMAP\Events\FlagNewEvent::class,
                'deleted' => \Webklex\IMAP\Events\FlagDeletedEvent::class,
            ],
        ],
        'masks' => [
            'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
            'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class
        ]
    ],

    'masks' => [
        'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
        'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class
    ],
];
