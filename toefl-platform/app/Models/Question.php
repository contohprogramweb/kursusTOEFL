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
            'difficulty' => 'integer',
            'preparation_time' => 'integer',
            'response_time' => 'integer',
            'word_limit_min' => 'integer',
            'word_limit_max' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const QUESTION_TYPES = [
        'multiple_choice' => 'Multiple Choice',
        'fill_blank' => 'Fill in the Blank',
        'ordering' => 'Ordering',
        'speaking_task' => 'Speaking Task',
        'writing_task' => 'Writing Task',
    ];

    public const SECTIONS = [
        'reading' => 'Reading',
        'listening' => 'Listening',
        'speaking' => 'Speaking',
        'writing' => 'Writing',
    ];

    public const SOURCES = [
        'official_ets' => 'Official ETS',
        'internal' => 'Internal',
        'partner' => 'Partner',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

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

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        // Use MySQL full-text search if available
        try {
            return $query->whereRaw(
                "MATCH(question_text, passage_text) AGAINST(? IN BOOLEAN MODE)",
                [$searchTerm]
            );
        } catch (\Exception $e) {
            // Fallback to LIKE search
            return $query->where(function ($q) use ($searchTerm) {
                $q->where('question_text', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('passage_text', 'LIKE', "%{$searchTerm}%");
            });
        }
    }

    public function scopeWithSkills($query, $skillIds)
    {
        if (empty($skillIds)) {
            return $query;
        }

        return $query->whereHas('skills', function ($q) use ($skillIds) {
            $q->whereIn('micro_skills.id', $skillIds);
        });
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
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

    public function getQuestionTypeLabelAttribute(): string
    {
        return self::QUESTION_TYPES[$this->question_type] ?? $this->question_type;
    }

    public function getSectionLabelAttribute(): string
    {
        return self::SECTIONS[$this->section] ?? $this->section;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Validate that question has between 1 and 3 micro-skills
     */
    public function validateSkillCount(): bool
    {
        $skillCount = $this->skills()->count();
        return $skillCount >= 1 && $skillCount <= 3;
    }
}
