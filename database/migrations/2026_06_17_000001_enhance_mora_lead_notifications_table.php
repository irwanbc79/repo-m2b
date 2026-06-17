<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mora_lead_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('mora_lead_notifications', 'status')) {
                $table->string('status', 20)->default('new')->after('read_at');
            }
            if (!Schema::hasColumn('mora_lead_notifications', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('mora_lead_notifications', 'follow_up_at')) {
                $table->dateTime('follow_up_at')->nullable()->after('assigned_to');
            }
            if (!Schema::hasColumn('mora_lead_notifications', 'sales_notes')) {
                $table->text('sales_notes')->nullable()->after('follow_up_at');
            }
            if (!Schema::hasColumn('mora_lead_notifications', 'deal_value')) {
                $table->decimal('deal_value', 15, 2)->nullable()->after('sales_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mora_lead_notifications', function (Blueprint $table) {
            // Check if foreign key can be dropped (for SQLite compatibility)
            try {
                $table->dropForeign(['assigned_to']);
            } catch (\Exception $e) {
                // Ignore
            }
            $table->dropColumn(['status', 'assigned_to', 'follow_up_at', 'sales_notes', 'deal_value']);
        });
    }
};
