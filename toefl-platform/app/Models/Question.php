<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section',
        'question_type',
        'question_text',
        'passage_text',
        'audio_url',
        'image_url',
        'difficulty',
        'explanation',
        'source',
        'correct_answer',
        'preparation_time',
        'response_time',
        'word_limit_min',
        'word_limit_max',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'preparation_time' => 'integer',
            'response_time' => 'integer',
            'word_limit_min' => 'integer',
            'word_limit_max' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(MicroSkill::class, 'question_skills', 'question_id', 'skill_id')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
