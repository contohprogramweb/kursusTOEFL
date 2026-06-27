<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanReminder extends Model
{
    protected $fillable = [
        'user_id',
        'study_plan_id',
        'task_id',
        'scheduled_date',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(StudyPlanTask::class, 'task_id');
    }

    /**
     * Scope to get pending reminders
     */
    public function scopePending($query)
    {
        return $query->where('is_sent', false)
                    ->where('scheduled_date', '<=', now()->toDateString());
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }
}
