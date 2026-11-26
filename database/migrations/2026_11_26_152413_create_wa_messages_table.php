<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wa_messages')) {
            Schema::create('wa_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mora_lead_id')->nullable();
                $table->string('wa_message_id', 160)->nullable()->unique();
                $table->string('wa_phone', 30)->index();
                $table->string('sender_name', 120)->nullable();
                $table->string('direction', 20)->default('inbound')->index(); // inbound | outbound
                $table->string('origin', 40)->default('meta_cloud_api'); // meta_cloud_api | wa_business_app_echo | portal_manual
                $table->string('message_type', 30)->default('text'); // text | image | document | audio | video | template | other
                $table->text('body')->nullable();
                $table->string('media_url', 500)->nullable();
                $table->string('media_mime', 100)->nullable();
                $table->string('status', 30)->default('received')->index(); // received | sent | delivered | read | failed
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->timestamp('wa_timestamp')->nullable()->index();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->index(['mora_lead_id', 'created_at']);
                $table->foreign('mora_lead_id')
                    ->references('id')
                    ->on('mora_lead_notifications')
                    ->nullOnDelete();
                $table->foreign('sent_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('mora_lead_notifications')) {
            Schema::table('mora_lead_notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('mora_lead_notifications', 'first_responded_at')) {
                    $table->timestamp('first_responded_at')->nullable()->after('read_at');
                }
                if (!Schema::hasColumn('mora_lead_notifications', 'last_wa_message_at')) {
                    $table->timestamp('last_wa_message_at')->nullable()->after('first_responded_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mora_lead_notifications')) {
            Schema::table('mora_lead_notifications', function (Blueprint $table) {
                foreach (['first_responded_at', 'last_wa_message_at'] as $col) {
                    if (Schema::hasColumn('mora_lead_notifications', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('wa_messages');
    }
};
