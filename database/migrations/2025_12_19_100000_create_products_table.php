<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Product code: IMP-FS-001, EXP-PEB-001');
            $table->string('name')->comment('Product/Service name');
            $table->enum('category', ['import', 'export', 'domestic', 'consultation', 'reimbursement'])
                  ->comment('Main category');
            $table->string('sub_category', 100)->nullable()
                  ->comment('full_service, pib_only, peb_only, clearance_component, etc');
            $table->enum('service_type', ['service', 'reimbursement'])
                  ->default('service')
                  ->comment('service = jasa, reimbursement = biaya reimburse');
            $table->text('description')->nullable();
            $table->decimal('default_price', 15, 2)->default(0)
                  ->comment('Default price (can be changed in invoice)');
            $table->unsignedBigInteger('coa_id')->nullable()
                  ->comment('Link to Chart of Accounts (revenue account)');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Index for performance
            $table->index('category');
            $table->index('service_type');
            $table->index('is_active');
            $table->index('coa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
