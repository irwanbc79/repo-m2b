<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('mailbox', 20);
            $table->unsignedBigInteger('uid');
            $table->string('message_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('email_date')->nullable();
            $table->timestamps();

            $table->unique(['mailbox', 'uid']);
            $table->index(['mailbox', 'email_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
