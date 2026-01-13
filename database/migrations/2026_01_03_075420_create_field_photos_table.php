<?php
// database/migrations/xxxx_create_field_photos_table.php

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
        Schema::create('field_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id');
            $table->unsignedBigInteger('user_id');
            
            // File info
            $table->string('original_filename', 255);
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->unsignedInteger('file_size')->comment('in bytes');
            $table->string('mime_type', 100);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            
            // Additional info
            $table->text('description')->nullable();
            $table->string('upload_ip', 45)->nullable();
            
            // Geolocation (NEW FEATURES)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_address', 500)->nullable();
            $table->decimal('location_accuracy', 8, 2)->nullable();
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            
            $table->timestamps();
            
            // Indexes
            $table->index('shipment_id', 'idx_shipment');
            $table->index('user_id', 'idx_user');
            $table->index('created_at', 'idx_created');
            
            // Foreign keys
            $table->foreign('shipment_id')
                ->references('id')
                ->on('shipments')
                ->onDelete('cascade');
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_photos');
    }
};
