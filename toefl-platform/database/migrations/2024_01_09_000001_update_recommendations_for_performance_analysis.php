<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fitur: Generate rekomendasi berbasis performa (FR-3.2.4, FR-3.6.3)
     * - Analisis gap antara skor saat ini dan target
     * - Identifikasi 3 micro-skills terlemah
     * - Rekomendasi: modul spesifik, latihan targeted, tips strategi, jadwal simulasi berikutnya
     * - Urutkan berdasarkan impact (skill paling mempengaruhi skor total)
     * - Max 5 rekomendasi per simulasi
     * - Sesuaikan dengan sisa waktu hingga ujian (urgency factor)
     */
    public function up(): void
    {
        // Update tabel recommendations untuk menyimpan hasil analisis performa
        Schema::table('recommendations', function (Blueprint $table) {
            $table->foreignId('simulation_id')->nullable()->after('user_id')
                  ->constrained('simulations')->onDelete('cascade');
            
            $table->string('category')->nullable()->after('type'); // reading, listening, speaking, writing, strategy, schedule
            $table->string('micro_skill')->nullable()->after('category'); // micro-skill spesifik
            $table->integer('impact_score')->default(0)->after('priority'); // skor impact untuk sorting
            $table->integer('urgency_factor')->default(1)->after('impact_score'); // faktor urgensi (1-5)
            $table->text('action_plan')->nullable()->after('reason'); // langkah konkret yang harus dilakukan
            $table->json('metadata')->nullable()->after('action_plan'); // data tambahan (current_score, target_score, dll)
            $table->timestamp('generated_at')->nullable()->after('is_read');
            
            $table->index(['user_id', 'impact_score']);
            $table->index(['user_id', 'urgency_factor']);
            $table->index(['simulation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropForeign(['simulation_id']);
            $table->dropIndex(['user_id', 'impact_score']);
            $table->dropIndex(['user_id', 'urgency_factor']);
            $table->dropIndex(['simulation_id']);
            
            $table->dropColumn([
                'simulation_id',
                'category',
                'micro_skill',
                'impact_score',
                'urgency_factor',
                'action_plan',
                'metadata',
                'generated_at'
            ]);
        });
    }
};
