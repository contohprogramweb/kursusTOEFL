<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['school', 'university', 'course_center', 'corporate']);
            $table->text('address')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#007bff');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->index('status');
            $table->index('type');
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->integer('target_score')->nullable();
            $table->date('test_date')->nullable();
            $table->enum('learning_preference', ['visual', 'auditory', 'reading_writing', 'kinesthetic'])->default('visual');
            $table->string('timezone')->default('UTC');
            $table->string('phone')->nullable();
            $table->foreignId('institution_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('institution_id');
        });

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('dark_mode')->default(false);
            $table->integer('font_size')->default(16);
            $table->boolean('high_contrast')->default(false);
            $table->boolean('animations')->default(true);
            $table->boolean('screen_reader_opt')->default(false);
            $table->string('language')->default('en');
            $table->boolean('dnd_enabled')->default(false);
            $table->time('dnd_start')->nullable();
            $table->time('dnd_end')->nullable();
            $table->json('dnd_days')->nullable(); // ['monday', 'tuesday', ...]
            $table->timestamps();
            
            $table->unique('user_id');
        });

        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('plan_type', ['free', 'basic', 'premium', 'enterprise']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('institution_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('plan_type');
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('intermediate');
            $table->integer('order_index')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('section');
            $table->index('difficulty');
            $table->index('status');
        });

        Schema::create('module_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->enum('content_type', ['video', 'text', 'audio', 'interactive', 'quiz']);
            $table->string('title');
            $table->json('content_data');
            $table->integer('order_index')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->timestamps();
            
            $table->index('module_id');
            $table->index('content_type');
        });

        Schema::create('micro_skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('section');
            $table->unique(['name', 'section']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->enum('question_type', ['multiple_choice', 'completion', 'reordering', 'essay', 'speaking']);
            $table->text('question_text');
            $table->text('passage_text')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->text('explanation')->nullable();
            $table->string('source')->nullable();
            $table->string('correct_answer')->nullable();
            $table->integer('preparation_time')->nullable(); // seconds
            $table->integer('response_time')->nullable(); // seconds
            $table->integer('word_limit_min')->nullable();
            $table->integer('word_limit_max')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('section');
            $table->index('question_type');
            $table->index('difficulty');
            $table->index('status');
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            
            $table->index('question_id');
        });

        Schema::create('question_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('micro_skills')->onDelete('cascade');
            $table->decimal('weight', 3, 2)->default(1.00);
            
            $table->unique(['question_id', 'skill_id']);
            $table->index('skill_id');
        });

        Schema::create('simulation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('mode', ['full_test', 'section_practice', 'custom']);
            $table->integer('total_duration')->default(120); // minutes
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('mode');
            $table->index('status');
        });

        Schema::create('simulation_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->integer('order_index')->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->integer('question_count')->default(0);
            $table->boolean('break_after')->default(false);
            $table->integer('break_duration')->default(0); // minutes
            
            $table->index('template_id');
        });

        Schema::create('simulation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('simulation_templates')->onDelete('set null');
            $table->enum('mode', ['full_test', 'section_practice', 'custom']);
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->enum('status', ['ongoing', 'completed', 'abandoned'])->default('ongoing');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('section_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained('simulation_results')->onDelete('cascade');
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->decimal('score', 5, 2)->nullable();
            $table->integer('raw_score')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->enum('status', ['not_started', 'ongoing', 'completed', 'graded'])->default('not_started');
            $table->decimal('ai_confidence', 5, 4)->nullable();
            
            $table->index('result_id');
            $table->index('section');
        });

        Schema::create('question_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_result_id')->constrained('section_results')->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->onDelete('set null');
            $table->text('text_response')->nullable();
            $table->string('audio_url')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('time_spent_seconds')->default(0);
            $table->boolean('flagged')->default(false);
            $table->timestamps();
            
            $table->index('section_result_id');
            $table->index('question_id');
        });

        Schema::create('ai_grading_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_result_id')->constrained('section_results')->onDelete('cascade');
            $table->string('dimension'); // e.g., 'grammar', 'vocabulary', 'coherence'
            $table->decimal('score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->text('feedback')->nullable();
            $table->json('highlights')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();
            
            $table->index('section_result_id');
            $table->index('dimension');
        });

        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('target_score')->nullable();
            $table->date('test_date')->nullable();
            $table->decimal('daily_hours', 3, 1)->default(1.0);
            $table->json('available_days')->nullable(); // ['monday', 'tuesday', ...]
            $table->json('generated_schedule')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'paused'])->default('draft');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->integer('time_spent_minutes')->default(0);
            $table->timestamp('last_accessed')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->unique(['user_id', 'module_id']);
            $table->index('user_id');
        });

        Schema::create('exercise_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('exercise_type');
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->enum('mode', ['practice', 'timed', 'adaptive']);
            $table->decimal('score', 5, 2)->nullable();
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->timestamp('completed_at');
            
            $table->index('user_id');
            $table->index('section');
            $table->index('completed_at');
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('badge_type');
            $table->string('badge_name');
            $table->text('badge_description')->nullable();
            $table->string('badge_icon')->nullable();
            $table->timestamp('awarded_at');
            $table->boolean('is_public')->default(true);
            
            $table->index('user_id');
            $table->index('badge_type');
        });

        Schema::create('streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->integer('freezes_used')->default(0);
            $table->date('freeze_reset_date')->nullable();
            
            $table->unique('user_id');
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('title_template');
            $table->text('message_template');
            $table->json('channels'); // ['email', 'push', 'sms']
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->enum('channel', ['email', 'push', 'sms', 'in_app']);
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
        });

        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('title');
            $table->text('content');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamps();
            
            $table->index('category');
            $table->index('author_id');
        });

        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('parent_reply_id')->nullable()->constrained('forum_replies')->onDelete('cascade');
            $table->text('content');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_hidden')->default(false);
            $table->string('hide_reason')->nullable();
            $table->timestamps();
            
            $table->index('thread_id');
            $table->index('author_id');
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('set null');
            $table->integer('max_students')->default(30);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index('institution_id');
            $table->index('instructor_id');
        });

        Schema::create('class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('enrolled_at');
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
            
            $table->unique(['class_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('assignment_type', ['quiz', 'essay', 'speaking', 'mixed']);
            $table->morphs('target'); // target_type and target_id for polymorphic relation
            $table->timestamp('deadline');
            $table->boolean('reminder_1_day')->default(true);
            $table->boolean('reminder_1_hour')->default(true);
            $table->timestamps();
            
            $table->index('class_id');
            $table->index('deadline');
        });

        Schema::create('instructor_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('set null');
            $table->enum('section', ['listening', 'structure', 'reading']);
            $table->string('dimension')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('text_feedback')->nullable();
            $table->string('audio_feedback_url')->nullable();
            $table->json('highlights')->nullable();
            $table->boolean('is_draft')->default(true);
            $table->timestamp('submitted_at')->nullable();
            
            $table->index('assignment_id');
            $table->index('student_id');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('action');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('instructor_feedbacks');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('class_enrollments');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('streaks');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('exercise_histories');
        Schema::dropIfExists('learning_progress');
        Schema::dropIfExists('study_plans');
        Schema::dropIfExists('ai_grading_results');
        Schema::dropIfExists('question_responses');
        Schema::dropIfExists('section_results');
        Schema::dropIfExists('simulation_results');
        Schema::dropIfExists('simulation_template_sections');
        Schema::dropIfExists('simulation_templates');
        Schema::dropIfExists('question_skills');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('micro_skills');
        Schema::dropIfExists('module_contents');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('institutions');
    }
};
