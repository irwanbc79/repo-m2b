<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Buat tabel pivot customer_user
        Schema::create('customer_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // PIC utama
            $table->timestamps();
            
            // Unique constraint - 1 user hanya bisa link ke 1 customer
            $table->unique(['customer_id', 'user_id']);
        });

        // Migrate existing data: copy user_id dari customers ke pivot table
        $customers = DB::table('customers')->whereNotNull('user_id')->get();
        foreach ($customers as $customer) {
            DB::table('customer_user')->insert([
                'customer_id' => $customer->id,
                'user_id' => $customer->user_id,
                'is_primary' => true, // User existing = PIC utama
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user');
    }
};
