<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class StudyPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'target_score',
        'test_date',
        'daily_hours',
        'available_days',
        'ai_notes',
        'is_ai_generated',
        'start_date',
        'end_date',
        'total_tasks',
        'completed_tasks',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'test_date' => 'date',
        'daily_hours' => 'decimal:1',
        'available_days' => 'array',
        'is_completed' => 'boolean',
        'is_ai_generated' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StudyPlanTask::class)->orderBy('order');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StudyPlanAdjustment::class);
    }

    /**
     * Get the next incomplete task for this study plan
     */
    public function getNextTaskAttribute()
    {
        return $this->tasks()->where('is_completed', false)->orderBy('order')->first();
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_tasks === 0) {
            return 0;
        }
        return round(($this->completed_tasks / $this->total_tasks) * 100);
    }

    /**
     * Calculate days remaining until test date
     */
    public function getDaysRemainingAttribute(): int
    {
        $targetDate = $this->test_date ?? $this->end_date;
        $diff = now()->startOfDay()->diffInDays($targetDate->startOfDay(), false);
        return max(0, $diff);
    }

    /**
     * Calculate days remaining until end date (original behavior)
     */
    public function getDaysUntilEndDateAttribute(): int
    {
        $diff = now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false);
        return max(0, $diff);
    }

    /**
     * Check if study plan is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    /**
     * Scope to get active study plans
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '>=', now());
    }

    /**
     * Get available days as array
     */
    public function getAvailableDaysArrayAttribute(): array
    {
        return $this->available_days ?? [1, 2, 3, 4, 5]; // Default: Mon-Fri
    }

    /**
     * Check if a specific day of week is available (0=Sunday, 6=Saturday)
     */
    public function isDayAvailable(int $dayOfWeek): bool
    {
        $availableDays = $this->available_days ?? [1, 2, 3, 4, 5];
        return in_array($dayOfWeek, $availableDays);
    }

    /**
     * Calculate total estimated minutes for all tasks
     */
    public function getTotalEstimatedMinutesAttribute(): int
    {
        return $this->tasks()->sum('estimated_minutes');
    }
}
