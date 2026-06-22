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
        // Notification Preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category'); // payment, assignment, forum, streak, study_plan, attendance
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(false);
            $table->boolean('push')->default(true);
            $table->boolean('sms')->default(false);
            $table->boolean('whatsapp')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'category']);
        });

        // User DND Settings
        Schema::create('user_dnd_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->time('start_time')->default('22:00:00');
            $table->time('end_time')->default('07:00:00');
            $table->json('allowed_categories')->nullable(); // categories that bypass DND
            $table->timestamps();
        });

        // DND Queue for delayed notifications
        Schema::create('dnd_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('notifiable');
            $table->string('type'); // notification class
            $table->json('data');
            $table->json('channels');
            $table->timestamp('scheduled_at');
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['scheduled_at', 'user_id']);
        });

        // Notification Rate Limits
        Schema::create('notification_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel'); // push, sms, whatsapp, payment
            $table->date('date');
            $table->integer('count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'channel', 'date']);
        });

        // FCM Tokens
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->string('device_name')->nullable();
            $table->string('platform')->default('android'); // android, ios, web
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        // Extend existing notifications table with delivery tracking
        if (!Schema::hasColumn('notifications', 'delivery_status')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->enum('delivery_status', ['pending', 'sent', 'read', 'failed'])->default('pending')->after('read_at');
                $table->integer('retry_count')->default(0)->after('delivery_status');
                $table->timestamp('last_retry_at')->nullable()->after('retry_count');
                $table->text('error_message')->nullable()->after('last_retry_at');
                $table->json('metadata')->nullable()->after('error_message');
                
                $table->index(['delivery_status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'delivery_status')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn(['delivery_status', 'retry_count', 'last_retry_at', 'error_message', 'metadata']);
            });
        }

        Schema::dropIfExists('fcm_tokens');
        Schema::dropIfExists('notification_rate_limits');
        Schema::dropIfExists('dnd_queues');
        Schema::dropIfExists('user_dnd_settings');
        Schema::dropIfExists('notification_preferences');
    }
};
