<?php

namespace App\Services;

use App\Models\Streak;
use App\Models\StreakFreeze;
use App\Models\Badge;
use App\Models\User;
use App\Models\ExerciseSession;
use App\Models\SimulationResult;
use App\Notifications\StreakWarningNotification;
use App\Notifications\BadgeEarnedNotification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GamificationService
{
    /**
     * Minimum learning duration in minutes to count for streak
     */
    protected const MIN_LEARNING_MINUTES = 15;

    /**
     * Record learning activity and update streak
     */
    public function recordActivity(int $userId, int $durationMinutes): void
    {
        DB::transaction(function () use ($userId, $durationMinutes) {
            $today = now()->toDateString();
            
            // Get or create streak record
            $streak = Streak::firstOrCreate(
                ['user_id' => $userId],
                [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_activity_date' => null,
                    'freezes_used' => 0,
                ]
            );

            // If already active today, just update duration tracking (if needed)
            if ($streak->last_activity_date && $streak->last_activity_date->toDateString() === $today) {
                return; // Already counted for today
            }

            // Check if this activity qualifies (>= 15 minutes)
            if ($durationMinutes < self::MIN_LEARNING_MINUTES) {
                return;
            }

            // Calculate new streak
            $yesterday = now()->subDay()->toDateString();
            $isContinuation = $streak->last_activity_date && 
                              $streak->last_activity_date->toDateString() === $yesterday;

            if ($isContinuation || $streak->isDateFrozen($yesterday)) {
                $streak->current_streak += 1;
            } else {
                // Check if yesterday was frozen
                if (!$streak->isDateFrozen($yesterday) && $streak->current_streak > 0) {
                    // Streak broken - could send notification here
                }
                $streak->current_streak = 1;
            }

            // Update longest streak
            if ($streak->current_streak > $streak->longest_streak) {
                $streak->longest_streak = $streak->current_streak;
            }

            $streak->last_activity_date = now();
            $streak->save();

            // Check for badge achievements
            $this->checkStreakBadges($userId, $streak->current_streak);
        });
    }

    /**
     * Use a streak freeze
     */
    public function useFreeze(int $userId, string $reason, ?string $notes = null): array
    {
        $streak = Streak::where('user_id', $userId)->first();

        if (!$streak) {
            return ['success' => false, 'message' => 'Streak tidak ditemukan'];
        }

        if (!$streak->canUseFreeze()) {
            return ['success' => false, 'message' => 'Anda sudah menggunakan freeze minggu ini (max 1x/minggu)'];
        }

        $success = $streak->useFreeze($reason, $notes);

        if ($success) {
            return ['success' => true, 'message' => 'Streak freeze berhasil digunakan'];
        }

        return ['success' => false, 'message' => 'Gagal menggunakan streak freeze'];
    }

    /**
     * Check and award streak-related badges
     */
    protected function checkStreakBadges(int $userId, int $currentStreak): void
    {
        // Consistency 7 days
        if ($currentStreak >= 7) {
            Badge::award($userId, 'consistency_7');
        }

        // Consistency 30 days
        if ($currentStreak >= 30) {
            Badge::award($userId, 'consistency_30');
        }

        // Comeback King - 14 days after break (simplified check)
        if ($currentStreak >= 14) {
            Badge::award($userId, 'comeback_king');
        }
    }

    /**
     * Check and award first step badge
     */
    public function checkFirstStepBadge(int $userId): void
    {
        $completedExercise = ExerciseSession::where('user_id', $userId)
            ->where('is_completed', true)
            ->exists();

        if ($completedExercise) {
            Badge::award($userId, 'first_step');
        }
    }

    /**
     * Check and award performance-based badges
     */
    public function checkPerformanceBadges(int $userId, array $scores): void
    {
        // Master Reader - Reading >= 25
        if (isset($scores['reading']) && $scores['reading'] >= 25) {
            Badge::award($userId, 'master_reader');
        }

        // Listening Master - Listening >= 25
        if (isset($scores['listening']) && $scores['listening'] >= 25) {
            Badge::award($userId, 'listening_master');
        }

        // Speaking Pro - Speaking >= 25
        if (isset($scores['speaking']) && $scores['speaking'] >= 25) {
            Badge::award($userId, 'speaking_pro');
        }

        // Writing Expert - Writing >= 25
        if (isset($scores['writing']) && $scores['writing'] >= 25) {
            Badge::award($userId, 'writing_expert');
        }

        // Perfect Score - Total >= 110
        $totalScore = array_sum($scores);
        if ($totalScore >= 110) {
            Badge::award($userId, 'perfect_score');
        }
    }

    /**
     * Check and award speed demon badge
     */
    public function checkSpeedDemonBadge(int $userId, int $expectedDurationMinutes, int $actualDurationMinutes): void
    {
        // Finished 10 minutes faster than expected
        if ($expectedDurationMinutes - $actualDurationMinutes >= 10) {
            Badge::award($userId, 'speed_demon');
        }
    }

    /**
     * Check and award time-based badges (Night Owl, Early Bird, Weekend Warrior)
     */
    public function checkTimeBasedBadges(int $userId): void
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;

        // Night Owl - studying after 10 PM
        if ($hour >= 22) {
            Badge::award($userId, 'night_owl');
        }

        // Early Bird - studying before 6 AM
        if ($hour < 6) {
            Badge::award($userId, 'early_bird');
        }

        // Weekend Warrior - Saturday (6) or Sunday (0)
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            Badge::award($userId, 'weekend_warrior');
        }
    }

    /**
     * Send streak warning notifications (called by scheduler)
     */
    public function sendStreakWarnings(): void
    {
        $activeStudents = User::where('role', User::ROLE_STUDENT)
            ->where('status', 'active')
            ->get();

        foreach ($activeStudents as $user) {
            $streak = Streak::where('user_id', $user->id)->first();

            if (!$streak || $streak->current_streak === 0) {
                continue;
            }

            // Check if user has activity today
            $hasActivityToday = $streak->last_activity_date && 
                               $streak->last_activity_date->toDateString() === now()->toDateString();

            if ($hasActivityToday) {
                continue;
            }

            // Check if today is frozen
            if ($streak->isTodayFrozen()) {
                continue;
            }

            // Send warning 6 hours before midnight (at 6 PM)
            if (now()->hour === 18) {
                $user->notify(new StreakWarningNotification($streak->current_streak, 'will_break'));
            }
        }
    }

    /**
     * Calculate and update streaks nightly (called by scheduler)
     */
    public function calculateNightlyStreaks(): void
    {
        $yesterday = now()->subDay()->toDateString();
        
        $streaks = Streak::whereNotNull('last_activity_date')
            ->get();

        foreach ($streaks as $streak) {
            $lastActivity = $streak->last_activity_date->toDateString();

            // If last activity was before yesterday and not frozen
            if ($lastActivity < $yesterday && !$streak->isDateFrozen($yesterday)) {
                // Streak is broken
                $streak->current_streak = 0;
                $streak->save();
            }
        }
    }

    /**
     * Get user's gamification stats
     */
    public function getUserStats(int $userId): array
    {
        $streak = Streak::where('user_id', $userId)->first();
        $badges = Badge::getUserBadges($userId);
        
        return [
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'last_activity' => $streak?->last_activity_date,
            'total_badges' => $badges->count(),
            'total_points' => $badges->sum('points'),
            'badges' => $badges->map(fn($badge) => [
                'code' => $badge->badge_code,
                'name' => $badge->badge_name,
                'icon' => $badge->badge_icon,
                'description' => $badge->badge_description,
                'points' => $badge->points,
                'awarded_at' => $badge->awarded_at,
                'is_public' => $badge->is_public,
            ]),
            'can_use_freeze' => $streak?->canUseFreeze() ?? true,
            'freeze_reset_date' => $streak?->freeze_reset_date,
        ];
    }

    /**
     * Toggle badge visibility
     */
    public function toggleBadgeVisibility(int $userId, int $badgeId, bool $isPublic): bool
    {
        $badge = Badge::where('id', $badgeId)
            ->where('user_id', $userId)
            ->first();

        if (!$badge) {
            return false;
        }

        $badge->is_public = $isPublic;
        $badge->save();

        return true;
    }

    /**
     * Get leaderboard (optional, per institution)
     */
    public function getLeaderboard(?int $institutionId = null, string $type = 'streak', int $limit = 10)
    {
        $query = User::where('role', User::ROLE_STUDENT)
            ->where('status', 'active');

        if ($institutionId) {
            $query->whereHas('institution', fn($q) => $q->where('id', $institutionId));
        }

        $users = $query->with('streak')->get();

        if ($type === 'streak') {
            $users = $users->sortByDesc(fn($u) => $u->streak?->current_streak ?? 0)
                ->take($limit);
        } elseif ($type === 'badges') {
            $users = $users->sortByDesc(fn($u) => Badge::where('user_id', $u->id)->count())
                ->take($limit);
        } elseif ($type === 'points') {
            $users = $users->sortByDesc(fn($u) => Badge::where('user_id', $u->id)->sum('points'))
                ->take($limit);
        }

        return $users->map(fn($user) => [
            'user_id' => $user->id,
            'name' => $user->full_name,
            'avatar' => $user->profile?->avatar_url,
            'streak' => $user->streak?->current_streak ?? 0,
            'badges_count' => Badge::where('user_id', $user->id)->count(),
            'points' => Badge::where('user_id', $user->id)->sum('points'),
        ])->values();
    }
}
