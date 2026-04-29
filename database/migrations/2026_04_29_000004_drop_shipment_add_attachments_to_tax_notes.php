<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_notes', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropColumn('shipment_id');
            $table->json('attachments')->nullable()->after('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::table('tax_notes', function (Blueprint $table) {
            $table->dropColumn('attachments');
            $table->foreignId('shipment_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
        });
    }
};
