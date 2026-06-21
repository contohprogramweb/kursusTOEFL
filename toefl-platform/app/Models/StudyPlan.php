<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'target_score',
        'test_date',
        'daily_hours',
        'available_days',
        'generated_schedule',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_score' => 'integer',
            'test_date' => 'date',
            'daily_hours' => 'decimal:2',
            'available_days' => 'array',
            'generated_schedule' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
