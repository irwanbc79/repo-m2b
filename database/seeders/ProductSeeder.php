<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ========================================
            // IMPORT SERVICES
            // ========================================
            
            // Full Service Import
            [
                'code' => 'IMP-FS-001',
                'name' => 'Import Clearance (Full Service)',
                'category' => 'import',
                'sub_category' => 'full_service',
                'service_type' => 'service',
                'description' => 'Layanan import lengkap: PIB, Clearance, dan Delivery',
                'default_price' => 5000000,
                'sort_order' => 10,
            ],
            [
                'code' => 'IMP-FS-002',
                'name' => 'Import + Door Delivery',
                'category' => 'import',
                'sub_category' => 'full_service',
                'service_type' => 'service',
                'description' => 'Import clearance dengan pengiriman ke alamat customer',
                'default_price' => 6000000,
                'sort_order' => 11,
            ],
            
            // PIB Only
            [
                'code' => 'IMP-PIB-001',
                'name' => 'Online PIB Processing Only',
                'category' => 'import',
                'sub_category' => 'pib_only',
                'service_type' => 'service',
                'description' => 'Pengajuan PIB online tanpa clearance fisik',
                'default_price' => 1500000,
                'sort_order' => 20,
            ],
            [
                'code' => 'IMP-PIB-002',
                'name' => 'PIB + Document Handling',
                'category' => 'import',
                'sub_category' => 'pib_only',
                'service_type' => 'service',
                'description' => 'PIB online + pengurusan dokumen import',
                'default_price' => 2000000,
                'sort_order' => 21,
            ],
            
            // Import Components
            [
                'code' => 'IMP-CLR-001',
                'name' => 'Custom Clearance (Import)',
                'category' => 'import',
                'sub_category' => 'clearance_component',
                'service_type' => 'service',
                'description' => 'Jasa pengurusan kepabeanan import',
                'default_price' => 3000000,
                'sort_order' => 30,
            ],
            [
                'code' => 'IMP-DOC-001',
                'name' => 'Import Document Processing',
                'category' => 'import',
                'sub_category' => 'clearance_component',
                'service_type' => 'service',
                'description' => 'Pengurusan dokumen import (BC 1.1, SPPB, dll)',
                'default_price' => 1000000,
                'sort_order' => 31,
            ],
            
            // Import Additional
            [
                'code' => 'IMP-FWD-001',
                'name' => 'Import Forwarding Fee',
                'category' => 'import',
                'sub_category' => 'additional',
                'service_type' => 'service',
                'description' => 'Biaya koordinasi pengiriman import',
                'default_price' => 500000,
                'sort_order' => 40,
            ],
            [
                'code' => 'IMP-TRK-001',
                'name' => 'Import Trucking/Delivery',
                'category' => 'import',
                'sub_category' => 'additional',
                'service_type' => 'service',
                'description' => 'Pengiriman barang import dari pelabuhan',
                'default_price' => 2000000,
                'sort_order' => 41,
            ],
            
            // ========================================
            // EXPORT SERVICES
            // ========================================
            
            // Full Service Export
            [
                'code' => 'EXP-FS-001',
                'name' => 'Export Clearance (Full Service)',
                'category' => 'export',
                'sub_category' => 'full_service',
                'service_type' => 'service',
                'description' => 'Layanan export lengkap: PEB, Clearance, Stuffing',
                'default_price' => 4000000,
                'sort_order' => 110,
            ],
            [
                'code' => 'EXP-FS-002',
                'name' => 'Export + Pickup Service',
                'category' => 'export',
                'sub_category' => 'full_service',
                'service_type' => 'service',
                'description' => 'Export clearance dengan pickup dari lokasi customer',
                'default_price' => 5000000,
                'sort_order' => 111,
            ],
            
            // PEB Only
            [
                'code' => 'EXP-PEB-001',
                'name' => 'Online PEB Processing Only',
                'category' => 'export',
                'sub_category' => 'peb_only',
                'service_type' => 'service',
                'description' => 'Pengajuan PEB online tanpa clearance fisik',
                'default_price' => 1200000,
                'sort_order' => 120,
            ],
            [
                'code' => 'EXP-PEB-002',
                'name' => 'PEB + Document Handling',
                'category' => 'export',
                'sub_category' => 'peb_only',
                'service_type' => 'service',
                'description' => 'PEB online + pengurusan dokumen export',
                'default_price' => 1800000,
                'sort_order' => 121,
            ],
            
            // Export Components
            [
                'code' => 'EXP-CLR-001',
                'name' => 'Custom Clearance (Export)',
                'category' => 'export',
                'sub_category' => 'clearance_component',
                'service_type' => 'service',
                'description' => 'Jasa pengurusan kepabeanan export',
                'default_price' => 2500000,
                'sort_order' => 130,
            ],
            [
                'code' => 'EXP-STF-001',
                'name' => 'Export Stuffing Fee',
                'category' => 'export',
                'sub_category' => 'clearance_component',
                'service_type' => 'service',
                'description' => 'Biaya stuffing container untuk export',
                'default_price' => 1500000,
                'sort_order' => 131,
            ],
            
            // ========================================
            // DOMESTIC SERVICES
            // ========================================
            [
                'code' => 'DOM-SHP-001',
                'name' => 'Domestic Shipping',
                'category' => 'domestic',
                'sub_category' => 'shipping',
                'service_type' => 'service',
                'description' => 'Pengiriman domestik antar kota',
                'default_price' => 1000000,
                'sort_order' => 210,
            ],
            [
                'code' => 'DOM-TRK-001',
                'name' => 'Trucking Service (Local)',
                'category' => 'domestic',
                'sub_category' => 'trucking',
                'service_type' => 'service',
                'description' => 'Jasa trucking lokal dalam kota',
                'default_price' => 800000,
                'sort_order' => 211,
            ],
            [
                'code' => 'DOM-WH-001',
                'name' => 'Warehouse Storage',
                'category' => 'domestic',
                'sub_category' => 'warehouse',
                'service_type' => 'service',
                'description' => 'Biaya penyimpanan gudang (per hari)',
                'default_price' => 50000,
                'sort_order' => 212,
            ],
            
            // ========================================
            // CONSULTATION SERVICES
            // ========================================
            [
                'code' => 'CON-HS-001',
                'name' => 'HS Code Classification',
                'category' => 'consultation',
                'sub_category' => 'classification',
                'service_type' => 'service',
                'description' => 'Konsultasi dan penentuan kode HS',
                'default_price' => 500000,
                'sort_order' => 310,
            ],
            [
                'code' => 'CON-DOC-001',
                'name' => 'Document Legalization',
                'category' => 'consultation',
                'sub_category' => 'documentation',
                'service_type' => 'service',
                'description' => 'Legalisasi dokumen import/export',
                'default_price' => 750000,
                'sort_order' => 311,
            ],
            [
                'code' => 'CON-SKA-001',
                'name' => 'Certificate of Origin (SKA)',
                'category' => 'consultation',
                'sub_category' => 'certification',
                'service_type' => 'service',
                'description' => 'Pengurusan Surat Keterangan Asal (SKA)',
                'default_price' => 1000000,
                'sort_order' => 312,
            ],
            
            // ========================================
            // REIMBURSEMENT ITEMS
            // ========================================
            [
                'code' => 'REIMB-DUTY',
                'name' => 'Bea Masuk (Reimburse)',
                'category' => 'reimbursement',
                'sub_category' => 'government_fee',
                'service_type' => 'reimbursement',
                'description' => 'Bea masuk yang dibayarkan ke pemerintah',
                'default_price' => 0,
                'sort_order' => 410,
            ],
            [
                'code' => 'REIMB-TAX-PPN',
                'name' => 'PPN Import (Reimburse)',
                'category' => 'reimbursement',
                'sub_category' => 'government_fee',
                'service_type' => 'reimbursement',
                'description' => 'Pajak Pertambahan Nilai import',
                'default_price' => 0,
                'sort_order' => 411,
            ],
            [
                'code' => 'REIMB-TAX-PPH',
                'name' => 'PPh Import (Reimburse)',
                'category' => 'reimbursement',
                'sub_category' => 'government_fee',
                'service_type' => 'reimbursement',
                'description' => 'Pajak Penghasilan import',
                'default_price' => 0,
                'sort_order' => 412,
            ],
            [
                'code' => 'REIMB-PORT',
                'name' => 'Port Charges (Reimburse)',
                'category' => 'reimbursement',
                'sub_category' => 'port_fee',
                'service_type' => 'reimbursement',
                'description' => 'Biaya pelabuhan (THC, Lift On/Off, dll)',
                'default_price' => 0,
                'sort_order' => 413,
            ],
            [
                'code' => 'REIMB-DEMURRAGE',
                'name' => 'Container Demurrage (Reimburse)',
                'category' => 'reimbursement',
                'sub_category' => 'container_fee',
                'service_type' => 'reimbursement',
                'description' => 'Biaya keterlambatan pengembalian container',
                'default_price' => 0,
                'sort_order' => 414,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('✅ ' . count($products) . ' products seeded successfully!');
    }
}
