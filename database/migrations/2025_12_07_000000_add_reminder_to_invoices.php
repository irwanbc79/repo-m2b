<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('last_reminded_at')->nullable()->after('status');
            $table->integer('reminder_count')->default(0)->after('last_reminded_at');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['last_reminded_at', 'reminder_count']);
        });
    }
};