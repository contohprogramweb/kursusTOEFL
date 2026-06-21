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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('full_name');
            $table->enum('role', ['admin', 'instructor', 'student', 'parent'])->default('student');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending_verification'])->default('pending_verification');
            $table->boolean('email_verified')->default(false);
            $table->string('sso_provider')->nullable();
            $table->string('sso_id')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('role');
            $table->index('status');
            $table->index(['sso_provider', 'sso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
