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
        // Forum Categories
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Forum Threads
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('forum_categories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->string('hidden_reason')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_pinned', 'created_at']);
            $table->index(['is_flagged', 'is_hidden']);
        });

        // Forum Replies (Adjacency List with nesting level)
        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('forum_replies')->onDelete('cascade');
            $table->integer('nesting_level')->default(0);
            $table->text('content');
            $table->boolean('is_hidden')->default(false);
            $table->string('hidden_reason')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->integer('like_count')->default(0);
            $table->timestamps();

            $table->index(['thread_id', 'parent_id', 'nesting_level']);
            $table->index(['is_flagged', 'is_hidden']);
        });

        // Thread Followers (for notifications)
        Schema::create('thread_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('receive_notifications')->default(true);
            $table->timestamps();

            $table->unique(['thread_id', 'user_id']);
        });

        // Forum Attachments
        Schema::create('forum_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->nullable()->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('reply_id')->nullable()->constrained('forum_replies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->string('path');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_attachments');
        Schema::dropIfExists('thread_followers');
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('forum_categories');
    }
};
