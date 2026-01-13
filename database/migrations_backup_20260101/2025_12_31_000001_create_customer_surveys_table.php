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
        Schema::create('customer_surveys', function (Blueprint $table) {
            $table->id();
            
            // Tracking
            $table->year('survey_year')->default(date('Y'));
            $table->enum('survey_quarter', ['Q1', 'Q2', 'Q3', 'Q4'])->nullable();
            $table->timestamp('response_date')->useCurrent();
            
            // Responder Info (Optional/Anonymous)
            $table->boolean('is_anonymous')->default(false);
            $table->string('company_name')->nullable();
            $table->enum('respondent_position', ['owner', 'director', 'manager', 'staff', 'other'])->nullable();
            $table->string('respondent_position_other', 100)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            
            // Services Used (JSON array)
            $table->json('services_used')->nullable()->comment('["import","export","domestic","customs","freight"]');
            
            // B. SATISFACTION SCORES (1-5)
            $table->unsignedTinyInteger('overall_satisfaction')->nullable();
            $table->unsignedTinyInteger('service_fit_needs')->nullable();
            
            // C. OPERATIONAL QUALITY (1-5)
            $table->unsignedTinyInteger('timely_delivery')->nullable();
            $table->unsignedTinyInteger('shipment_info_clarity')->nullable();
            $table->unsignedTinyInteger('document_accuracy')->nullable();
            $table->unsignedTinyInteger('problem_handling')->nullable();
            $table->unsignedTinyInteger('coordination_quality')->nullable();
            
            // D. COMMUNICATION & CUSTOMER SERVICE (1-5)
            $table->unsignedTinyInteger('responsiveness')->nullable();
            $table->unsignedTinyInteger('explanation_clarity')->nullable();
            $table->unsignedTinyInteger('staff_professionalism')->nullable();
            $table->unsignedTinyInteger('contact_ease')->nullable();
            
            // E. PRICING (1-5)
            $table->unsignedTinyInteger('price_fairness')->nullable();
            $table->unsignedTinyInteger('cost_transparency')->nullable();
            $table->unsignedTinyInteger('invoice_accuracy')->nullable();
            
            // F. DIGITAL EXPERIENCE (1-5 or NULL if not used)
            $table->boolean('portal_used')->default(false);
            $table->unsignedTinyInteger('portal_ease_of_use')->nullable();
            $table->unsignedTinyInteger('portal_info_clarity')->nullable();
            $table->unsignedTinyInteger('portal_usefulness')->nullable();
            
            // G. LOYALTY & NPS
            $table->unsignedTinyInteger('likelihood_reuse')->nullable()->comment('1-5 scale');
            $table->unsignedTinyInteger('nps_score')->nullable()->comment('0-10 scale for Net Promoter Score');
            
            // H. OPEN-ENDED FEEDBACK
            $table->text('appreciate_most')->nullable();
            $table->text('needs_improvement')->nullable();
            $table->text('future_features')->nullable();
            
            // I. FOLLOW-UP
            $table->boolean('willing_to_contact')->default(false);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->unsignedInteger('completion_time')->nullable()->comment('Seconds to complete survey');
            
            // Admin Management
            $table->boolean('is_complete')->default(true);
            $table->boolean('is_flagged')->default(false)->comment('For admin review');
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('survey_year');
            $table->index('response_date');
            $table->index('nps_score');
            $table->index('overall_satisfaction');
            $table->index('customer_id');
            $table->index('is_flagged');
            
            // Foreign Key
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_surveys');
    }
};
