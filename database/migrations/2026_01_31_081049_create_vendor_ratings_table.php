<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel vendor_ratings untuk menyimpan rating dari staff
        Schema::create('vendor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('job_cost_id')->nullable();
            $table->tinyInteger('rating')->default(5); // 1-5 stars
            $table->enum('criteria', ['quality', 'timeliness', 'communication', 'pricing', 'overall'])->default('overall');
            $table->text('notes')->nullable();
            $table->foreignId('rated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['vendor_id', 'criteria']);
            $table->index('created_at');
        });

        // Tambah kolom scoring di tabel vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0)->after('category');
            $table->integer('total_ratings')->default(0)->after('avg_rating');
            $table->decimal('vendor_score', 5, 2)->default(0)->after('total_ratings');
            $table->char('vendor_grade', 2)->default('B')->after('vendor_score');
            $table->date('last_evaluated_at')->nullable()->after('vendor_grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_ratings');
        
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['avg_rating', 'total_ratings', 'vendor_score', 'vendor_grade', 'last_evaluated_at']);
        });
    }
};
