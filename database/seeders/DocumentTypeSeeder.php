<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Master Jenis Dokumen M2B (v1.1) — 7 kategori, 2 level.
 * Idempotent (updateOrCreate). doc_type diselaraskan dengan getDocumentTriggers
 * agar auto-status tetap jalan.
 *
 * Tuple: [doc_type, aliases[], category, service, mode, level, responsibility,
 *         conditional, is_status_trigger, is_payment_obligation, has_expiry]
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // 1 · KOMERSIAL (shipment · customer)
            ['Invoice', ['Commercial Invoice'], 'komersial', 'all', null, 'shipment', 'customer', 'selalu', true, false, false],
            ['Packing List', ['PL'], 'komersial', 'all', null, 'shipment', 'customer', 'selalu', true, false, false],
            ['Sales Contract / Purchase Order', ['Kontrak', 'PO'], 'komersial', 'all', null, 'shipment', 'customer', 'situasional', false, false, false],
            ['Shipping Instruction', ['SI'], 'komersial', 'export', null, 'shipment', 'customer', 'selalu', false, false, false],
            ['MSDS', ['Material Safety Data Sheet'], 'komersial', 'all', null, 'shipment', 'customer', 'kondisional', false, false, false],
            ['Certificate of Analysis', ['COA'], 'komersial', 'all', null, 'shipment', 'customer', 'kondisional', false, false, false],

            // 2 · ANGKUT (shipment · sadar-moda)
            ['Bill of Lading', ['B/L', 'Konosemen'], 'angkut', 'all', 'sea', 'shipment', 'customer', 'selalu', true, false, false],
            ['Air Waybill', ['AWB'], 'angkut', 'all', 'air', 'shipment', 'customer', 'selalu', false, false, false],
            ['Surat Muatan Udara', ['SMU', 'AWB domestik'], 'angkut', 'domestic', 'air', 'shipment', 'internal', 'selalu', false, false, false],
            ['Surat Jalan', ['Delivery Note'], 'angkut', 'domestic', 'land', 'shipment', 'internal', 'selalu', true, false, false],
            ['Surat Jalan Pickup', [], 'angkut', 'domestic', 'land', 'shipment', 'internal', 'selalu', true, false, false],
            ['Manifest', ['Manifest Domestik'], 'angkut', 'domestic', null, 'shipment', 'internal', 'selalu', true, false, false],

            // 3 · KEPABEANAN (shipment · umumnya M2B)
            ['PIB', ['Pemberitahuan Impor Barang'], 'kepabeanan', 'import', null, 'shipment', 'internal', 'selalu', false, false, false],
            ['Manifest / BC 1.1', ['Inward Manifest', 'BC 1.1'], 'kepabeanan', 'import', null, 'shipment', 'internal', 'selalu', true, false, false],
            ['Billing Pungutan', [], 'kepabeanan', 'import', null, 'shipment', 'internal', 'selalu', true, false, false],
            ['SPJM', ['Surat Pemberitahuan Jalur Merah'], 'kepabeanan', 'import', null, 'shipment', 'internal', 'kondisional', true, false, false],
            ['SPPB', ['Surat Persetujuan Pengeluaran Barang'], 'kepabeanan', 'import', null, 'shipment', 'internal', 'selalu', true, false, false],
            ['Bukti Bayar BM/PDRI', ['SSPCP'], 'kepabeanan', 'import', null, 'shipment', 'customer', 'selalu', false, false, false],
            ['Faktur Pajak', [], 'kepabeanan', 'import', null, 'shipment', 'internal', 'situasional', false, false, false],
            ['BC 3.0', ['PEB', 'Pemberitahuan Ekspor Barang'], 'kepabeanan', 'export', null, 'shipment', 'internal', 'selalu', true, false, false],
            ['NPE', ['Nota Pelayanan Ekspor'], 'kepabeanan', 'export', null, 'shipment', 'internal', 'selalu', true, false, false],
            ['Billing Bea Keluar', [], 'kepabeanan', 'export', null, 'shipment', 'internal', 'kondisional', true, true, false],
            ['PPB', ['Pemberitahuan Pemeriksaan Barang'], 'kepabeanan', 'export', null, 'shipment', 'internal', 'kondisional', true, false, false],
            ['SPTNP', ['Notul', 'Nota Pembetulan'], 'kepabeanan', 'import', null, 'shipment', 'internal', 'kondisional', false, true, false],
            ['SPKPBK', ['Surat Penetapan Kembali Perhitungan Bea Keluar'], 'kepabeanan', 'export', null, 'shipment', 'internal', 'kondisional', false, true, false],
            ['Billing Dana Sawit', ['BPDPKS'], 'kepabeanan', 'export', null, 'shipment', 'internal', 'kondisional', false, true, false],

            // 4 · LARTAS / IZIN (shipment · kondisional · HS-driven)
            ['SNI', ['Standar Nasional Indonesia'], 'lartas', 'import', null, 'shipment', 'customer', 'kondisional', false, false, false],
            ['LS', ['Laporan Surveyor'], 'lartas', 'all', null, 'shipment', 'customer', 'kondisional', false, false, false],
            ['PI', ['Persetujuan Impor'], 'lartas', 'import', null, 'shipment', 'customer', 'kondisional', false, false, true],
            ['PE', ['Persetujuan Ekspor'], 'lartas', 'export', null, 'shipment', 'customer', 'kondisional', false, false, true],
            ['Izin Edar BPOM', ['ML', 'MD'], 'lartas', 'import', null, 'shipment', 'customer', 'kondisional', false, false, true],
            ['Sertifikat Halal', ['BPJPH'], 'lartas', 'import', null, 'shipment', 'customer', 'kondisional', false, false, true],
            ['Sertifikat/Izin Karantina', ['SPS', 'Phytosanitary', 'Health Certificate'], 'lartas', 'all', null, 'shipment', 'customer', 'kondisional', false, false, false],
            ['COO / SKA', ['Certificate of Origin', 'Form E', 'Form D', 'Form AK'], 'lartas', 'all', null, 'shipment', 'customer', 'kondisional', false, false, false],
            ['Sertifikat Fumigasi', ['Fumigation Certificate'], 'lartas', 'export', null, 'shipment', 'customer', 'kondisional', false, false, false],
            ['Rekomendasi/Izin Kementerian', [], 'lartas', 'all', null, 'shipment', 'customer', 'kondisional', false, false, true],

            // 5 · PENGIRIMAN (shipment · M2B)
            ['Delivery Order', ['DO'], 'pengiriman', 'import', null, 'shipment', 'internal', 'selalu', false, false, false],
            ['SP2', ['Surat Penyerahan Petikemas'], 'pengiriman', 'import', 'sea', 'shipment', 'internal', 'selalu', true, false, false],
            ['Bukti Terima', ['Proof of Delivery'], 'pengiriman', 'all', null, 'shipment', 'internal', 'selalu', true, false, false],

            // 6 · PRINSIP / LEGALITAS PERUSAHAAN (profil · opsional · sekali)
            ['NIB', ['Nomor Induk Berusaha'], 'prinsip', 'all', null, 'profil', 'customer', 'opsional', false, false, false],
            ['NPWP Perusahaan', [], 'prinsip', 'all', null, 'profil', 'customer', 'opsional', false, false, false],
            ['NPWP Direktur', [], 'prinsip', 'all', null, 'profil', 'customer', 'opsional', false, false, false],
            ['Akte Pendirian', ['Akta'], 'prinsip', 'all', null, 'profil', 'customer', 'opsional', false, false, false],
            ['SK Kemenkumham', ['Pengesahan Badan Hukum'], 'prinsip', 'all', null, 'profil', 'customer', 'opsional', false, false, false],
            ['API-U / API-P', ['Angka Pengenal Importir'], 'prinsip', 'import', null, 'profil', 'customer', 'opsional', false, false, true],
            ['NPPBKC', [], 'prinsip', 'import', null, 'profil', 'customer', 'opsional', false, false, true],
            ['KTP / Identitas Direktur', [], 'prinsip', 'all', null, 'profil', 'customer', 'opsional', false, false, false],

            // 7 · LAIN-LAIN (katup pengaman — sudah ada di portal)
            ['Dokumen Pendukung Lainnya', [], 'lainlain', 'all', null, 'shipment', 'customer', 'situasional', false, false, false],
        ];

        $order = 0;
        foreach ($rows as $r) {
            DocumentType::updateOrCreate(
                ['doc_type' => $r[0], 'service_type' => $r[3], 'mode' => $r[4]],
                [
                    'aliases' => $r[1],
                    'category' => $r[2],
                    'level' => $r[5],
                    'responsibility' => $r[6],
                    'conditional' => $r[7],
                    'is_status_trigger' => $r[8],
                    'is_payment_obligation' => $r[9],
                    'has_expiry' => $r[10],
                    'sort_order' => $order++,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('DocumentTypeSeeder: ' . count($rows) . ' jenis dokumen ter-seed.');
    }
}
