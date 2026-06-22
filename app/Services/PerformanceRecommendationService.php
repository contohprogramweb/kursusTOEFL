<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\Simulation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Performance-Based Recommendation Generator Service
 * 
 * Fitur: Generate rekomendasi berbasis performa (FR-3.2.4, FR-3.6.3)
 * - Analisis gap antara skor saat ini dan target
 * - Identifikasi 3 micro-skills terlemah
 * - Rekomendasi: modul spesifik, latihan targeted, tips strategi, jadwal simulasi berikutnya
 * - Urutkan berdasarkan impact (skill paling mempengaruhi skor total)
 * - Max 5 rekomendasi per simulasi
 * - Sesuaikan dengan sisa waktu hingga ujian (urgency factor)
 */
class PerformanceRecommendationService
{
    /**
     * Micro-skills mapping untuk TOEFL ITP
     */
    protected array $microSkillsMap = [
        'reading' => [
            'main_idea' => 'Menemukan Ide Utama',
            'detail_info' => 'Informasi Detail',
            'inference' => 'Kesimpulan/Inferensi',
            'vocabulary' => 'Kosa Kata dalam Konteks',
            'reference' => 'Referensi Pronoun',
            'sentence_simplification' => 'Penyederhanaan Kalimat',
            'insert_text' => 'Menyisipkan Teks',
            'summary' => 'Ringkasan',
        ],
        'listening' => [
            'gist_content' => 'Pemahaman Isi Utama',
            'gist_purpose' => 'Tujuan Pembicaraan',
            'detail_info' => 'Informasi Detail',
            'attitude_speaker' => 'Sikap Pembicara',
            'organization' => 'Organisasi Informasi',
            'connecting_content' => 'Menghubungkan Konten',
            'inference' => 'Kesimpulan',
        ],
        'structure' => [
            'subject_verb_agreement' => 'Kesesuaian Subjek-Verb',
            'verb_forms' => 'Bentuk Verb',
            'modifiers' => 'Modifier',
            'parallel_structure' => 'Struktur Paralel',
            'comparison' => 'Perbandingan',
            'negation' => 'Negasi',
            'clauses' => 'Klausa',
            'prepositions' => 'Preposisi',
        ],
        'writing' => [
            'grammar_usage' => 'Tata Bahasa',
            'sentence_structure' => 'Struktur Kalimat',
            'organization' => 'Organisasi Esai',
            'development' => 'Pengembangan Ide',
            'vocabulary_range' => 'Variasi Kosa Kata',
            'mechanics' => 'Ejaan & Tanda Baca',
        ],
    ];

    /**
     * Bobot impact setiap micro-skill terhadap skor total
     */
    protected array $impactWeights = [
        'reading' => [
            'main_idea' => 0.20,
            'detail_info' => 0.25,
            'inference' => 0.15,
            'vocabulary' => 0.15,
            'reference' => 0.08,
            'sentence_simplification' => 0.07,
            'insert_text' => 0.05,
            'summary' => 0.05,
        ],
        'listening' => [
            'gist_content' => 0.18,
            'detail_info' => 0.28,
            'inference' => 0.15,
            'attitude_speaker' => 0.12,
            'organization' => 0.10,
            'connecting_content' => 0.10,
            'gist_purpose' => 0.07,
        ],
        'structure' => [
            'subject_verb_agreement' => 0.18,
            'verb_forms' => 0.18,
            'clauses' => 0.15,
            'modifiers' => 0.12,
            'parallel_structure' => 0.12,
            'comparison' => 0.10,
            'prepositions' => 0.08,
            'negation' => 0.07,
        ],
        'writing' => [
            'grammar_usage' => 0.25,
            'sentence_structure' => 0.20,
            'organization' => 0.18,
            'development' => 0.17,
            'vocabulary_range' => 0.12,
            'mechanics' => 0.08,
        ],
    ];

    /**
     * Template rekomendasi berdasarkan tipe dan kategori
     */
    protected array $recommendationTemplates = [
        'module' => [
            'title' => 'Pelajari Modul: {skill_name}',
            'reason' => 'Skor Anda di {skill_name} adalah {current_score}, masih {gap_points} poin di bawah target.',
            'action_plan' => '1. Selesaikan modul "{skill_name}" dalam 2-3 hari\n2. Kerjakan semua latihan di akhir modul\n3. Review penjelasan untuk jawaban yang salah\n4. Catat rumus/pola penting di buku catatan',
        ],
        'practice' => [
            'title' => 'Latihan Targeted: {skill_name}',
            'reason' => 'Anda hanya benar {correct_percentage}% di {skill_name}. Perlu lebih banyak latihan.',
            'action_plan' => '1. Kerjakan 15-20 soal latihan khusus {skill_name}\n2. Fokus pada pemahaman konsep, bukan kecepatan\n3. Analisis kesalahan setelah setiap sesi\n4. Ulangi hingga mencapai 80%+',
        ],
        'strategy' => [
            'title' => 'Strategi: {skill_name}',
            'reason' => 'Pendekatan Anda di {skill_name} kurang efektif berdasarkan pola jawaban.',
            'action_plan' => '1. Pelajari strategi khusus untuk {skill_name}\n2. Terapkan teknik scanning/skimming untuk reading\n3. Gunakan metode eliminasi untuk pilihan ganda\n4. Latihan time management per soal',
        ],
        'simulation' => [
            'title' => 'Jadwal Simulasi Berikutnya',
            'reason' => 'Untuk memantau progress, Anda perlu simulasi rutin.',
            'action_plan' => '1. Jadwalkan simulasi penuh setiap 7-10 hari\n2. Simulasikan kondisi ujian sebenarnya (waktu, lingkungan)\n3. Review mendalam setelah setiap simulasi\n4. Track improvement di setiap micro-skill',
        ],
        'schedule' => [
            'title' => 'Adjustment Jadwal Belajar',
            'reason' => 'Dengan sisa {days_until_test} hari, Anda perlu intensif di {skill_name}.',
            'action_plan' => '1. Alokasikan {hours_per_day} jam/hari untuk {skill_name}\n2. Prioritaskan sesi pagi saat fokus maksimal\n3. Gunakan teknik Pomodoro (25 menit fokus, 5 menit istirahat)\n4. Weekend: simulasi + review mendalam',
        ],
    ];

    /**
     * Generate rekomendasi berdasarkan hasil simulasi
     * 
     * @param Simulation $simulation
     * @param array $userProfile (target_score, test_date, current_level)
     * @return Collection
     */
    public function generateFromSimulation(Simulation $simulation, array $userProfile = []): Collection
    {
        $recommendations = collect();
        
        // Extract data dari simulasi
        $microSkills = $simulation->micro_skills ?? [];
        $totalScore = $simulation->total_score;
        $sectionScores = [
            'reading' => $simulation->reading_score ?? 0,
            'listening' => $simulation->listening_score ?? 0,
            'structure' => $simulation->speaking_score ?? 0, // Using speaking as structure placeholder
        ];
        
        // Default user profile jika tidak disediakan
        $targetScore = $userProfile['target_score'] ?? 550;
        $testDate = isset($userProfile['test_date']) ? Carbon::parse($userProfile['test_date']) : now()->addDays(60);
        $daysUntilTest = max(1, $testDate->diffInDays(now()));
        
        // Hitung urgency factor (1-5)
        $urgencyFactor = $this->calculateUrgencyFactor($daysUntilTest);
        
        // 1. Analisis gap skor
        $scoreGap = max(0, $targetScore - $totalScore);
        
        if ($scoreGap > 0) {
            $recommendations = $recommendations->merge(
                $this->analyzeScoreGap($simulation, $scoreGap, $targetScore, $urgencyFactor, $daysUntilTest)
            );
        }
        
        // 2. Identifikasi 3 micro-skills terlemah
        $weakSkills = $this->identifyWeakestSkills($microSkills, $sectionScores);
        
        foreach ($weakSkills as $index => $weakSkill) {
            $recommendations = $recommendations->merge(
                $this->generateWeakSkillRecommendations(
                    $simulation,
                    $weakSkill,
                    $index + 1, // priority (1 = highest)
                    $urgencyFactor,
                    $daysUntilTest,
                    $targetScore
                )
            );
        }
        
        // 3. Tambahkan rekomendasi strategis jika ada pola tertentu
        $recommendations = $recommendations->merge(
            $this->generateStrategicRecommendations($simulation, $microSkills, $urgencyFactor, $daysUntilTest)
        );
        
        // 4. Sort by impact score dan ambil max 5
        $recommendations = $recommendations
            ->sortByDesc('impact_score')
            ->thenByDesc('urgency_factor')
            ->take(5)
            ->values();
        
        // 5. Simpan ke database
        $savedRecommendations = $this->saveRecommendations($recommendations, $simulation->user_id, $simulation->id);
        
        return $savedRecommendations;
    }

    /**
     * Hitung urgency factor berdasarkan sisa waktu
     */
    protected function calculateUrgencyFactor(int $daysUntilTest): int
    {
        if ($daysUntilTest <= 7) {
            return 5; // Critical
        } elseif ($daysUntilTest <= 14) {
            return 4; // High
        } elseif ($daysUntilTest <= 30) {
            return 3; // Medium
        } elseif ($daysUntilTest <= 60) {
            return 2; // Low
        }
        return 1; // Very low
    }

    /**
     * Analisis gap skor dan generate rekomendasi
     */
    protected function analyzeScoreGap(
        Simulation $simulation,
        int $scoreGap,
        int $targetScore,
        int $urgencyFactor,
        int $daysUntilTest
    ): Collection {
        $recommendations = collect();
        
        // Hitung required improvement per hari
        $requiredDailyImprovement = $scoreGap / max(1, $daysUntilTest);
        
        // Rekomendasi schedule adjustment
        if ($requiredDailyImprovement > 2) {
            $recommendations->push([
                'type' => 'schedule',
                'category' => 'general',
                'micro_skill' => null,
                'title' => 'Intensifikasi Belajar Diperlukan',
                'reason' => "Anda perlu meningkatkan rata-rata {$requiredDailyImprovement} poin/hari untuk mencapai target {$targetScore}.",
                'action_plan' => "1. Tingkatkan durasi belajar menjadi " . round($requiredDailyImprovement * 0.5, 1) . " jam/hari\n2. Fokus pada section dengan bobot tertinggi\n3. Kurangi distraksi dan tingkatkan konsistensi\n4. Gunakan weekend untuk simulasi penuh + review",
                'priority' => 1,
                'impact_score' => min(100, $scoreGap),
                'urgency_factor' => $urgencyFactor,
                'metadata' => [
                    'score_gap' => $scoreGap,
                    'target_score' => $targetScore,
                    'current_score' => $simulation->total_score,
                    'days_until_test' => $daysUntilTest,
                    'daily_improvement_needed' => round($requiredDailyImprovement, 2),
                ],
            ]);
        }
        
        return $recommendations;
    }

    /**
     * Identifikasi 3 micro-skills terlemah dari hasil simulasi
     */
    protected function identifyWeakestSkills(array $microSkills, array $sectionScores): array
    {
        $skillPerformance = [];
        
        // Jika micro_skills tersedia dari simulasi
        if (!empty($microSkills)) {
            foreach ($microSkills as $category => $skills) {
                if (is_array($skills)) {
                    foreach ($skills as $skill => $data) {
                        $accuracy = is_array($data) ? ($data['correct'] / max(1, $data['total'])) * 100 : $data;
                        $weight = $this->impactWeights[$category][$skill] ?? 0.1;
                        
                        $skillPerformance[] = [
                            'category' => $category,
                            'skill' => $skill,
                            'accuracy' => is_numeric($accuracy) ? $accuracy : 50,
                            'weight' => $weight,
                            'impact' => (100 - ($accuracy ?? 50)) * $weight, // Higher impact = worse performance * weight
                        ];
                    }
                }
            }
        } else {
            // Fallback: gunakan section scores untuk estimasi
            foreach ($sectionScores as $section => $score) {
                $maxScore = $section === 'structure' ? 42 : 50; // Structure max is 42, others 50
                $accuracy = ($score / $maxScore) * 100;
                
                // Ambil top skill dengan weight tertinggi di section ini
                $topSkill = collect($this->impactWeights[$section] ?? [])
                    ->sortDesc()
                    ->keys()
                    ->first();
                
                if ($topSkill) {
                    $skillPerformance[] = [
                        'category' => $section,
                        'skill' => $topSkill,
                        'accuracy' => $accuracy,
                        'weight' => $this->impactWeights[$section][$topSkill] ?? 0.1,
                        'impact' => (100 - $accuracy) * ($this->impactWeights[$section][$topSkill] ?? 0.1),
                    ];
                }
            }
        }
        
        // Sort by impact (highest first = weakest skills)
        $sorted = collect($skillPerformance)
            ->sortByDesc('impact')
            ->take(3)
            ->toArray();
        
        return $sorted;
    }

    /**
     * Generate rekomendasi untuk weak skills
     */
    protected function generateWeakSkillRecommendations(
        Simulation $simulation,
        array $weakSkill,
        int $priorityOffset,
        int $urgencyFactor,
        int $daysUntilTest,
        int $targetScore
    ): Collection {
        $recommendations = collect();
        
        $category = $weakSkill['category'];
        $skill = $weakSkill['skill'];
        $accuracy = $weakSkill['accuracy'];
        $skillName = $this->microSkillsMap[$category][$skill] ?? $skill;
        $correctPercentage = round($accuracy);
        
        // 1. Rekomendasi modul
        $template = $this->recommendationTemplates['module'];
        $recommendations->push([
            'type' => 'module',
            'category' => $category,
            'micro_skill' => $skill,
            'title' => str_replace('{skill_name}', $skillName, $template['title']),
            'reason' => str_replace(
                ['{skill_name}', '{current_score}', '{gap_points}'],
                [$skillName, $correctPercentage . '%', (100 - $correctPercentage)],
                $template['reason']
            ),
            'action_plan' => str_replace('{skill_name}', $skillName, $template['action_plan']),
            'priority' => $priorityOffset,
            'impact_score' => round((100 - $correctPercentage) * ($weakSkill['weight'] * 100)),
            'urgency_factor' => $urgencyFactor,
            'metadata' => [
                'category' => $category,
                'skill' => $skill,
                'skill_name' => $skillName,
                'accuracy' => $correctPercentage,
                'weight' => $weakSkill['weight'],
                'target_score' => $targetScore,
                'test_date' => now()->addDays($daysUntilTest)->toDateString(),
            ],
        ]);
        
        // 2. Rekomendasi practice jika accuracy < 60%
        if ($correctPercentage < 60) {
            $template = $this->recommendationTemplates['practice'];
            $recommendations->push([
                'type' => 'practice',
                'category' => $category,
                'micro_skill' => $skill,
                'title' => str_replace('{skill_name}', $skillName, $template['title']),
                'reason' => str_replace(
                    ['{correct_percentage}', '{skill_name}'],
                    [$correctPercentage, $skillName],
                    $template['reason']
                ),
                'action_plan' => str_replace('{skill_name}', $skillName, $template['action_plan']),
                'priority' => $priorityOffset + 0.5,
                'impact_score' => round((100 - $correctPercentage) * ($weakSkill['weight'] * 80)),
                'urgency_factor' => min(5, $urgencyFactor + 1),
                'metadata' => [
                    'category' => $category,
                    'skill' => $skill,
                    'skill_name' => $skillName,
                    'accuracy' => $correctPercentage,
                    'recommended_questions' => 20,
                ],
            ]);
        }
        
        // 3. Rekomendasi strategy jika accuracy 60-75%
        if ($correctPercentage >= 60 && $correctPercentage < 75) {
            $template = $this->recommendationTemplates['strategy'];
            $recommendations->push([
                'type' => 'strategy',
                'category' => $category,
                'micro_skill' => $skill,
                'title' => str_replace('{skill_name}', $skillName, $template['title']),
                'reason' => str_replace('{skill_name}', $skillName, $template['reason']),
                'action_plan' => str_replace('{skill_name}', $skillName, $template['action_plan']),
                'priority' => $priorityOffset + 1,
                'impact_score' => round((100 - $correctPercentage) * ($weakSkill['weight'] * 60)),
                'urgency_factor' => $urgencyFactor,
                'metadata' => [
                    'category' => $category,
                    'skill' => $skill,
                    'skill_name' => $skillName,
                    'strategy_focus' => 'optimization',
                ],
            ]);
        }
        
        return $recommendations;
    }

    /**
     * Generate rekomendasi strategis berdasarkan pola
     */
    protected function generateStrategicRecommendations(
        Simulation $simulation,
        array $microSkills,
        int $urgencyFactor,
        int $daysUntilTest
    ): Collection {
        $recommendations = collect();
        
        // Cek time management dari duration
        $durationMinutes = ($simulation->duration_seconds ?? 0) / 60;
        
        // Jika terlalu cepat (< 80% waktu normal), mungkin terburu-buru
        if ($durationMinutes > 0 && $durationMinutes < 90) {
            $template = $this->recommendationTemplates['strategy'];
            $recommendations->push([
                'type' => 'strategy',
                'category' => 'time_management',
                'micro_skill' => 'pacing',
                'title' => 'Manajemen Waktu Perlu Diperbaiki',
                'reason' => "Anda menyelesaikan simulasi dalam " . round($durationMinutes) . " menit. Terlalu cepat dapat menyebabkan kesalahan ceroboh.",
                'action_plan' => "1. Alokasikan waktu spesifik per bagian (Reading: 55 menit, Listening: 35 menit)\n2. Jangan menghabiskan > 2 menit per soal reading\n3. Lewati soal sulit, kembali nanti jika ada waktu\n4. Sisakan 5 menit di akhir untuk review",
                'priority' => 2,
                'impact_score' => 65,
                'urgency_factor' => $urgencyFactor,
                'metadata' => [
                    'duration_minutes' => round($durationMinutes),
                    'recommended_duration' => 115,
                    'issue' => 'too_fast',
                ],
            ]);
        }
        
        // Rekomendasi simulasi berikutnya
        $nextSimulationDays = min(10, max(5, floor($daysUntilTest / 6)));
        $template = $this->recommendationTemplates['simulation'];
        $recommendations->push([
            'type' => 'simulation',
            'category' => 'schedule',
            'micro_skill' => null,
            'title' => $template['title'],
            'reason' => "Simulasi berikutnya disarankan dalam {$nextSimulationDays} hari untuk mengukur progress.",
            'action_plan' => str_replace('{days}', $nextSimulationDays, $template['action_plan']),
            'priority' => 3,
            'impact_score' => 50,
            'urgency_factor' => max(1, $urgencyFactor - 1),
            'metadata' => [
                'next_simulation_date' => now()->addDays($nextSimulationDays)->toDateString(),
                'recommended_frequency' => 'every_' . $nextSimulationDays . '_days',
            ],
        ]);
        
        return $recommendations;
    }

    /**
     * Save recommendations ke database
     */
    protected function saveRecommendations(Collection $recommendations, int $userId, int $simulationId): Collection
    {
        $saved = collect();
        
        foreach ($recommendations as $rec) {
            $recommendation = Recommendation::updateOrCreate(
                [
                    'user_id' => $userId,
                    'simulation_id' => $simulationId,
                    'type' => $rec['type'],
                    'category' => $rec['category'],
                    'micro_skill' => $rec['micro_skill'],
                ],
                array_merge($rec, [
                    'generated_at' => now(),
                ])
            );
            
            $saved->push($recommendation);
        }
        
        return $saved;
    }

    /**
     * Get recommendations for user with filters
     */
    public function getUserRecommendations(
        int $userId,
        ?int $limit = 5,
        ?string $category = null,
        bool $unreadOnly = false
    ): Collection {
        $query = Recommendation::where('user_id', $userId);
        
        if ($unreadOnly) {
            $query->where('is_read', false);
        }
        
        if ($category) {
            $query->where('category', $category);
        }
        
        return $query->orderBy('urgency_factor', 'desc')
                     ->orderBy('impact_score', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Mark recommendation as read
     */
    public function markAsRead(int $recommendationId): bool
    {
        $recommendation = Recommendation::find($recommendationId);
        
        if ($recommendation) {
            return $recommendation->markAsRead();
        }
        
        return false;
    }

    /**
     * Clear old recommendations (optional maintenance)
     */
    public function clearOldRecommendations(int $userId, int $daysOld = 30): int
    {
        return Recommendation::where('user_id', $userId)
                             ->where('generated_at', '<', now()->subDays($daysOld))
                             ->where('is_read', true)
                             ->delete();
    }
}
