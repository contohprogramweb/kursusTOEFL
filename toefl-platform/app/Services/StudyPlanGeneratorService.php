<?php

namespace App\Services;

use App\Models\StudyPlan;
use App\Models\StudyPlanTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Study Plan Generator Service (FR-3.2.4, FR-3.6.3)
 * 
 * Menghasilkan rencana belajar berdasarkan:
 * - Target score
 * - Test date
 * - Jam belajar per hari
 * - Hari tersedia
 * 
 * Algoritma:
 * 1. Hitung total waktu yang tersedia hingga test date
 * 2. Prioritaskan section dengan skor terendah
 * 3. Distribusikan jam belajar merata
 * 4. Sisipkan simulasi mingguan
 */
class StudyPlanGeneratorService
{
    // Minimum minutes per session
    private const MIN_SESSION_MINUTES = 30;
    
    // Default task durations by type
    private const TASK_DURATIONS = [
        'module' => 45,      // 45 menit per modul
        'practice' => 30,    // 30 menit latihan
        'simulation' => 120, // 2 jam simulasi full
        'review' => 30,      // 30 menit review
    ];

    // TOEFL sections
    private const SECTIONS = ['reading', 'listening', 'speaking', 'writing'];

    /**
     * Generate study plan berdasarkan input user
     * 
     * @param User $user
     * @param int $targetScore
     * @param Carbon $testDate
     * @param float $dailyHours
     * @param array $availableDays [0,1,2,3,4,5,6] - 0=Sunday
     * @param string|null $planName
     * @return StudyPlan
     */
    public function generatePlan(
        User $user,
        int $targetScore,
        Carbon $testDate,
        float $dailyHours = 2.0,
        array $availableDays = [1, 2, 3, 4, 5],
        ?string $planName = null
    ): StudyPlan {
        DB::beginTransaction();
        
        try {
            $today = now()->startOfDay();
            $testDate = $testDate->startOfDay();
            
            // Validasi: test date harus di masa depan
            if ($testDate->isPast() || $testDate->isSameDay($today)) {
                throw new \InvalidArgumentException('Test date harus di masa depan');
            }

            // Hitung jumlah hari tersisa
            $daysRemaining = $today->diffInDays($testDate);
            
            // Hitung jumlah hari belajar yang tersedia
            $availableDaysCount = $this->countAvailableDaysInRange($today, $testDate, $availableDays);
            
            // Total jam belajar yang tersedia
            $totalStudyHours = $availableDaysCount * $dailyHours;
            $totalStudyMinutes = $totalStudyHours * 60;

            // Buat study plan
            $studyPlan = StudyPlan::create([
                'user_id' => $user->id,
                'name' => $planName ?? "Study Plan - {$testDate->format('d M Y')}",
                'target_score' => $targetScore,
                'test_date' => $testDate,
                'daily_hours' => $dailyHours,
                'available_days' => $availableDays,
                'start_date' => $today,
                'end_date' => $testDate,
                'status' => 'active',
                'is_ai_generated' => true,
                'ai_notes' => $this->generateAiNotes($targetScore, $daysRemaining, $totalStudyHours),
            ]);

            // Dapatkan rekomendasi section prioritas berdasarkan skor terakhir user
            $sectionPriorities = $this->calculateSectionPriorities($user, $targetScore);

            // Generate tasks
            $tasks = $this->generateTasks(
                $studyPlan,
                $today,
                $testDate,
                $availableDays,
                $dailyHours,
                $sectionPriorities,
                $targetScore
            );

            // Update total tasks count
            $studyPlan->update([
                'total_tasks' => count($tasks),
                'completed_tasks' => 0,
            ]);

            DB::commit();
            
            return $studyPlan->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hitung jumlah hari tersedia dalam rentang tanggal
     */
    private function countAvailableDaysInRange(Carbon $startDate, Carbon $endDate, array $availableDays): int
    {
        $count = 0;
        $current = $startDate->copy();
        
        while ($current->lte($endDate)) {
            if (in_array($current->dayOfWeek, $availableDays)) {
                $count++;
            }
            $current->addDay();
        }
        
        return $count;
    }

    /**
     * Hitung prioritas section berdasarkan skor terakhir user
     * Return array dengan priority score (lebih tinggi = lebih prioritas)
     */
    private function calculateSectionPriorities(User $user, int $targetScore): array
    {
        // Ambil skor terakhir dari simulation results
        $lastResult = DB::table('simulation_results')
            ->where('user_id', $user->id)
            ->orderByDesc('completed_at')
            ->first();

        $priorities = [];
        
        if ($lastResult) {
            // Skor saat ini per section (asumsi max 140 per section untuk ITP)
            $scores = [
                'reading' => $lastResult->reading_score ?? 0,
                'listening' => $lastResult->listening_score ?? 0,
                'speaking' => $lastResult->speaking_score ?? 0,
                'writing' => $lastResult->writing_score ?? 0,
            ];

            // Hitung gap dengan target (target per section = targetScore / 315 * 140 approx)
            $targetPerSection = ($targetScore / 315) * 140; // Normalisasi ke max 140 per section

            foreach (self::SECTIONS as $section) {
                $currentScore = $scores[$section] ?? 0;
                $gap = max(0, $targetPerSection - $currentScore);
                
                // Priority score: gap lebih besar = prioritas lebih tinggi
                $priorities[$section] = round($gap, 2);
            }
        } else {
            // Tidak ada data sebelumnya, semua section sama prioritasnya
            foreach (self::SECTIONS as $section) {
                $priorities[$section] = 5.0; // Medium priority
            }
        }

        // Normalize priorities to 1-10 scale
        $maxPriority = max($priorities);
        if ($maxPriority > 0) {
            foreach ($priorities as &$priority) {
                $priority = max(1, round(($priority / $maxPriority) * 9) + 1); // Scale 1-10
            }
        }

        return $priorities;
    }

    /**
     * Generate AI notes untuk study plan
     */
    private function generateAiNotes(int $targetScore, int $daysRemaining, float $totalHours): string
    {
        $notes = [];
        
        if ($daysRemaining <= 7) {
            $notes[] = "⚠️ Waktu sangat terbatas! Fokus pada simulasi dan review materi penting.";
        } elseif ($daysRemaining <= 30) {
            $notes[] = "📅 Waktu cukup untuk persiapan intensif. Pertahankan konsistensi.";
        } else {
            $notes[] = "✅ Waktu persiapan baik. Manfaatkan untuk membangun fondasi kuat.";
        }

        if ($targetScore >= 550) {
            $notes[] = "🎯 Target tinggi! Diperlukan fokus ekstra pada semua section.";
        } elseif ($targetScore >= 450) {
            $notes[] = "📈 Target moderat. Tingkatkan gradually dengan latihan rutin.";
        } else {
            $notes[] = "🌟 Target dapat dicapai. Fokus pada pemahaman dasar.";
        }

        $notes[] = "Total waktu belajar: " . round($totalHours) . " jam";
        $notes[] = "Durasi: {$daysRemaining} hari";

        return implode(" ", $notes);
    }

    /**
     * Generate daftar tasks untuk study plan
     */
    private function generateTasks(
        StudyPlan $studyPlan,
        Carbon $startDate,
        Carbon $endDate,
        array $availableDays,
        float $dailyHours,
        array $sectionPriorities,
        int $targetScore
    ): array {
        $tasks = [];
        $currentDate = $startDate->copy();
        $taskOrder = 1;
        $weekNumber = 1;
        $dailyMinutes = $dailyHours * 60;

        // Sort sections by priority (highest first)
        arsort($sectionPriorities);
        $sortedSections = array_keys($sectionPriorities);

        while ($currentDate->lte($endDate)) {
            // Skip jika hari ini tidak tersedia
            if (!in_array($currentDate->dayOfWeek, $availableDays)) {
                $currentDate->addDay();
                continue;
            }

            $remainingMinutes = $dailyMinutes;
            $dayTasks = [];

            // Setiap minggu (7 hari), sisipkan simulasi
            $isSimulationDay = ($currentDate->copy()->diffInDays($startDate) % 7 === 6); // Setiap hari ke-7
            
            if ($isSimulationDay && $remainingMinutes >= self::TASK_DURATIONS['simulation']) {
                // Tambahkan simulasi
                $tasks[] = StudyPlanTask::create([
                    'study_plan_id' => $studyPlan->id,
                    'title' => "Simulasi TOEFL Full Test #{$weekNumber}",
                    'type' => 'simulation',
                    'estimated_minutes' => self::TASK_DURATIONS['simulation'],
                    'section' => 'all',
                    'priority' => 1, // Highest priority
                    'order' => $taskOrder++,
                    'metadata' => [
                        'scheduled_date' => $currentDate->toDateString(),
                        'week' => $weekNumber,
                        'simulation_number' => $weekNumber,
                    ],
                ]);
                
                $remainingMinutes -= self::TASK_DURATIONS['simulation'];
            }

            // Distribusikan waktu untuk section berdasarkan prioritas
            $sectionIndex = 0;
            while ($remainingMinutes >= self::MIN_SESSION_MINUTES) {
                $section = $sortedSections[$sectionIndex % count($sortedSections)];
                $priority = $sectionPriorities[$section];
                
                // Tentukan jenis aktivitas berdasarkan prioritas dan waktu
                $taskType = $this->determineTaskType($remainingMinutes, $priority);
                $duration = self::TASK_DURATIONS[$taskType] ?? self::MIN_SESSION_MINUTES;
                
                if ($remainingMinutes < $duration) {
                    break;
                }

                $taskTitle = $this->generateTaskTitle($taskType, $section, $taskOrder);

                $tasks[] = StudyPlanTask::create([
                    'study_plan_id' => $studyPlan->id,
                    'title' => $taskTitle,
                    'type' => $taskType,
                    'estimated_minutes' => $duration,
                    'section' => $section,
                    'priority' => $priority,
                    'order' => $taskOrder++,
                    'metadata' => [
                        'scheduled_date' => $currentDate->toDateString(),
                        'difficulty' => $this->calculateDifficulty($priority),
                    ],
                ]);

                $remainingMinutes -= $duration;
                $sectionIndex++;
            }

            // Jika masih ada sisa waktu, tambahkan review
            if ($remainingMinutes >= self::MIN_SESSION_MINUTES) {
                $tasks[] = StudyPlanTask::create([
                    'study_plan_id' => $studyPlan->id,
                    'title' => 'Review & Refleksi Harian',
                    'type' => 'review',
                    'estimated_minutes' => min($remainingMinutes, self::TASK_DURATIONS['review']),
                    'section' => 'all',
                    'priority' => 10, // Lowest priority
                    'order' => $taskOrder++,
                    'metadata' => [
                        'scheduled_date' => $currentDate->toDateString(),
                    ],
                ]);
            }

            // Increment week number setiap 7 hari
            if ($currentDate->copy()->diffInDays($startDate) % 7 === 6) {
                $weekNumber++;
            }

            $currentDate->addDay();
        }

        return $tasks;
    }

    /**
     * Tentukan jenis task berdasarkan waktu dan prioritas
     */
    private function determineTaskType(int $remainingMinutes, int $priority): string
    {
        if ($remainingMinutes >= self::TASK_DURATIONS['simulation'] && $priority <= 3) {
            return 'simulation';
        } elseif ($remainingMinutes >= self::TASK_DURATIONS['module']) {
            return 'module';
        } elseif ($remainingMinutes >= self::TASK_DURATIONS['practice']) {
            return 'practice';
        } else {
            return 'review';
        }
    }

    /**
     * Generate judul task yang deskriptif
     */
    private function generateTaskTitle(string $type, string $section, int $order): string
    {
        $sectionNames = [
            'reading' => 'Reading',
            'listening' => 'Listening',
            'speaking' => 'Speaking',
            'writing' => 'Writing',
        ];

        $sectionName = $sectionNames[$section] ?? ucfirst($section);

        switch ($type) {
            case 'module':
                return "Modul {$sectionName} - Bagian #" . ceil($order / 4);
            case 'practice':
                return "Latihan Soal {$sectionName}";
            case 'simulation':
                return "Simulasi TOEFL Full Test";
            case 'review':
                return "Review {$sectionName}";
            default:
                return "Aktivitas {$sectionName}";
        }
    }

    /**
     * Calculate difficulty level based on priority
     */
    private function calculateDifficulty(int $priority): string
    {
        if ($priority <= 3) {
            return 'hard';
        } elseif ($priority <= 6) {
            return 'medium';
        } else {
            return 'easy';
        }
    }

    /**
     * Regenerate study plan dengan parameter baru
     */
    public function regeneratePlan(StudyPlan $studyPlan, array $newParams): StudyPlan
    {
        DB::beginTransaction();
        
        try {
            // Hapus tasks lama
            $studyPlan->tasks()->delete();
            
            // Generate ulang dengan parameter baru
            $this->generatePlan(
                $studyPlan->user,
                $newParams['target_score'] ?? $studyPlan->target_score,
                $newParams['test_date'] ?? $studyPlan->test_date,
                $newParams['daily_hours'] ?? $studyPlan->daily_hours,
                $newParams['available_days'] ?? $studyPlan->available_days,
                $studyPlan->name
            );

            DB::commit();
            
            return $studyPlan->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
