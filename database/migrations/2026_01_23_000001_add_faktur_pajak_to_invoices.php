<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('faktur_pajak_number')->nullable()->after('status');
            $table->string('faktur_pajak_path')->nullable()->after('faktur_pajak_number');
            $table->timestamp('faktur_pajak_uploaded_at')->nullable()->after('faktur_pajak_path');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['faktur_pajak_number', 'faktur_pajak_path', 'faktur_pajak_uploaded_at']);
        });
    }
};
