<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Model untuk AI Grading Results
 * Menyimpan hasil penilaian AI untuk Speaking & Writing
 */
class AiGradingResult extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ai_grading_results';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'gradable_type',
        'gradable_id',
        'dimension',
        'score',
        'max_score',
        'feedback',
        'highlights',
        'confidence',
        'model_version',
        'transcript',
        'submission_content',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'highlights' => 'array',
        'confidence' => 'decimal:2',
        'score' => 'decimal:2',
        'max_score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the parent gradable model (SpeakingSubmission or WritingSubmission).
     */
    public function gradable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope untuk mendapatkan hasil yang perlu manual review.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('confidence', '<', 70)
                     ->orWhere('status', 'pending_review');
    }

    /**
     * Scope untuk mendapatkan hasil dengan confidence tinggi.
     */
    public function scopeHighConfidence($query, $threshold = 80)
    {
        return $query->where('confidence', '>=', $threshold);
    }

    /**
     * Check apakah hasil ini perlu manual review.
     */
    public function needsReview(): bool
    {
        return $this->confidence < 70 || $this->status === 'pending_review';
    }

    /**
     * Mark hasil sebagai sudah direview.
     */
    public function markAsReviewed(?int $reviewerId = null): self
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Get formatted highlights untuk frontend.
     */
    public function getFormattedHighlightsAttribute(): array
    {
        return collect($this->highlights ?? [])
            ->map(function ($highlight) {
                return [
                    'position' => [
                        'start' => $highlight['position']['start'] ?? $highlight['start_index'] ?? 0,
                        'end' => $highlight['position']['end'] ?? $highlight['end_index'] ?? 0,
                    ],
                    'type' => $highlight['type'] ?? 'grammar_error',
                    'message' => $highlight['message'] ?? $highlight['explanation'] ?? '',
                    'suggestion' => $highlight['suggestion'] ?? $highlight['correction'] ?? '',
                    'example' => $highlight['example'] ?? $highlight['correct_form'] ?? '',
                    'timestamp' => $highlight['timestamp'] ?? null,
                    'confidence' => $highlight['confidence'] ?? null,
                ];
            })
            ->sortBy('position.start')
            ->values()
            ->toArray();
    }

    /**
     * Get statistics dari content.
     */
    public function getStatisticsAttribute(): array
    {
        $content = $this->transcript ?? $this->submission_content ?? '';
        
        // Word count
        $words = preg_split('/\s+/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        // Sentence count
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        // Average sentence length
        $avgSentenceLength = $sentenceCount > 0 
            ? round($wordCount / $sentenceCount, 1) 
            : 0;

        // Unique words count
        $uniqueWords = collect($words)
            ->map(fn($word) => strtolower(preg_replace('/[^a-z0-9]/i', '', $word)))
            ->filter()
            ->unique()
            ->count();

        return [
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'avg_sentence_length' => $avgSentenceLength,
            'unique_words_count' => $uniqueWords,
        ];
    }

    /**
     * Tambahkan highlight baru.
     */
    public function addHighlight(array $highlight): self
    {
        $highlights = $this->highlights ?? [];
        $highlights[] = $highlight;
        
        // Sort by position
        $highlights = collect($highlights)
            ->sortBy(fn($h) => $h['position']['start'] ?? $h['start_index'] ?? 0)
            ->values()
            ->toArray();

        $this->update(['highlights' => $highlights]);

        return $this;
    }

    /**
     * Update confidence score dan tentukan apakah perlu review.
     */
    public function updateConfidence(float $confidence): self
    {
        $this->update([
            'confidence' => $confidence,
            'status' => $confidence < 70 ? 'pending_review' : 'completed',
        ]);

        return $this;
    }
}
