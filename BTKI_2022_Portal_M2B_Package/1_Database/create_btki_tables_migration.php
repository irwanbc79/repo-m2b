<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - BTKI 2022 HS Code System
     * Created for Portal M2B Integration
     */
    public function up(): void
    {
        // 1. hs_sections table
        Schema::create('hs_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_number', 10)->unique()->comment('I, II, III, etc');
            $table->text('title_id')->comment('Judul Bagian (Indonesia)');
            $table->text('title_en')->nullable()->comment('Section Title (English)');
            $table->text('notes')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index('display_order');
        });

        // 2. hs_chapters table
        Schema::create('hs_chapters', function (Blueprint $table) {
            $table->id();
            $table->string('chapter_number', 2)->unique()->comment('01, 02, 03, etc');
            $table->text('title_id')->comment('Judul Bab (Indonesia)');
            $table->text('title_en')->nullable()->comment('Chapter Title (English)');
            $table->foreignId('section_id')->nullable()->constrained('hs_sections')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index('chapter_number');
            $table->index('section_id');
            $table->index('display_order');
        });

        // 3. hs_codes table (MAIN TABLE - 17,000+ records)
        Schema::create('hs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('hs_code', 20)->unique()->comment('Format: XX.XX.XX.XX');
            $table->tinyInteger('hs_level')->comment('2=Bab, 4=Pos, 6=Subpos, 8=Detail, 10=Subdetail');
            $table->string('parent_code', 20)->nullable()->comment('Parent HS Code');
            $table->text('description_id')->comment('Uraian Barang (Indonesia)');
            $table->text('description_en')->nullable()->comment('Description of Goods (English)');
            $table->string('chapter_number', 2)->comment('Reference to chapter');
            $table->string('section_number', 10)->nullable();
            
            // Additional fields
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->text('notes')->nullable();
            
            // Explanatory notes
            $table->boolean('has_explanatory_note')->default(false);
            $table->string('explanatory_note_url')->nullable();
            $table->longText('explanatory_note_content')->nullable();
            
            // Import tracking
            $table->string('import_batch_id', 50)->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('hs_code');
            $table->index('hs_level');
            $table->index('parent_code');
            $table->index('chapter_number');
            $table->index('section_number');
            $table->index('is_active');
            $table->index('has_explanatory_note');
            
            // Foreign keys
            $table->foreign('chapter_number')->references('chapter_number')->on('hs_chapters')->onDelete('cascade');
        });

        // Add full-text indexes (after table creation)
        DB::statement('ALTER TABLE hs_codes ADD FULLTEXT INDEX idx_search_id (description_id)');
        DB::statement('ALTER TABLE hs_codes ADD FULLTEXT INDEX idx_search_en (description_en)');
        DB::statement('ALTER TABLE hs_codes ADD FULLTEXT INDEX idx_search_both (description_id, description_en)');

        // 4. hs_general_rules table
        Schema::create('hs_general_rules', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Judul aturan');
            $table->longText('content_id')->comment('Konten lengkap (Indonesia)');
            $table->longText('content_en')->nullable()->comment('Content (English)');
            $table->integer('rule_order')->default(1)->comment('Urutan aturan (1-6)');
            $table->string('version', 50)->default('BTKI 2022 v1');
            $table->date('effective_date');
            $table->timestamps();
            
            $table->index('version');
            $table->index('rule_order');
        });

        // 5. hs_section_notes table
        Schema::create('hs_section_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('hs_sections')->onDelete('cascade');
            $table->longText('note_text');
            $table->integer('note_order')->default(1);
            $table->timestamps();
            
            $table->index('section_id');
            $table->index('note_order');
        });

        // 6. hs_chapter_notes table
        Schema::create('hs_chapter_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('hs_chapters')->onDelete('cascade');
            $table->longText('note_text');
            $table->integer('note_order')->default(1);
            $table->timestamps();
            
            $table->index('chapter_id');
            $table->index('note_order');
        });

        // 7. hs_explanatory_notes table
        Schema::create('hs_explanatory_notes', function (Blueprint $table) {
            $table->id();
            $table->string('hs_code', 20);
            $table->string('note_title')->nullable();
            $table->longText('note_content');
            $table->enum('note_type', ['general', 'subheading', 'exclusion', 'example'])->default('general');
            $table->string('language', 5)->default('id');
            $table->string('source', 100)->nullable()->comment('Source: CHM, Manual, WCO');
            $table->timestamps();
            
            $table->index('hs_code');
            $table->index('note_type');
            $table->index('language');
            
            $table->foreign('hs_code')->references('hs_code')->on('hs_codes')->onDelete('cascade');
        });

        // Add full-text index
        DB::statement('ALTER TABLE hs_explanatory_notes ADD FULLTEXT INDEX idx_content (note_content)');

        // 8. hs_search_logs table (Analytics)
        Schema::create('hs_search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('search_query');
            $table->integer('result_count')->default(0);
            $table->string('selected_hs_code', 20)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('search_date')->useCurrent();
            
            $table->index('search_query');
            $table->index('search_date');
            $table->index('user_id');
        });

        // 9. hs_favorites table
        Schema::create('hs_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('hs_code', 20);
            $table->text('notes')->nullable()->comment('User personal notes');
            $table->timestamps();
            
            $table->unique(['user_id', 'hs_code']);
            $table->index('user_id');
            $table->index('hs_code');
            
            $table->foreign('hs_code')->references('hs_code')->on('hs_codes')->onDelete('cascade');
        });

        // 10. hs_import_history table
        Schema::create('hs_import_history', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 50)->unique();
            $table->string('file_name');
            $table->integer('total_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('skipped_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->longText('error_log')->nullable();
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->timestamps();
            
            $table->index('batch_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hs_import_history');
        Schema::dropIfExists('hs_favorites');
        Schema::dropIfExists('hs_search_logs');
        Schema::dropIfExists('hs_explanatory_notes');
        Schema::dropIfExists('hs_chapter_notes');
        Schema::dropIfExists('hs_section_notes');
        Schema::dropIfExists('hs_general_rules');
        Schema::dropIfExists('hs_codes');
        Schema::dropIfExists('hs_chapters');
        Schema::dropIfExists('hs_sections');
    }
};
