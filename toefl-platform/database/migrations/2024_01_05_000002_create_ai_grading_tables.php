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
        // Tabel ai_grading_results - Menyimpan hasil penilaian AI
        Schema::create('ai_grading_results', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation ke SpeakingSubmission atau WritingSubmission
            $table->morphs('gradable');
            
            // Dimensi penilaian
            $table->string('dimension'); // e.g., 'delivery', 'language_use', 'topic_development' untuk speaking
            
            // Skor
            $table->decimal('score', 5, 2);
            $table->integer('max_score')->default(30);
            
            // Feedback dan highlights
            $table->text('feedback')->nullable();
            $table->json('highlights')->nullable(); // Array of {position, type, message, suggestion, example}
            
            // Confidence dan model info
            $table->decimal('confidence', 5, 2)->nullable(); // 0-100
            $table->string('model_version')->nullable(); // e.g., 'gpt-4-2024-01-01'
            
            // Untuk speaking: transkrip dari audio
            $table->text('transcript')->nullable();
            
            // Konten submission asli
            $table->text('submission_content')->nullable();
            
            // Status review
            $table->string('status')->default('completed'); // completed, pending_review, reviewed
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes untuk performa
            $table->index(['gradable_type', 'gradable_id']);
            $table->index('status');
            $table->index('confidence');
            $table->index('created_at');
        });

        // Tabel ai_grading_queue - Queue untuk manual review
        Schema::create('ai_grading_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_grading_result_id')->constrained('ai_grading_results')->onDelete('cascade');
            $table->string('reason'); // low_confidence, service_down, etc.
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index('assigned_to');
        });

        // Tabel ai_service_logs - Monitoring dan SLA tracking
        Schema::create('ai_service_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service'); // google_speech, aws_transcribe, openai, claude
            $table->string('action'); // transcribe, grade_speaking, grade_writing
            $table->foreignId('gradable_id')->nullable();
            $table->string('gradable_type')->nullable();
            $table->boolean('success');
            $table->integer('response_time_ms')->nullable(); // Untuk SLA monitoring
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable(); // Tracking biaya API
            $table->string('model_version')->nullable();
            $table->timestamps();
            
            // Indexes untuk query performa
            $table->index(['service', 'success']);
            $table->index('created_at');
            $table->index(['gradable_type', 'gradable_id']);
            
            // Composite index untuk SLA queries
            $table->index(['service', 'action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_service_logs');
        Schema::dropIfExists('ai_grading_queue');
        Schema::dropIfExists('ai_grading_results');
    }
};
