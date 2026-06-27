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
        // Update notifications table for delivery status tracking
        if (!Schema::hasColumn('notifications', 'delivery_status')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->enum('delivery_status', ['pending', 'sent', 'read', 'failed'])->default('pending')->after('data');
                $table->timestamp('sent_at')->nullable()->after('delivery_status');
                $table->timestamp('read_at')->nullable()->change();
                $table->integer('retry_count')->default(0)->after('read_at');
                $table->timestamp('next_retry_at')->nullable()->after('retry_count');
                $table->text('error_message')->nullable()->after('next_retry_at');
                $table->string('channel')->nullable()->after('error_message');
            });
        }

        // Create notification_preferences table for granular user preferences
        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('category'); // payment, assignment, forum, streak, study_plan, attendance
                $table->string('event_type'); // e.g., payment_success, payment_failed, new_assignment, forum_reply
                $table->boolean('channel_in_app')->default(true);
                $table->boolean('channel_email')->default(true);
                $table->boolean('channel_push')->default(true);
                $table->boolean('channel_sms')->default(false);
                $table->boolean('channel_whatsapp')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'category', 'event_type']);
                $table->index(['user_id', 'category']);
            });
        }

        // Create user_dnd_settings table for Do Not Disturb
        if (!Schema::hasTable('user_dnd_settings')) {
            Schema::create('user_dnd_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
                $table->boolean('is_enabled')->default(false);
                $table->time('start_time')->default('22:00:00');
                $table->time('end_time')->default('07:00:00');
                $table->json('allowed_categories')->nullable(); // Categories that bypass DND (urgent)
                $table->timestamps();
            });
        }

        // Create dnd_queues table for queued notifications during DND
        if (!Schema::hasTable('dnd_queues')) {
            Schema::create('dnd_queues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->morphs('notifiable');
                $table->string('notification_type');
                $table->json('data');
                $table->string('category');
                $table->boolean('is_urgent')->default(false);
                $table->timestamp('scheduled_send_at');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'scheduled_send_at']);
                $table->index(['is_urgent', 'scheduled_send_at']);
            });
        }

        // Create notification_rate_limits table for rate limiting
        if (!Schema::hasTable('notification_rate_limits')) {
            Schema::create('notification_rate_limits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('limit_type'); // push_daily, payment_daily, etc.
                $table->integer('count')->default(0);
                $table->date('reset_date');
                $table->timestamps();

                $table->unique(['user_id', 'limit_type', 'reset_date']);
                $table->index(['user_id', 'reset_date']);
            });
        }

        // Create fcm_tokens table for Firebase Cloud Messaging
        if (!Schema::hasTable('fcm_tokens')) {
            Schema::create('fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('token')->unique();
                $table->string('device_name')->nullable();
                $table->string('platform')->nullable(); // android, ios, web
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
        Schema::dropIfExists('notification_rate_limits');
        Schema::dropIfExists('dnd_queues');
        Schema::dropIfExists('user_dnd_settings');
        Schema::dropIfExists('notification_preferences');

        // Revert notifications table changes
        if (Schema::hasColumn('notifications', 'delivery_status')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn(['delivery_status', 'sent_at', 'retry_count', 'next_retry_at', 'error_message', 'channel']);
                $table->timestamp('read_at')->change();
            });
        }
    }
};
