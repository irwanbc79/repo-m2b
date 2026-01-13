<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'signature_type')) {
                $table->string('signature_type')->default('full')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'signer_name')) {
                $table->string('signer_name')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'signer_title')) {
                $table->string('signer_title')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'signer_sign_path')) {
                $table->string('signer_sign_path')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'use_materai')) {
                $table->boolean('use_materai')->default(false);
            }
            if (!Schema::hasColumn('invoices', 'materai_type')) {
                $table->string('materai_type')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'total_paid')) {
                $table->decimal('total_paid', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = ['signature_type', 'signer_name', 'signer_title', 'signer_sign_path', 'use_materai', 'materai_type', 'total_paid'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
