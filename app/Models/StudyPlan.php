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
        'start_date',
        'end_date',
        'total_tasks',
        'completed_tasks',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StudyPlanTask::class)->orderBy('order');
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
     * Calculate days remaining until end date
     */
    public function getDaysRemainingAttribute(): int
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
}
