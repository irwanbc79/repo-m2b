<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'parent_shipment_id')) {
                $table->unsignedBigInteger('parent_shipment_id')->nullable()->after('quotation_id');
                $table->foreign('parent_shipment_id')
                    ->references('id')
                    ->on('shipments')
                    ->nullOnDelete();
                $table->index('parent_shipment_id');
            }

            if (!Schema::hasColumn('shipments', 'customs_regime')) {
                $table->string('customs_regime', 40)->default('regular')->after('service_type');
                $table->index('customs_regime');
            }

            if (!Schema::hasColumn('shipments', 'customs_deadline_at')) {
                $table->date('customs_deadline_at')->nullable()->after('actual_arrival');
                $table->index('customs_deadline_at');
            }

            if (!Schema::hasColumn('shipments', 'customs_reference_number')) {
                $table->string('customs_reference_number', 120)->nullable()->after('customs_deadline_at');
            }

            if (!Schema::hasColumn('shipments', 'customs_regime_notes')) {
                $table->text('customs_regime_notes')->nullable()->after('customs_reference_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'parent_shipment_id')) {
                try {
                    $table->dropForeign(['parent_shipment_id']);
                } catch (\Throwable $e) {
                    // SQLite / older index fallback
                }
                try {
                    $table->dropIndex(['parent_shipment_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('parent_shipment_id');
            }

            foreach (['customs_regime', 'customs_deadline_at', 'customs_reference_number', 'customs_regime_notes'] as $col) {
                if (Schema::hasColumn('shipments', $col)) {
                    if (in_array($col, ['customs_regime', 'customs_deadline_at'], true)) {
                        try {
                            $table->dropIndex([$col]);
                        } catch (\Throwable $e) {
                        }
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
