<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Recommendation extends Model
{
    protected $fillable = [
        'user_id',
        'simulation_id',
        'type',
        'category',
        'micro_skill',
        'title',
        'reason',
        'action_plan',
        'resource_id',
        'priority',
        'impact_score',
        'urgency_factor',
        'metadata',
        'is_read',
        'generated_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'priority' => 'integer',
        'impact_score' => 'integer',
        'urgency_factor' => 'integer',
        'metadata' => 'array',
        'generated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    /**
     * Scope to get unread recommendations
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to order by priority and impact
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority')
                     ->orderBy('impact_score', 'desc');
    }

    /**
     * Scope to order by urgency and impact
     */
    public function scopeByUrgency(Builder $query): Builder
    {
        return $query->orderBy('urgency_factor', 'desc')
                     ->orderBy('impact_score', 'desc');
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get recommendations for specific simulation
     */
    public function scopeForSimulation(Builder $query, int $simulationId): Builder
    {
        return $query->where('simulation_id', $simulationId);
    }

    /**
     * Mark recommendation as read
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Get icon based on type
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'module' => '📚',
            'practice' => '✍️',
            'simulation' => '🎓',
            'strategy' => '💡',
            'schedule' => '📅',
            default => '⭐',
        };
    }

    /**
     * Get color badge based on urgency
     */
    public function getUrgencyColorAttribute(): string
    {
        return match($this->urgency_factor) {
            5 => 'red',    // Critical - ujian dalam < 7 hari
            4 => 'orange', // High - ujian dalam 7-14 hari
            3 => 'yellow', // Medium - ujian dalam 15-30 hari
            2 => 'blue',   // Low - ujian dalam 30-60 hari
            1 => 'green',  // Very low - ujian > 60 hari
            default => 'gray',
        };
    }

    /**
     * Get urgency label
     */
    public function getUrgencyLabelAttribute(): string
    {
        return match($this->urgency_factor) {
            5 => 'Sangat Mendesak',
            4 => 'Mendesak',
            3 => 'Sedang',
            2 => 'Rendah',
            1 => 'Sangat Rendah',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Calculate days until test from metadata
     */
    public function getDaysUntilTestAttribute(): ?int
    {
        if (isset($this->metadata['test_date'])) {
            $testDate = \Carbon\Carbon::parse($this->metadata['test_date']);
            return max(0, $testDate->diffInDays(now()));
        }
        return null;
    }
}
