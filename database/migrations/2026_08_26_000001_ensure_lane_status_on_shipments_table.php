<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('shipments') && !Schema::hasColumn('shipments', 'lane_status')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('lane_status', 20)->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'lane_status')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('lane_status');
            });
        }
    }
};
