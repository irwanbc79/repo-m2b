<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'bukti_potong_number')) {
                $table->string('bukti_potong_number')->nullable()->after('faktur_pajak_requested_at');
            }
            if (!Schema::hasColumn('invoices', 'bukti_potong_path')) {
                $table->string('bukti_potong_path')->nullable()->after('bukti_potong_number');
            }
            if (!Schema::hasColumn('invoices', 'bukti_potong_amount')) {
                $table->decimal('bukti_potong_amount', 15, 2)->nullable()->after('bukti_potong_path');
            }
            if (!Schema::hasColumn('invoices', 'bukti_potong_date')) {
                $table->date('bukti_potong_date')->nullable()->after('bukti_potong_amount');
            }
            if (!Schema::hasColumn('invoices', 'bukti_potong_uploaded_at')) {
                $table->timestamp('bukti_potong_uploaded_at')->nullable()->after('bukti_potong_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['bukti_potong_number', 'bukti_potong_path', 'bukti_potong_amount', 'bukti_potong_date', 'bukti_potong_uploaded_at'] as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
