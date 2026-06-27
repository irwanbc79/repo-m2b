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
        Schema::create('shipment_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            // 'customer' = dikirim customer; 'admin' = dikirim staf M2B
            $table->string('sender_type', 20);
            // user_id pengirim (nullable utk fleksibilitas)
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body');
            // Kapan pihak penerima membaca pesan ini (null = belum dibaca)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'created_at']);
            $table->index(['sender_type', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_messages');
    }
};
