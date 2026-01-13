<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('survey_analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->json('cache_data');
            $table->timestamp('valid_until');
            $table->timestamps();
            
            $table->index('cache_key');
            $table->index('valid_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_analytics_cache');
    }
};
