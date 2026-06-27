<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel utama hasil simulasi
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('mode'); // e.g., 'Full Test', 'Reading Only'
            $table->integer('total_score')->default(0); // 0-120
            $table->integer('reading_score')->default(0);
            $table->integer('listening_score')->default(0);
            $table->integer('speaking_score')->default(0);
            $table->integer('writing_score')->default(0);
            $table->json('micro_skills'); // Radar chart data: {grammar: 80, vocab: 75, ...}
            $table->json('time_analysis'); // Stacked bar data: [{section: 'Reading', allocated: 60, actual: 55}, ...]
            $table->json('common_errors'); // Top 3 errors: [{type: 'Grammar', count: 5, desc: 'Subject-Verb Agreement'}, ...]
            $table->json('recommendations'); // 5 items: ['Review Tenses', 'Practice Skimming', ...]
            $table->integer('duration_seconds')->default(0);
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['user_id', 'completed_at']);
        });

        // Tabel detail jawaban per soal
        Schema::create('simulation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained()->onDelete('cascade');
            $table->integer('question_number');
            $table->string('section'); // Reading, Listening, Speaking, Writing
            $table->text('question_text')->nullable();
            $table->text('user_answer')->nullable();
            $table->text('correct_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->text('explanation')->nullable();
            $table->json('ai_feedback')->nullable(); // {delivery: 8, feedback: "Good intonation...", highlights: [...]}
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamps();
            
            $table->index('simulation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_answers');
        Schema::dropIfExists('simulations');
    }
};
