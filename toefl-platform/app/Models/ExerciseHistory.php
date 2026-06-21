<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExerciseHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'exercise_type',
        'section',
        'mode',
        'score',
        'total_questions',
        'correct_answers',
        'duration_seconds',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'total_questions' => 'integer',
            'correct_answers' => 'integer',
            'duration_seconds' => 'integer',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
