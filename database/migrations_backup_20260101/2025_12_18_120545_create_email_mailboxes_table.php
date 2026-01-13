<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_mailboxes', function (Blueprint $table) {
            $table->id();
            $table->string('mailbox', 20)->unique();
            $table->unsignedBigInteger('last_uid')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_mailboxes');
    }
};
