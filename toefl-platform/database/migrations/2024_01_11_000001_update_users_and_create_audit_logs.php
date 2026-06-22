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
        // Update users table for additional fields
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active')->after('email');
            $table->text('suspension_reason')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            $table->foreignId('suspended_by')->nullable()->constrained('users')->onDelete('set null')->after('suspended_at');
            $table->softDeletes();
            
            // Indexes for filtering and search
            $table->index(['role', 'status']);
            $table->index(['name', 'email']);
            $table->index('created_at');
        });

        // Audit logs table
        Schema::create('user_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User being modified
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // Admin who performed action
            $table->string('action'); // created, updated, suspended, unsuspended, deleted, restored, role_changed
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable(); // For suspension or bulk actions
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
            $table->index('admin_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_audit_logs');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropIndex(['name', 'email']);
            $table->dropIndex('created_at');
            
            $table->dropColumn([
                'status',
                'suspension_reason',
                'suspended_at',
                'suspended_by',
            ]);
            
            // Note: softDeletes() adds 'deleted_at', we drop it if this migration is rolled back
            // but usually you wouldn't drop deleted_at if other models use it. 
            // For safety in rollback of this specific feature:
            if (Schema::hasColumn('users', 'deleted_at')) {
               // Only drop if we are sure it was added here and not used elsewhere
               // In a real scenario, be careful with dropping shared columns.
               // Assuming 'deleted_at' might be used elsewhere, we skip dropping it in down() 
               // or check if it's safe. For this exercise, we'll leave deleted_at or assume 
               // a fresh DB for rollback.
               // Let's assume for this migration context we manage it:
               // Actually, best practice: don't drop deleted_at in down() if it could be used by others.
               // But to be strictly reversible for THIS migration's additions:
               // We will NOT drop deleted_at in down() to avoid breaking other potential uses.
               // The other columns are safe.
            }
        });
    }
};
