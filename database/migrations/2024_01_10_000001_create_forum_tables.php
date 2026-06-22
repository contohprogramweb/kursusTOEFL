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
        // Update forum_categories table
        if (!Schema::hasTable('forum_categories')) {
            Schema::create('forum_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Update forum_threads table
        if (!Schema::hasTable('forum_threads')) {
            Schema::create('forum_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('forum_categories')->onDelete('cascade');
                $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->text('content');
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->boolean('is_hidden')->default(false);
                $table->string('hidden_reason')->nullable();
                $table->foreignId('hidden_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('hidden_at')->nullable();
                $table->unsignedInteger('view_count')->default(0);
                $table->unsignedInteger('reply_count')->default(0);
                $table->boolean('is_flagged')->default(false);
                $table->string('flag_reason')->nullable();
                $table->timestamp('flagged_at')->nullable();
                $table->timestamps();

                $table->index(['category_id', 'is_pinned', 'created_at']);
                $table->index(['is_flagged', 'created_at']);
            });
        }

        // Create forum_replies table with adjacency list
        if (!Schema::hasTable('forum_replies')) {
            Schema::create('forum_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
                $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('parent_id')->nullable()->constrained('forum_replies')->onDelete('cascade');
                $table->integer('nesting_level')->default(0);
                $table->text('content');
                $table->boolean('is_hidden')->default(false);
                $table->string('hidden_reason')->nullable();
                $table->foreignId('hidden_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('hidden_at')->nullable();
                $table->boolean('is_flagged')->default(false);
                $table->string('flag_reason')->nullable();
                $table->timestamp('flagged_at')->nullable();
                $table->timestamps();

                $table->index(['thread_id', 'parent_id', 'created_at']);
                $table->index(['nesting_level', 'created_at']);
                $table->index(['is_flagged', 'created_at']);
            });
        }

        // Create thread_followers table for notifications
        if (!Schema::hasTable('thread_followers')) {
            Schema::create('thread_followers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->boolean('receives_notifications')->default(true);
                $table->timestamps();

                $table->unique(['thread_id', 'user_id']);
                $table->index(['user_id', 'receives_notifications']);
            });
        }

        // Create forum_attachments table for image uploads
        if (!Schema::hasTable('forum_attachments')) {
            Schema::create('forum_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attachable_id');
                $table->string('attachable_type'); // forum_threads or forum_replies
                $table->string('file_name');
                $table->string('original_name');
                $table->string('mime_type');
                $table->unsignedInteger('file_size');
                $table->integer('width')->nullable();
                $table->integer('height')->nullable();
                $table->string('storage_path');
                $table->timestamps();

                $table->index(['attachable_type', 'attachable_id']);
            });
        }

        // Seed default categories
        DB::table('forum_categories')->insertOrIgnore([
            ['name' => 'Umum', 'slug' => 'umum', 'description' => 'Diskusi umum tentang pembelajaran', 'sort_order' => 1],
            ['name' => 'Reading', 'slug' => 'reading', 'description' => 'Diskusi tentang Reading TOEFL', 'sort_order' => 2],
            ['name' => 'Listening', 'slug' => 'listening', 'description' => 'Diskusi tentang Listening TOEFL', 'sort_order' => 3],
            ['name' => 'Speaking', 'slug' => 'speaking', 'description' => 'Diskusi tentang Speaking TOEFL', 'sort_order' => 4],
            ['name' => 'Writing', 'slug' => 'writing', 'description' => 'Diskusi tentang Writing TOEFL', 'sort_order' => 5],
            ['name' => 'Simulasi', 'slug' => 'simulasi', 'description' => 'Diskusi tentang simulasi TOEFL', 'sort_order' => 6],
            ['name' => 'Institusi', 'slug' => 'institusi', 'description' => 'Informasi dan diskusi terkait institusi', 'sort_order' => 7],
        ]);
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
