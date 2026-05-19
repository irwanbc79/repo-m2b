<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_transactions', 'invoice_payment_id')) {
                $table->unsignedBigInteger('invoice_payment_id')->nullable()->after('invoice_id');
            }
            if (!Schema::hasColumn('cash_transactions', 'job_cost_id')) {
                $table->unsignedBigInteger('job_cost_id')->nullable()->after('vendor_bill_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_transactions', 'invoice_payment_id')) {
                $table->dropColumn('invoice_payment_id');
            }
            if (Schema::hasColumn('cash_transactions', 'job_cost_id')) {
                $table->dropColumn('job_cost_id');
            }
        });
    }
};
