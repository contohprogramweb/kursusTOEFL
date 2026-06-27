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
        // Update questions table to match new requirements
        Schema::table('questions', function (Blueprint $table) {
            // Change question_type enum to include new types
            $table->string('question_type', 50)->change();
            
            // Change difficulty from enum to integer 1-5
            $table->tinyInteger('difficulty')->unsigned()->default(3)->change();
            
            // Add full-text search indexes for MySQL
            if (config('database.default') !== 'sqlite') { $table->fullText(['question_text', 'passage_text']); }
        });

        // Update micro_skills table if needed
        // Index already exists in create_core_tables migration, so we skip it

        // Ensure question_skills has proper constraints for min 1, max 3 tags
        // This will be enforced at application level
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropFullText(['question_text', 'passage_text']);
            $table->enum('question_type', ['multiple_choice', 'completion', 'reordering', 'essay', 'speaking'])->change();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium')->change();
        });

        Schema::table('micro_skills', function (Blueprint $table) {
            $table->dropIndex(['section']);
        });
    }
};
