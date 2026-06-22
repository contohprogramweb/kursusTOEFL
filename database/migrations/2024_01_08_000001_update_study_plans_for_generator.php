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
        // Tambahkan kolom-kolom baru untuk study plan generator
        Schema::table('study_plans', function (Blueprint $table) {
            $table->integer('target_score')->nullable()->after('name');
            $table->date('test_date')->nullable()->after('target_score');
            $table->decimal('daily_hours', 3, 1)->default(2.0)->after('test_date');
            $table->json('available_days')->nullable()->after('daily_hours'); // [0,1,2,3,4,5,6] - 0=Sunday
            $table->text('ai_notes')->nullable()->after('available_days');
            $table->boolean('is_ai_generated')->default(false)->after('ai_notes');
        });

        // Tambahkan kolom pada study_plan_tasks untuk tracking estimasi waktu
        Schema::table('study_plan_tasks', function (Blueprint $table) {
            $table->integer('estimated_minutes')->default(30)->after('type');
            $table->string('section')->nullable()->after('estimated_minutes'); // reading, listening, speaking, writing
            $table->integer('priority')->default(5)->after('section'); // 1-10, 1=highest
            $table->json('metadata')->nullable()->after('priority'); // extra data seperti modul_id, difficulty, etc
        });

        // Tabel untuk tracking penyesuaian manual user
        Schema::create('study_plan_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained('study_plan_tasks')->onDelete('cascade');
            $table->string('adjustment_type'); // reschedule, skip, add_custom
            $table->text('reason')->nullable();
            $table->timestamp('adjusted_at')->useCurrent();
            $table->timestamps();

            $table->index(['study_plan_id', 'adjusted_at']);
        });

        // Tabel untuk reminder notifications log
        Schema::create('study_plan_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('study_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('task_id')->constrained('study_plan_tasks')->onDelete('cascade');
            $table->date('scheduled_date');
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'task_id', 'scheduled_date']);
            $table->index(['user_id', 'is_sent', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_plan_reminders');
        Schema::dropIfExists('study_plan_adjustments');

        Schema::table('study_plan_tasks', function (Blueprint $table) {
            $table->dropColumn(['estimated_minutes', 'section', 'priority', 'metadata']);
        });

        Schema::table('study_plans', function (Blueprint $table) {
            $table->dropColumn([
                'target_score',
                'test_date',
                'daily_hours',
                'available_days',
                'ai_notes',
                'is_ai_generated'
            ]);
        });
    }
};
