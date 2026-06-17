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
            if (!Schema::hasColumn('cash_transactions', 'shipment_id')) {
                $table->foreignId('shipment_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('cash_transactions', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('cash_transactions', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');
            }
            
            // Transaction type & category
            if (!Schema::hasColumn('cash_transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['cash_in', 'cash_out'])->default('cash_in');
            }
            if (!Schema::hasColumn('cash_transactions', 'cost_category')) {
                $table->enum('cost_category', ['shipment', 'overhead', 'other'])->nullable();
            }
            
            // Counterpart info (nama pihak yang bayar/dibayar)
            if (!Schema::hasColumn('cash_transactions', 'counterpart_name')) {
                $table->string('counterpart_name', 200)->nullable();
            }
            if (!Schema::hasColumn('cash_transactions', 'counterpart_type')) {
                $table->enum('counterpart_type', ['customer', 'vendor', 'other'])->nullable();
            }
            
            // Amount & currency
            if (!Schema::hasColumn('cash_transactions', 'currency')) {
                $table->string('currency', 3)->default('IDR');
            }
            if (!Schema::hasColumn('cash_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 4)->nullable();
            }
            if (!Schema::hasColumn('cash_transactions', 'amount_idr')) {
                $table->decimal('amount_idr', 15, 2)->nullable();
            }
            
            // Attachment & proof
            if (!Schema::hasColumn('cash_transactions', 'attachment_path')) {
                $table->string('attachment_path')->nullable();
            }
            if (!Schema::hasColumn('cash_transactions', 'attachment_filename')) {
                $table->string('attachment_filename')->nullable();
            }
            
            // Related invoice/bill
            if (!Schema::hasColumn('cash_transactions', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('cash_transactions', 'vendor_bill_id')) {
                $table->unsignedBigInteger('vendor_bill_id')->nullable();
            }
            
            // Auto-posting to accounting
            if (!Schema::hasColumn('cash_transactions', 'is_posted')) {
                $table->boolean('is_posted')->default(false);
            }
            if (!Schema::hasColumn('cash_transactions', 'journal_id')) {
                $table->unsignedBigInteger('journal_id')->nullable();
            }
            if (!Schema::hasColumn('cash_transactions', 'posted_at')) {
                $table->timestamp('posted_at')->nullable();
            }
            
            // Audit trail
            if (!Schema::hasColumn('cash_transactions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            }
        });

        // Indexes for performance (separately to handle errors gracefully)
        try {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->index('transaction_type');
                $table->index('cost_category');
                $table->index('is_posted');
                $table->index(['shipment_id', 'transaction_type']);
                $table->index(['customer_id', 'transaction_type']);
            });
        } catch (\Exception $e) {
            // Ignore if index already exists
        }
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
