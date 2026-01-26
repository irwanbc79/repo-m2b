<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('faktur_pajak_requested')->default(false)->after('faktur_pajak_uploaded_at');
            $table->timestamp('faktur_pajak_requested_at')->nullable()->after('faktur_pajak_requested');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['faktur_pajak_requested', 'faktur_pajak_requested_at']);
        });
    }
};
