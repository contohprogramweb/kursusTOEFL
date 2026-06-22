<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AIGradingQueue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_result_id',
        'type',
        'reason',
        'transcript',
        'essay_text',
        'ai_response',
        'ai_confidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
    ];

    protected function casts(): array
    {
        return [
            'ai_response' => 'array',
            'ai_confidence' => 'decimal:4',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sectionResult(): BelongsTo
    {
        return $this->belongsTo(SectionResult::class, 'section_result_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to get pending items for manual review
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get items by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get items by reason
     */
    public function scopeOfReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Mark as in review
     */
    public function markAsInReview(?User $reviewer = null): void
    {
        $this->update([
            'status' => 'in_review',
            'reviewed_by' => $reviewer?->id,
        ]);
    }

    /**
     * Mark as completed with reviewer notes
     */
    public function markAsCompleted(string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
        ]);
    }
}
