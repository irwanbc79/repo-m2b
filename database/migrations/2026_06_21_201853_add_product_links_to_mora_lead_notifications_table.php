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
        Schema::table('mora_lead_notifications', function (Blueprint $table) {
            $table->text('product_links')->nullable()->after('service_interest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mora_lead_notifications', function (Blueprint $table) {
            $table->dropColumn('product_links');
        });
    }
};
