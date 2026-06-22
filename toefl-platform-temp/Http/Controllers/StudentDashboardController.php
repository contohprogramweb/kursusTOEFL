<?php

namespace App\Http\Controllers;

use App\Models\StudyPlan;
use App\Models\Recommendation;
use App\Models\Badge;
use App\Models\DailyActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentDashboardController extends Controller
{
    /**
     * Display the student dashboard.
     * Target load time: <= 3 seconds
     */
    public function index()
    {
        $user = Auth::user();
        $cacheKey = "dashboard_data_user_{$user->id}";

        // Cache dashboard data for 5 minutes to ensure fast load times
        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            return $this->buildDashboardData($user);
        });

        return view('student.dashboard', $data);
    }

    /**
     * Build all dashboard data with optimized queries.
     */
    private function buildDashboardData($user): array
    {
        $today = Carbon::today();

        // 1. Ringkasan Hari Ini (Single query)
        $todayStats = DailyActivityLog::where('user_id', $user->id)
            ->where('activity_date', $today)
            ->first();

        $summary = [
            'study_time' => $todayStats ? $this->formatDuration($todayStats->study_duration_seconds) : '00:00',
            'questions_solved' => $todayStats ? $todayStats->questions_solved : 0,
            'simulations_taken' => $todayStats ? $todayStats->simulations_taken : 0,
        ];

        // 2. Rekomendasi Harian (Eager loaded, limited)
        $recommendations = Recommendation::where('user_id', $user->id)
            ->unread()
            ->byPriority()
            ->limit(5)
            ->get();

        // 3. Status Study Plan (Eager loading dengan constraint)
        $studyPlan = StudyPlan::with([
            'tasks' => fn($q) => $q->incomplete()->orderBy('order')->limit(1)
        ])
        ->where('user_id', $user->id)
        ->active()
        ->orderBy('created_at', 'desc')
        ->first();

        // 4. Streak Calculation
        $streak = $this->calculateStreak($user->id);

        // 5. Lencana Terbaru (Optimized join query)
        $badges = Badge::join('user_badges', 'badges.id', '=', 'user_badges.badge_id')
            ->where('user_badges.user_id', $user->id)
            ->orderBy('user_badges.earned_at', 'desc')
            ->limit(4)
            ->select('badges.*', 'user_badges.earned_at')
            ->get();

        // 6. Skor Terakhir & Trend (Efficient query)
        $lastSimulation = DB::table('simulation_results')
            ->where('user_id', $user->id)
            ->orderBy('completed_at', 'desc')
            ->first();

        $trend = 'flat';
        $scoreValue = 0;

        if ($lastSimulation) {
            $scoreValue = $lastSimulation->total_score;

            $prevSimulation = DB::table('simulation_results')
                ->where('user_id', $user->id)
                ->where('id', '<', $lastSimulation->id)
                ->orderBy('completed_at', 'desc')
                ->first();

            if ($prevSimulation) {
                $trend = $scoreValue > $prevSimulation->total_score 
                    ? 'up' 
                    : ($scoreValue < $prevSimulation->total_score ? 'down' : 'flat');
            }
        }

        return compact(
            'summary',
            'recommendations',
            'studyPlan',
            'streak',
            'badges',
            'lastSimulation',
            'trend',
            'scoreValue'
        );
    }

    /**
     * Format seconds to HH:MM format.
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Calculate consecutive day streak.
     */
    private function calculateStreak(int $userId): int
    {
        $dates = DailyActivityLog::where('user_id', $userId)
            ->where('study_duration_seconds', '>', 0)
            ->orderBy('activity_date', 'desc')
            ->pluck('activity_date')
            ->map(fn($d) => $d->toDateString());

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::today();

        foreach ($dates as $dateStr) {
            $logDate = Carbon::parse($dateStr);
            $diffInDays = $logDate->diffInDays($currentDate, false);

            // Allow same day or consecutive days
            if ($diffInDays <= 1) {
                $streak++;
                $currentDate = $logDate;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Clear dashboard cache (for testing or manual refresh).
     */
    public function refresh()
    {
        $user = Auth::user();
        Cache::forget("dashboard_data_user_{$user->id}");

        return redirect()->route('student.dashboard')
            ->with('success', 'Dasbor telah diperbarui.');
    }
}
