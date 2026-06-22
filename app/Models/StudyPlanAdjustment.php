<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanAdjustment extends Model
{
    protected $fillable = [
        'study_plan_id',
        'user_id',
        'task_id',
        'adjustment_type',
        'reason',
        'adjusted_at',
    ];

    protected $casts = [
        'adjusted_at' => 'datetime',
    ];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(StudyPlanTask::class, 'task_id');
    }
}
