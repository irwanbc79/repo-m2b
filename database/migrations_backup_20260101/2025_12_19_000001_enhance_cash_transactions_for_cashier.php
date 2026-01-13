<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhancement untuk Simple Cashier Management
     * - Link ke Shipment
     * - Link ke Customer/Vendor
     * - Support attachment
     * - Transaction categorization
     */
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            // Link to related entities
            $table->foreignId('shipment_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->after('shipment_id')->constrained()->onDelete('set null');
            $table->foreignId('vendor_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            
            // Transaction type & category
            $table->enum('transaction_type', ['cash_in', 'cash_out'])->after('vendor_id')->default('cash_in');
            $table->enum('cost_category', ['shipment', 'overhead', 'other'])->nullable()->after('transaction_type');
            
            // Counterpart info (nama pihak yang bayar/dibayar)
            $table->string('counterpart_name', 200)->nullable()->after('cost_category');
            $table->enum('counterpart_type', ['customer', 'vendor', 'other'])->nullable()->after('counterpart_name');
            
            // Amount & currency
            $table->string('currency', 3)->default('IDR')->after('amount');
            $table->decimal('exchange_rate', 12, 4)->nullable()->after('currency');
            $table->decimal('amount_idr', 15, 2)->nullable()->after('exchange_rate')->comment('Amount in IDR for reporting');
            
            // Attachment & proof
            $table->string('attachment_path')->nullable()->after('description');
            $table->string('attachment_filename')->nullable()->after('attachment_path');
            
            // Related invoice/bill
            $table->foreignId('invoice_id')->nullable()->after('attachment_filename')->constrained()->onDelete('set null');
            $table->unsignedBigInteger('vendor_bill_id')->nullable()->after('invoice_id');
            
            // Auto-posting to accounting
            $table->boolean('is_posted')->default(false)->after('vendor_bill_id')->comment('Posted to journal entry');
            $table->unsignedBigInteger('journal_id')->nullable()->after('is_posted');
            $table->timestamp('posted_at')->nullable()->after('journal_id');
            
            // Audit trail
            $table->foreignId('created_by')->nullable()->after('posted_at')->constrained('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('transaction_type');
            $table->index('cost_category');
            $table->index('is_posted');
            $table->index(['shipment_id', 'transaction_type']);
            $table->index(['customer_id', 'transaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['shipment_id', 'transaction_type']);
            $table->dropIndex(['customer_id', 'transaction_type']);
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['cost_category']);
            $table->dropIndex(['is_posted']);
            
            // Drop foreign keys
            $table->dropForeign(['shipment_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['created_by']);
            
            // Drop columns
            $table->dropColumn([
                'shipment_id',
                'customer_id',
                'vendor_id',
                'transaction_type',
                'cost_category',
                'counterpart_name',
                'counterpart_type',
                'currency',
                'exchange_rate',
                'amount_idr',
                'attachment_path',
                'attachment_filename',
                'invoice_id',
                'vendor_bill_id',
                'is_posted',
                'journal_id',
                'posted_at',
                'created_by'
            ]);
        });
    }
};
