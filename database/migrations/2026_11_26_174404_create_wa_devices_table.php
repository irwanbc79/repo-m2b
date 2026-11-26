<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wa_devices')) {
            Schema::create('wa_devices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable(); // null = nomor utama kantor
                $table->string('label', 120); // Contoh: "WA Utama M2B", "Marketing - Nurul"
                $table->string('phone_number', 30); // Contoh: 6281234567890
                $table->string('phone_number_id', 80)->unique(); // Kunci utama pencocokan dari Meta
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_synced_at')->nullable()->index(); // Pantau batas aktif 13 hari Coexistence
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('wa_messages')) {
            Schema::table('wa_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('wa_messages', 'wa_device_id')) {
                    $table->unsignedBigInteger('wa_device_id')->nullable()->after('mora_lead_id');
                    $table->foreign('wa_device_id')
                        ->references('id')
                        ->on('wa_devices')
                        ->nullOnDelete();
                    $table->index('wa_device_id');
                }
            });
        }

        if (Schema::hasTable('mora_lead_notifications')) {
            Schema::table('mora_lead_notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('mora_lead_notifications', 'wa_device_id')) {
                    $table->unsignedBigInteger('wa_device_id')->nullable()->after('assigned_to');
                    $table->foreign('wa_device_id')
                        ->references('id')
                        ->on('wa_devices')
                        ->nullOnDelete();
                    $table->index('wa_device_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mora_lead_notifications') && Schema::hasColumn('mora_lead_notifications', 'wa_device_id')) {
            Schema::table('mora_lead_notifications', function (Blueprint $table) {
                try { $table->dropForeign(['wa_device_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['wa_device_id']); } catch (\Throwable $e) {}
                $table->dropColumn('wa_device_id');
            });
        }

        if (Schema::hasTable('wa_messages') && Schema::hasColumn('wa_messages', 'wa_device_id')) {
            Schema::table('wa_messages', function (Blueprint $table) {
                try { $table->dropForeign(['wa_device_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['wa_device_id']); } catch (\Throwable $e) {}
                $table->dropColumn('wa_device_id');
            });
        }

        Schema::dropIfExists('wa_devices');
    }
};
