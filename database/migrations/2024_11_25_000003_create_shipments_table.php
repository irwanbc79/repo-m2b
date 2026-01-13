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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('awb_number', 50)->unique();
            $table->string('bl_number', 50)->nullable();
            $table->enum('shipment_type', ['air', 'sea', 'land']);
            $table->enum('service_type', ['import', 'export']);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->string('origin', 100);
            $table->string('destination', 100);
            $table->string('shipper_name', 200)->nullable();
            $table->string('consignee_name', 200)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('volume', 8, 2)->nullable();
            $table->integer('pieces')->default(1);
            $table->string('commodity', 200)->nullable();
            $table->dateTime('estimated_departure')->nullable();
            $table->dateTime('estimated_arrival')->nullable();
            $table->dateTime('actual_departure')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('status');
            $table->index('awb_number');
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
