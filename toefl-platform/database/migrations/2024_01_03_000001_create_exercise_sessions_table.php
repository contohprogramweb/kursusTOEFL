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
        Schema::create('exercise_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('section'); // reading, listening, speaking, writing
            $table->integer('total_questions')->default(10);
            $table->json('question_ids'); // Array of question IDs in this session
            $table->json('user_answers')->nullable(); // Store answers as JSON {question_id: answer}
            $table->boolean('is_completed')->default(false);
            $table->integer('current_question_index')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'section']);
            $table->index(['user_id', 'is_completed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_sessions');
    }
};
