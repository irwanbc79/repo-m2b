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
        Schema::create('simple_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simple_invoice_id');
            $table->string('description', 500);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign key
            $table->foreign('simple_invoice_id')
                  ->references('id')
                  ->on('simple_invoices')
                  ->onDelete('cascade');
            
            // Index
            $table->index('simple_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simple_invoice_items');
    }
};
