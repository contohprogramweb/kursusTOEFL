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
        // Create jobs table for Laravel Queue (database driver)
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Create failed_jobs table for tracking failed queue jobs
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Create ai_grading_queue table for manual review queue
        Schema::create('ai_grading_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_result_id')->constrained('section_results')->onDelete('cascade');
            $table->enum('type', ['speaking', 'writing']);
            $table->enum('reason', ['low_confidence', 'service_down', 'manual_override']);
            $table->text('transcript')->nullable(); // For speaking
            $table->text('essay_text')->nullable(); // For writing
            $table->json('ai_response')->nullable(); // Raw AI response for reference
            $table->decimal('ai_confidence', 5, 4)->nullable();
            $table->enum('status', ['pending', 'in_review', 'completed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();

            $table->index('section_result_id');
            $table->index('type');
            $table->index('status');
            $table->index('reason');
        });

        // Create ai_service_logs table for monitoring and SLA tracking
        Schema::create('ai_service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_result_id')->nullable()->constrained('section_results')->onDelete('set null');
            $table->string('service'); // 'google_speech', 'aws_transcribe', 'openai', 'claude'
            $table->string('action'); // 'transcribe', 'grade_speaking', 'grade_writing'
            $table->enum('status', ['success', 'error', 'timeout', 'fallback']);
            $table->integer('response_time_ms')->nullable(); // For SLA monitoring
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->string('model_version')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->boolean('is_cached')->default(false);
            $table->timestamps();

            $table->index('section_result_id');
            $table->index('service');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_service_logs');
        Schema::dropIfExists('ai_grading_queue');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
    }
};
