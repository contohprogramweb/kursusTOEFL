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
        Schema::table('badges', function (Blueprint $table) {
            // Add badge_code for system identification
            $table->string('badge_code')->nullable()->after('id');
            
            // Add category for grouping badges
            $table->string('category')->default('achievement')->after('badge_type');
            
            // Add difficulty level
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'legendary'])->default('medium')->after('category');
            
            // Add points value
            $table->integer('points')->default(10)->after('difficulty');
            
            // Index for badge code lookups
            $table->index('badge_code');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropIndex(['badge_code']);
            $table->dropIndex(['category']);
            $table->dropColumn(['badge_code', 'category', 'difficulty', 'points']);
        });
    }
};
