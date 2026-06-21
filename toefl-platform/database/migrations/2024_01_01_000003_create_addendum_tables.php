<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('course_type', ['private', 'group', 'intensive', 'online', 'hybrid']);
            $table->integer('duration_weeks')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->json('features')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('max_students')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('course_type');
            $table->index('status');
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('discount_type', ['percentage', 'fixed', 'free_item']);
            $table->decimal('discount_value', 12, 2);
            $table->integer('max_usage')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('max_usage_per_user')->default(1);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('applicable_packages')->nullable(); // package IDs or 'all'
            $table->boolean('stackable')->default(false);
            $table->json('eligibility_rules')->nullable(); // e.g., min_purchase, user_type
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->foreignId('created_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('code');
            $table->index('status');
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('referral_code')->unique();
            $table->string('tracking_url')->nullable();
            $table->enum('status', ['pending', 'converted', 'rewarded', 'fraud', 'expired'])->default('pending');
            $table->timestamp('conversion_date')->nullable();
            $table->decimal('reward_amount', 12, 2)->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->json('fraud_flags')->nullable();
            $table->timestamps();
            
            $table->index('referrer_id');
            $table->index('referee_id');
            $table->index('status');
            $table->index('referral_code');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained('course_packages')->onDelete('set null');
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->onDelete('set null');
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->onDelete('set null');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'expired', 'refunded'])->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_order_id')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->timestamps();
            
            $table->index('order_code');
            $table->index('user_id');
            $table->index('status');
            $table->index('midtrans_order_id');
        });

        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['request', 'webhook', 'polling', 'notification', 'retry', 'error']);
            $table->json('midtrans_payload');
            $table->string('status_code')->nullable();
            $table->string('status_message')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('event_type');
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('UTC');
            $table->enum('location_type', ['online', 'offline', 'hybrid']);
            $table->string('location_detail')->nullable(); // URL for online, address for offline
            $table->foreignId('instructor_id')->constrained('users')->onDelete('set null');
            $table->json('material_urls')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            
            $table->index('class_id');
            $table->index('session_date');
            $table->index('status');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('class_schedules')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('absent');
            $table->text('notes')->nullable();
            $table->string('attachment_url')->nullable();
            $table->foreignId('marked_by')->constrained('users')->onDelete('set null');
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            
            $table->unique(['schedule_id', 'student_id']);
            $table->index('schedule_id');
            $table->index('student_id');
        });

        Schema::create('parent_student_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'active', 'revoked'])->default('pending');
            $table->foreignId('invited_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['parent_id', 'student_id']);
            $table->index('parent_id');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student_links');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('course_packages');
    }
};
