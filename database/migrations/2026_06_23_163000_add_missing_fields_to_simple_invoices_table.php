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
        Schema::table('simple_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('simple_invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }
            if (!Schema::hasColumn('simple_invoices', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('simple_invoices', 'paid_date')) {
                $table->date('paid_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('simple_invoices', 'payment_proof')) {
                $table->string('payment_proof', 255)->nullable()->after('paid_date');
            }
            if (!Schema::hasColumn('simple_invoices', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('payment_proof');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_invoices', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('simple_invoices', 'due_date')) {
                $columns[] = 'due_date';
            }
            if (Schema::hasColumn('simple_invoices', 'customer_address')) {
                $columns[] = 'customer_address';
            }
            if (Schema::hasColumn('simple_invoices', 'paid_date')) {
                $columns[] = 'paid_date';
            }
            if (Schema::hasColumn('simple_invoices', 'payment_proof')) {
                $columns[] = 'payment_proof';
            }
            if (Schema::hasColumn('simple_invoices', 'payment_notes')) {
                $columns[] = 'payment_notes';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
