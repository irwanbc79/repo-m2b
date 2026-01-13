<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah kolom status sudah ada
        $hasStatusColumn = Schema::hasColumn('shipments', 'status');
        
        if ($hasStatusColumn) {
            // Update enum status untuk menambahkan 'cancel'
            DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'completed', 'cancel') DEFAULT 'pending'");
        }
        
        // Tambah kolom tracking pembatalan jika belum ada
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('shipments', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
                $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('shipments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
            }
            
            if (Schema::hasColumn('shipments', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            
            if (Schema::hasColumn('shipments', 'cancelled_by')) {
                $table->dropColumn('cancelled_by');
            }
            
            if (Schema::hasColumn('shipments', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });
        
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending'");
    }
};
