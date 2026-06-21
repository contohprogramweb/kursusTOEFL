<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstructorFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'instructor_id',
        'section',
        'dimension',
        'score',
        'text_feedback',
        'audio_feedback_url',
        'highlights',
        'is_draft',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'score' => 'decimal:2',
            'is_draft' => 'boolean',
            'submitted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('is_draft', false)
            ->whereNotNull('submitted_at');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
