<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->timestamp('token_expires_at')->nullable()->after('token');
            $table->timestamp('reminder_sent_at')->nullable()->after('google_review_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumnIfExists('reminder_sent_at');
            $table->dropColumnIfExists('token_expires_at');
        });
    }
};
