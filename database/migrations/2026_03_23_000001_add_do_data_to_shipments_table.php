<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->json('do_containers')->nullable()->after('notes');
            $table->text('do_alamat_bongkar')->nullable()->after('do_containers');
            $table->string('do_nama_penerima', 200)->nullable()->after('do_alamat_bongkar');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['do_containers', 'do_alamat_bongkar', 'do_nama_penerima']);
        });
    }
};
