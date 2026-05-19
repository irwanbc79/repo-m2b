<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['cash_in', 'cash_out'])->default('cash_in')->after('vendor_id');
            }
            if (!Schema::hasColumn('cash_transactions', 'cost_category')) {
                $table->enum('cost_category', ['shipment', 'overhead', 'other'])->nullable()->after('transaction_type');
            }
            if (!Schema::hasColumn('cash_transactions', 'counterpart_name')) {
                $table->string('counterpart_name', 200)->nullable()->after('cost_category');
            }
            if (!Schema::hasColumn('cash_transactions', 'counterpart_type')) {
                $table->enum('counterpart_type', ['customer', 'vendor', 'other'])->nullable()->after('counterpart_name');
            }
            if (!Schema::hasColumn('cash_transactions', 'currency')) {
                $table->string('currency', 3)->default('IDR')->after('amount');
            }
            if (!Schema::hasColumn('cash_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 4)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('cash_transactions', 'amount_idr')) {
                $table->decimal('amount_idr', 15, 2)->nullable()->after('exchange_rate');
            }
            if (!Schema::hasColumn('cash_transactions', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('cash_transactions', 'attachment_filename')) {
                $table->string('attachment_filename')->nullable()->after('attachment_path');
            }
            if (!Schema::hasColumn('cash_transactions', 'vendor_bill_id')) {
                $table->unsignedBigInteger('vendor_bill_id')->nullable()->after('invoice_id');
            }
            if (!Schema::hasColumn('cash_transactions', 'is_posted')) {
                $table->boolean('is_posted')->default(false)->after('vendor_bill_id');
            }
            if (!Schema::hasColumn('cash_transactions', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('is_posted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('cash_transactions', 'transaction_type') ? 'transaction_type' : null,
                Schema::hasColumn('cash_transactions', 'cost_category') ? 'cost_category' : null,
                Schema::hasColumn('cash_transactions', 'counterpart_name') ? 'counterpart_name' : null,
                Schema::hasColumn('cash_transactions', 'counterpart_type') ? 'counterpart_type' : null,
                Schema::hasColumn('cash_transactions', 'currency') ? 'currency' : null,
                Schema::hasColumn('cash_transactions', 'exchange_rate') ? 'exchange_rate' : null,
                Schema::hasColumn('cash_transactions', 'amount_idr') ? 'amount_idr' : null,
                Schema::hasColumn('cash_transactions', 'attachment_path') ? 'attachment_path' : null,
                Schema::hasColumn('cash_transactions', 'attachment_filename') ? 'attachment_filename' : null,
                Schema::hasColumn('cash_transactions', 'vendor_bill_id') ? 'vendor_bill_id' : null,
                Schema::hasColumn('cash_transactions', 'is_posted') ? 'is_posted' : null,
                Schema::hasColumn('cash_transactions', 'posted_at') ? 'posted_at' : null,
            ]));
        });
    }
};
