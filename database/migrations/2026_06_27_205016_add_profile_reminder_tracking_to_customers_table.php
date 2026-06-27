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
        Schema::table('customers', function (Blueprint $table) {
            // Kapan customer pertama kali berada di portal saat datanya belum
            // lengkap (efektif = melihat banner pengingat).
            $table->timestamp('profile_reminder_seen_at')->nullable()->after('preferred_language');
            // Kapan customer menutup banner ("Ingatkan nanti").
            $table->timestamp('profile_reminder_dismissed_at')->nullable()->after('profile_reminder_seen_at');
            // Kapan profil mencapai status lengkap/valid (skor 'good').
            $table->timestamp('profile_completed_at')->nullable()->after('profile_reminder_dismissed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'profile_reminder_seen_at',
                'profile_reminder_dismissed_at',
                'profile_completed_at',
            ]);
        });
    }
};
