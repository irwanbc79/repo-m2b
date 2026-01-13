<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('related_invoice_id')->nullable()->after('invoice_number');
            $table->foreign('related_invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->onDelete('set null');
            $table->index('related_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['related_invoice_id']);
            $table->dropIndex(['related_invoice_id']);
            $table->dropColumn('related_invoice_id');
        });
    }
};
