<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ambil nilai enum yang ada
        $table = 'shipments';
        $column = 'status';
        
        // Ubah kolom status untuk menambahkan 'cancel'
        DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` ENUM('pending', 'in_progress', 'completed', 'cancel') DEFAULT 'pending'");
        
        // Tambah kolom cancelled_at dan cancelled_by untuk tracking
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            
            // Foreign key ke users table
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancellation_reason']);
        });
        
        // Kembalikan enum ke nilai sebelumnya
        DB::statement("ALTER TABLE `shipments` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending'");
    }
};
