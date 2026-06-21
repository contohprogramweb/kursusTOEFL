<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'instructor_id',
        'title',
        'description',
        'assignment_type',
        'target_id',
        'deadline',
        'reminder_1_day',
        'reminder_1_hour',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'reminder_1_day' => 'boolean',
            'reminder_1_hour' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeUpcoming($query)
    {
        return $query->where('deadline', '>', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now());
    }

    public function class_(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(InstructorFeedback::class, 'assignment_id');
    }
}
