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
        Schema::create('streak_freezes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('freeze_date');
            $table->enum('reason', ['sick', 'urgent', 'holiday'])->default('urgent');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'freeze_date']);
            $table->unique(['user_id', 'freeze_date'], 'unique_user_date_freeze');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streak_freezes');
    }
};
