<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('internal_messages')) {
            return;
        }

        Schema::table('internal_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('internal_messages', 'reply_to_id')) {
                $table->unsignedBigInteger('reply_to_id')->nullable()->after('is_pinned')->index();
            }
            if (! Schema::hasColumn('internal_messages', 'reply_to_sender')) {
                $table->string('reply_to_sender')->nullable()->after('reply_to_id');
            }
            if (! Schema::hasColumn('internal_messages', 'reply_to_body')) {
                $table->text('reply_to_body')->nullable()->after('reply_to_sender');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('internal_messages')) {
            return;
        }

        Schema::table('internal_messages', function (Blueprint $table) {
            foreach (['reply_to_id', 'reply_to_sender', 'reply_to_body'] as $kolom) {
                if (Schema::hasColumn('internal_messages', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
