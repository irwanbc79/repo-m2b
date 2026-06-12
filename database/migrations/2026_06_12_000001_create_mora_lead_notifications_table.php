<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mora_lead_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remote_lead_id')->nullable();
            $table->string('name', 100);
            $table->string('company', 100)->nullable();
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->string('score', 10)->default('cold');
            $table->string('source', 20)->default('mora_chat');
            $table->string('service_interest', 30)->nullable();
            $table->text('summary')->nullable();
            $table->json('chat_history')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mora_lead_notifications');
    }
};
