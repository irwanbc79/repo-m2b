<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_notes', function (Blueprint $table) {
            $table->string('jenis_pajak')->nullable()->after('shipment_id');
            $table->decimal('nominal', 18, 2)->nullable()->after('jenis_pajak');
            $table->foreignId('invoice_id')->nullable()->after('nominal')
                ->constrained('invoices')->nullOnDelete();
            $table->boolean('is_resolved')->default(false)->after('invoice_id');
            $table->timestamp('resolved_at')->nullable()->after('is_resolved');
        });
    }

    public function down(): void
    {
        Schema::table('tax_notes', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['jenis_pajak', 'nominal', 'invoice_id', 'is_resolved', 'resolved_at']);
        });
    }
};
