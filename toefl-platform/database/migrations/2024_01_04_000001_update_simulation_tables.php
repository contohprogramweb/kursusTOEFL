<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates simulation_templates and simulation_results tables to support
     * the full state machine for TOEFL iBT simulation tests.
     */
    public function up(): void
    {
        // Update simulation_templates table
        Schema::table('simulation_templates', function (Blueprint $table) {
            // Add institution_id for B2B assignment
            $table->foreignId('institution_id')->nullable()->constrained()->onDelete('set null');
            
            // Add is_locked to prevent deletion of default templates
            $table->boolean('is_locked')->default(false);
            
            // Modify mode enum to include 'realistic' and 'focus' modes
            $table->enum('mode', ['practice', 'scheduled', 'realistic', 'focus'])->change();
            
            $table->index('institution_id');
        });

        // Update simulation_template_sections table
        Schema::table('simulation_template_sections', function (Blueprint $table) {
            // Add section_result_id for tracking current section in progress
            $table->foreignId('section_result_id')->nullable()->constrained('section_results')->onDelete('set null');
        });

        // Update simulation_results table with state machine status
        Schema::table('simulation_results', function (Blueprint $table) {
            // Replace existing status enum with full state machine
            $table->enum('status', [
                'initiated',      // Test session created, not started
                'reading',        // Currently in Reading section
                'listening',      // Currently in Listening section
                'break',          // On break between sections
                'speaking',       // Currently in Speaking section
                'writing',        // Currently in Writing section
                'submitted',      // Test submitted, awaiting grading
                'grading',        // Being graded (AI/instructor review)
                'completed'       // Fully completed with scores
            ])->default('initiated')->change();
            
            // Add current_section_index for tracking progress
            $table->integer('current_section_index')->default(0);
            
            // Add time tracking per section
            $table->json('section_times')->nullable(); // {'reading': 3600, 'listening': 2400, ...}
            
            // Add pause/resume tracking
            $table->timestamp('paused_at')->nullable();
            $table->integer('total_paused_seconds')->default(0);
            
            $table->index('status');
            $table->index('current_section_index');
        });

        // Add pivot table for template-institution assignments (B2B)
        Schema::create('institution_simulation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('simulation_templates')->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['institution_id', 'template_id']);
            $table->index('institution_id');
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_simulation_templates');
        
        Schema::table('simulation_results', function (Blueprint $table) {
            $table->dropColumn([
                'current_section_index',
                'section_times',
                'paused_at',
                'total_paused_seconds'
            ]);
            $table->enum('status', ['ongoing', 'completed', 'abandoned'])->change();
        });

        Schema::table('simulation_template_sections', function (Blueprint $table) {
            $table->dropForeign(['section_result_id']);
            $table->dropColumn('section_result_id');
        });

        Schema::table('simulation_templates', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn(['institution_id', 'is_locked']);
            $table->enum('mode', ['full_test', 'section_practice', 'custom'])->change();
        });
    }
};
