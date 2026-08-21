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
        Schema::table('shipment_eta_revisions', function (Blueprint $table) {
            $table->text('customer_message')->nullable()->after('reason_notes');
            $table->boolean('evidence_customer_visible')->default(false)->after('customer_visible');
            $table->dateTime('published_at')->nullable()->after('evidence_customer_visible');
            $table->dateTime('viewed_at')->nullable()->after('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_eta_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'customer_message',
                'evidence_customer_visible',
                'published_at',
                'viewed_at',
            ]);
        });
    }
};
