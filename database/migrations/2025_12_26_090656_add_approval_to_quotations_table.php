<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('approval_token', 64)->nullable()->unique();
            $table->enum('approval_status', ['pending','approved','rejected'])
                  ->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('approval_ip', 45)->nullable();
            $table->text('approval_user_agent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'approval_token',
                'approval_status',
                'approved_at',
                'approved_by',
                'approval_ip',
                'approval_user_agent',
            ]);
        });
    }
};
