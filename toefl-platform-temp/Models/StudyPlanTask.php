<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanTask extends Model
{
    protected $fillable = [
        'study_plan_id',
        'title',
        'type',
        'estimated_minutes',
        'section',
        'priority',
        'metadata',
        'resource_id',
        'is_completed',
        'completed_at',
        'order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'estimated_minutes' => 'integer',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StudyPlanAdjustment::class);
    }

    /**
     * Scope to get incomplete tasks
     */
    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope to get tasks by section
     */
    public function scopeBySection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope to order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Mark task as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get metadata value
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->is_completed) {
            return false;
        }

        $scheduledDate = $this->getMetadataValue('scheduled_date');
        if (!$scheduledDate) {
            return false;
        }

        return now()->isAfter(\carbon\Carbon::parse($scheduledDate)->endOfDay());
    }
}
