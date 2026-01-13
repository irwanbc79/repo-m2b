<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL VENDORS (Master Data Partner)
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Misal: VEN-001
            $table->string('name'); // Nama PT / Perorangan
            $table->string('category')->default('Trucking'); // Trucking, Shipping Line, Agent, Courier, dll
            $table->string('pic_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('bank_details')->nullable(); // Info Rekening untuk transfer
            $table->timestamps();
            $table->softDeletes(); // Agar data tidak hilang permanen saat dihapus
        });

        // 2. TABEL JOB COSTS (Transaksi Pengeluaran)
        Schema::create('job_costs', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Shipment (Wajib)
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            
            // Relasi ke Vendor (Opsional, bisa null jika biaya internal/cash)
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            
            // Detail Biaya
            $table->string('description'); // Contoh: "Biaya Trucking Kontainer 20ft"
            $table->decimal('amount', 15, 2); // Nominal Biaya
            
            // Integrasi Akuntansi (Opsional dulu, nanti diisi ID dari tabel COA)
            $table->unsignedBigInteger('coa_id')->nullable(); 
            
            // Status Pembayaran ke Vendor
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->date('date_paid')->nullable();
            
            // Bukti
            $table->string('proof_file')->nullable(); // Foto Nota/Bukti Transfer
            
            // Audit Trail
            $table->foreignId('created_by')->constrained('users'); // Siapa yang input
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_costs');
        Schema::dropIfExists('vendors');
    }
};