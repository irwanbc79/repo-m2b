<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Chart of Accounts (COA) Mapping
    |--------------------------------------------------------------------------
    |
    | Halaman ini memetakan kode akun default untuk jurnal otomatis pada
    | modul invoicing dan kasir. Hal ini mempermudah pemeliharaan jika
    | struktur COA di masa depan disesuaikan.
    |
    */

    'default_accounts' => [
        // Akun Bank Utama (Default: Bank Mandiri)
        'bank' => env('ACC_BANK_CODE', '1103'),

        // Akun Piutang Usaha
        'piutang' => env('ACC_PIUTANG_CODE', '1201'),

        // Akun Pendapatan Jasa Clearance
        'pendapatan' => env('ACC_PENDAPATAN_CODE', '4101'),

        // Akun Uang Muka Customer (Down Payment)
        'uang_muka' => env('ACC_UANG_MUKA_CODE', '2103'),

        // Akun Biaya Operasional / Pembelian
        'biaya_ops' => env('ACC_BIAYA_OPS_CODE', '5101'),

        // Akun Biaya Lain-lain
        'biaya_lain' => env('ACC_BIAYA_LAIN_CODE', '5199'),
    ]
];
