<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kategori Biaya Kasir (Fase 1)
    |--------------------------------------------------------------------------
    |
    | Menggantikan keterangan ketik-bebas ("Pembayaran Lift On", "lift on",
    | "LO") dengan pilihan terstruktur, supaya biaya bisa dilaporkan per jenis
    | layanan dan tidak lagi bergantung pada ejaan orang yang menginput.
    |
    | CATATAN: kategori ini BELUM menentukan akun COA. Pemetaan ke akun masih
    | memakai jalur lama (config/accounting.php) sampai pemetaan COA-nya
    | dibereskan di Fase 4. Untuk sekarang kategori hanya direkam.
    |
    | 'talangan' = biaya yang ditalangi atas nama customer (bukan beban M2B).
    | Dipakai nanti untuk memisahkan piutang talangan dari beban sendiri.
    |
    */
    'expense_categories' => [
        'trucking'          => ['label' => 'Trucking & Angkutan Darat', 'talangan' => false],
        'thc'               => ['label' => 'THC / Terminal Handling', 'talangan' => false],
        'lift_on_off'       => ['label' => 'Lift On / Lift Off', 'talangan' => false],
        'storage_demurrage' => ['label' => 'Storage & Demurrage', 'talangan' => true],
        'sp2'               => ['label' => 'SP2 / Penebusan D.O', 'talangan' => true],
        'doc_fee'           => ['label' => 'Doc Fee & Administrasi Dokumen', 'talangan' => false],
        'coo'               => ['label' => 'COO / Sertifikat Asal', 'talangan' => true],
        'karantina'         => ['label' => 'Karantina & Perizinan', 'talangan' => true],
        'bea_masuk_pajak'   => ['label' => 'Bea Masuk & Pajak Impor', 'talangan' => true],
        'pnbp'              => ['label' => 'PNBP & Biaya Kepabeanan', 'talangan' => true],
        'ops_lapangan'      => ['label' => 'Operasional Lapangan', 'talangan' => false],
        'overhead_kantor'   => ['label' => 'Overhead Kantor', 'talangan' => false],
        'lainnya'           => ['label' => 'Lainnya', 'talangan' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deteksi Dugaan Duplikat (Fase 1)
    |--------------------------------------------------------------------------
    |
    | Transaksi dianggap kemungkinan duplikat bila shipment, lawan transaksi,
    | dan nominalnya sama dalam rentang hari berikut. Sifatnya PERINGATAN, bukan
    | larangan — pembayaran kembar yang sah memang ada.
    |
    */
    'duplicate_window_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Bukti Transaksi (Fase 1)
    |--------------------------------------------------------------------------
    |
    | Transaksi tanpa bukti tidak bisa diverifikasi. Accounting masih bisa
    | menyetujui secara pengecualian, tapi wajib menuliskan alasannya dan
    | alasan itu tercatat di jejak aktivitas.
    |
    */
    'require_proof_on_approval' => true,
];
