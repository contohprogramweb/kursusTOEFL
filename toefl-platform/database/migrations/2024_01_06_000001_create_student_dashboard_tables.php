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
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_tasks')->default(0);
            $table->integer('completed_tasks')->default(0);
            $table->enum('status', ['active', 'completed', 'expired'])->default('active');
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });

        Schema::create('study_plan_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('type'); // module, practice, simulation
            $table->string('resource_id')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['study_plan_id', 'is_completed', 'order']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('icon_path');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('badge_id')->constrained()->onDelete('cascade');
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['user_id', 'badge_id']);
            $table->index(['user_id', 'earned_at']);
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // module, practice, simulation
            $table->string('title');
            $table->text('reason');
            $table->string('resource_id');
            $table->integer('priority')->default(10);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_read', 'priority']);
        });

        Schema::create('daily_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('activity_date');
            $table->integer('study_duration_seconds')->default(0);
            $table->integer('questions_solved')->default(0);
            $table->integer('simulations_taken')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'activity_date']);
            $table->index(['user_id', 'activity_date']);
        });

        // Tabel simulation_results untuk tracking skor
        if (!Schema::hasTable('simulation_results')) {
            Schema::create('simulation_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('simulation_type')->default('full');
                $table->integer('total_score')->default(0);
                $table->integer('reading_score')->default(0);
                $table->integer('listening_score')->default(0);
                $table->integer('speaking_score')->default(0);
                $table->integer('writing_score')->default(0);
                $table->timestamp('completed_at')->useCurrent();
                $table->timestamps();
                
                $table->index(['user_id', 'completed_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_activity_logs');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('study_plan_tasks');
        Schema::dropIfExists('study_plans');
        
        // Jangan drop simulation_results jika sudah ada dari migration lain
    }
};
