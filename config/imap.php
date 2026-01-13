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
            'validate_cert' => false,
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
        ],

        'import' => [
            'host'  => env('IMAP_IMPORT_HOST'),
            'port'  => env('IMAP_IMPORT_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => false,
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
        ],

        'export' => [
            'host'  => env('IMAP_EXPORT_HOST'),
            'port'  => env('IMAP_EXPORT_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => false,
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
        ],

        'finance' => [
            'host'  => env('IMAP_FINANCE_HOST'),
            'port'  => env('IMAP_FINANCE_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => false,
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
        ],
        'gmail' => [
            'host'  => env('IMAP_GMAIL_HOST'),
            'port'  => env('IMAP_GMAIL_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => false,
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
        ],
        'outlook' => [
            'host'  => env('IMAP_OUTLOOK_HOST'),
            'port'  => env('IMAP_OUTLOOK_PORT'),
            'protocol'  => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => false,
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
