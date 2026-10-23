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
        // 1. Ensure hs_kum table exists
        if (!Schema::hasTable('hs_kum')) {
            Schema::create('hs_kum', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('rule_number')->unique();
                $table->string('title');
                $table->text('content');
                $table->timestamps();
            });
        }

        // 2. Ensure section_notes on hs_sections
        if (Schema::hasTable('hs_sections')) {
            Schema::table('hs_sections', function (Blueprint $table) {
                if (!Schema::hasColumn('hs_sections', 'section_notes')) {
                    $table->text('section_notes')->nullable()->after('notes');
                }
            });
        }

        // 3. Ensure chapter_notes on hs_chapters
        if (Schema::hasTable('hs_chapters')) {
            Schema::table('hs_chapters', function (Blueprint $table) {
                if (!Schema::hasColumn('hs_chapters', 'chapter_notes')) {
                    $table->text('chapter_notes')->nullable()->after('notes');
                }
            });
        }

        // 4. Ensure tariff and rich description columns on hs_codes
        if (Schema::hasTable('hs_codes')) {
            Schema::table('hs_codes', function (Blueprint $table) {
                if (!Schema::hasColumn('hs_codes', 'import_duty')) {
                    $table->string('import_duty', 50)->nullable()->after('section_number');
                }
                if (!Schema::hasColumn('hs_codes', 'export_duty')) {
                    $table->string('export_duty', 50)->nullable()->after('import_duty');
                }
                if (!Schema::hasColumn('hs_codes', 'ppn')) {
                    $table->string('ppn', 50)->nullable()->after('export_duty');
                }
                if (!Schema::hasColumn('hs_codes', 'ppnbm')) {
                    $table->string('ppnbm', 50)->nullable()->after('ppn');
                }
                if (!Schema::hasColumn('hs_codes', 'pph_api')) {
                    $table->string('pph_api', 50)->nullable()->after('ppnbm');
                }
                if (!Schema::hasColumn('hs_codes', 'pph_non_api')) {
                    $table->string('pph_non_api', 50)->nullable()->after('pph_api');
                }
                if (!Schema::hasColumn('hs_codes', 'description_full')) {
                    $table->text('description_full')->nullable()->after('description_en');
                }
                if (!Schema::hasColumn('hs_codes', 'description_full_en')) {
                    $table->text('description_full_en')->nullable()->after('description_full');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hs_codes')) {
            Schema::table('hs_codes', function (Blueprint $table) {
                $columnsToDrop = [];
                $checkColumns = [
                    'import_duty', 'export_duty', 'ppn', 'ppnbm',
                    'pph_api', 'pph_non_api', 'description_full', 'description_full_en'
                ];
                foreach ($checkColumns as $col) {
                    if (Schema::hasColumn('hs_codes', $col)) {
                        $columnsToDrop[] = $col;
                    }
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        if (Schema::hasTable('hs_chapters')) {
            Schema::table('hs_chapters', function (Blueprint $table) {
                if (Schema::hasColumn('hs_chapters', 'chapter_notes')) {
                    $table->dropColumn('chapter_notes');
                }
            });
        }

        if (Schema::hasTable('hs_sections')) {
            Schema::table('hs_sections', function (Blueprint $table) {
                if (Schema::hasColumn('hs_sections', 'section_notes')) {
                    $table->dropColumn('section_notes');
                }
            });
        }

        if (Schema::hasTable('hs_kum')) {
            Schema::dropIfExists('hs_kum');
        }
    }
};
