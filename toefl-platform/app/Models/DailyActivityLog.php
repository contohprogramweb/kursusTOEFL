<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DailyActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_date',
        'study_duration_seconds',
        'questions_solved',
        'simulations_taken',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create today's activity log
     */
    public static function getOrCreateToday(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'activity_date' => now()->toDateString()],
            [
                'study_duration_seconds' => 0,
                'questions_solved' => 0,
                'simulations_taken' => 0,
            ]
        );
    }

    /**
     * Add study duration
     */
    public function addStudyDuration(int $seconds): void
    {
        $this->increment('study_duration_seconds', $seconds);
    }

    /**
     * Increment questions solved count
     */
    public function incrementQuestionsSolved(int $count = 1): void
    {
        $this->increment('questions_solved', $count);
    }

    /**
     * Increment simulations taken count
     */
    public function incrementSimulationsTaken(int $count = 1): void
    {
        $this->increment('simulations_taken', $count);
    }

    /**
     * Format study duration as HH:MM
     */
    public function getFormattedStudyDurationAttribute(): string
    {
        $hours = floor($this->study_duration_seconds / 3600);
        $minutes = floor(($this->study_duration_seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
