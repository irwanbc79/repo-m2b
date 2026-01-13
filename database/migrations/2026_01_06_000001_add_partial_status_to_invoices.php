<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify ENUM to add 'partial' status
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'partial', 'cancelled') DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        // Revert back (note: this will fail if any row has 'partial' status)
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'cancelled') DEFAULT 'unpaid'");
    }
};
