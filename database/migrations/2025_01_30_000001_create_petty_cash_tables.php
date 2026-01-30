<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel utama kas kecil (support multi-fund & setting fleksibel)
        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Kas Kecil Operasional');
            $table->decimal('plafon', 15, 2)->default(1000000);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('min_balance_alert', 15, 2)->default(300000);
            $table->decimal('max_transaction', 15, 2)->default(250000);
            $table->foreignId('holder_user_id')->constrained('users');
            $table->foreignId('approver_user_id')->nullable()->constrained('users');
            $table->string('coa_code')->default('1102'); // Kas Kecil
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabel transaksi pengeluaran
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_fund_id')->constrained()->onDelete('cascade');
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->string('category');
            $table->string('description');
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->string('proof_file')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['petty_cash_fund_id', 'transaction_date']);
            $table->index('category');
        });

        // Tabel top-up / pengisian ulang
        Schema::create('petty_cash_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_fund_id')->constrained()->onDelete('cascade');
            $table->string('topup_number')->unique();
            $table->decimal('amount_requested', 15, 2);
            $table->decimal('amount_approved', 15, 2)->nullable();
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'transferred'])->default('pending');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->string('transfer_proof')->nullable();
            $table->text('notes')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();
        });

        // Tabel log perubahan setting (audit trail)
        Schema::create('petty_cash_setting_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_fund_id')->constrained()->onDelete('cascade');
            $table->foreignId('changed_by')->constrained('users');
            $table->string('field_changed');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_setting_logs');
        Schema::dropIfExists('petty_cash_topups');
        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('petty_cash_funds');
    }
};
