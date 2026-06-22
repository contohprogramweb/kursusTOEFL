<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExerciseSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'section',
        'total_questions',
        'question_ids',
        'user_answers',
        'is_completed',
        'current_question_index',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_questions' => 'integer',
            'question_ids' => 'array',
            'user_answers' => 'array',
            'is_completed' => 'boolean',
            'current_question_index' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const SECTIONS = [
        'reading' => 'Reading',
        'listening' => 'Listening',
        'speaking' => 'Speaking',
        'writing' => 'Writing',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get questions for this session
     */
    public function getQuestionsAttribute()
    {
        if (empty($this->question_ids)) {
            return collect();
        }

        return Question::whereIn('id', $this->question_ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $this->question_ids) . ')')
            ->get();
    }

    /**
     * Get current question based on index
     */
    public function getCurrentQuestionAttribute()
    {
        if (empty($this->question_ids) || $this->current_question_index >= count($this->question_ids)) {
            return null;
        }

        $questionId = $this->question_ids[$this->current_question_index];
        return Question::find($questionId);
    }

    /**
     * Save answer for a question
     */
    public function saveAnswer(int $questionId, string $answer): void
    {
        $answers = $this->user_answers ?? [];
        $answers[$questionId] = $answer;
        $this->user_answers = $answers;
        $this->save();
    }

    /**
     * Get answer for a specific question
     */
    public function getAnswer(int $questionId): ?string
    {
        return $this->user_answers[$questionId] ?? null;
    }

    /**
     * Move to next question
     */
    public function nextQuestion(): bool
    {
        if ($this->current_question_index < count($this->question_ids) - 1) {
            $this->increment('current_question_index');
            return true;
        }
        return false;
    }

    /**
     * Move to previous question
     */
    public function previousQuestion(): bool
    {
        if ($this->current_question_index > 0) {
            $this->decrement('current_question_index');
            return true;
        }
        return false;
    }

    /**
     * Mark session as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope to get active (incomplete) sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope to get completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope to filter by section
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }
}
