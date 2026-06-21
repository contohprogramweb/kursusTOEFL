<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionResponse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_result_id',
        'question_id',
        'selected_option_id',
        'text_response',
        'audio_url',
        'is_correct',
        'time_spent_seconds',
        'flagged',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'time_spent_seconds' => 'integer',
            'flagged' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sectionResult(): BelongsTo
    {
        return $this->belongsTo(SectionResult::class, 'section_result_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
