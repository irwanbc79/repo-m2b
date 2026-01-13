<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek kolom status
        $hasStatusColumn = Schema::hasColumn('shipments', 'status');
        
        if ($hasStatusColumn) {
            // Get current enum values
            $result = DB::select("SHOW COLUMNS FROM shipments WHERE Field = 'status'");
            $type = $result[0]->Type ?? '';
            
            // Check if 'cancel' already exists
            if (strpos($type, "'cancel'") === false) {
                // Add 'cancel' to enum
                DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'in_transit', 'completed', 'cancel') DEFAULT 'pending'");
            }
        }
        
        // Add tracking columns
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('shipments', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            }
            
            if (!Schema::hasColumn('shipments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });
        
        // Add foreign key if not exists
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'shipments' 
            AND COLUMN_NAME = 'cancelled_by'
            AND CONSTRAINT_NAME != 'PRIMARY'
        ");
        
        if (empty($foreignKeys)) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['cancelled_by']);
            
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
        
        // Revert enum
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'in_transit', 'completed') DEFAULT 'pending'");
    }
};
