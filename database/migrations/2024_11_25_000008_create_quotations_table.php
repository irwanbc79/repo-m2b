<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quotations')) {
            Schema::create('quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
                $table->string('manual_company', 100)->nullable();
                $table->string('manual_pic', 100)->nullable();
                $table->string('manual_email', 100)->nullable();
                $table->string('manual_phone', 20)->nullable();
                $table->string('quotation_number', 50)->unique();
                $table->date('quotation_date');
                $table->date('valid_until');
                $table->string('status', 20)->default('draft');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('service_total', 15, 2)->default(0);
                $table->decimal('reimbursement_total', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('pph_amount', 15, 2)->default(0);
                $table->decimal('grand_total', 15, 2)->default(0);
                $table->string('origin', 100)->nullable();
                $table->string('destination', 100)->nullable();
                $table->string('service_type', 30)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
