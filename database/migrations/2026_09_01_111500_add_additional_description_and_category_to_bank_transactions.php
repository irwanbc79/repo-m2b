<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bank_transactions')) {
            return;
        }

        Schema::table('bank_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_transactions', 'additional_description')) {
                $table->text('additional_description')->nullable()->after('description');
            }
            if (! Schema::hasColumn('bank_transactions', 'category')) {
                $table->string('category', 50)->nullable()->after('reference_number')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_transactions')) {
            return;
        }

        Schema::table('bank_transactions', function (Blueprint $table) {
            foreach (['additional_description', 'category'] as $kolom) {
                if (Schema::hasColumn('bank_transactions', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
