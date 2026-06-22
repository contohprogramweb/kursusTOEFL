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
        // Add nesting level column to forum_replies for max 3 level nesting
        Schema::table('forum_replies', function (Blueprint $table) {
            $table->integer('nesting_level')->default(0)->after('parent_reply_id');
            $table->index('nesting_level');
        });

        // Add thread_followers table for notifications
        Schema::create('thread_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_following')->default(true);
            $table->timestamps();

            $table->unique(['thread_id', 'user_id']);
            $table->index('user_id');
        });

        // Add spam detection columns to forum_replies and forum_threads
        Schema::table('forum_replies', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('hide_reason');
            $table->string('flag_reason')->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('view_count');
            $table->string('flag_reason')->nullable()->after('is_flagged');
            $table->timestamp('flagged_at')->nullable()->after('flag_reason');
        });

        // Add image attachments support
        Schema::create('forum_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // forum_thread or forum_reply
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size'); // in bytes
            $table->string('original_width')->nullable();
            $table->string('original_height')->nullable();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_attachments');
        
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'flag_reason', 'flagged_at']);
        });

        Schema::table('forum_replies', function (Blueprint $table) {
            $table->dropColumn(['nesting_level', 'is_flagged', 'flag_reason', 'flagged_at']);
        });

        Schema::dropIfExists('thread_followers');
    }
};
