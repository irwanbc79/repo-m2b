<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * shipments.status dulu ENUM('pending','in_progress','customs_released',
 * 'on_board','completed','cancel') — tidak memuat milestone dari getStatusFlow
 * (billing_issued, manifest_submitted, document_collection, physical_inspection,
 * delivery, dst). Akibatnya auto-status terpaksa memakai 'customs_released' &
 * menampilkan status keliru. Ubah ke VARCHAR agar semua milestone valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `shipments` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Normalisasi nilai di luar enum lama agar tak gagal saat revert.
        DB::statement("UPDATE `shipments` SET `status` = 'in_progress'
            WHERE `status` NOT IN ('pending','in_progress','customs_released','on_board','completed','cancel')");
        DB::statement("ALTER TABLE `shipments` MODIFY `status`
            ENUM('pending','in_progress','customs_released','on_board','completed','cancel') NOT NULL DEFAULT 'pending'");
    }
};
