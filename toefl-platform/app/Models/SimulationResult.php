<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'template_id',
        'mode',
        'start_time',
        'end_time',
        'total_score',
        'status',
        'current_section_index',
        'section_times',
        'paused_at',
        'total_paused_seconds',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'total_score' => 'decimal:2',
            'current_section_index' => 'integer',
            'section_times' => 'array',
            'paused_at' => 'datetime',
            'total_paused_seconds' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Scope to get completed simulations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', SimulationTemplate::STATUS_COMPLETED);
    }

    /**
     * Scope to get simulations in progress
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            SimulationTemplate::STATUS_INITIATED,
            SimulationTemplate::STATUS_READING,
            SimulationTemplate::STATUS_LISTENING,
            SimulationTemplate::STATUS_BREAK,
            SimulationTemplate::STATUS_SPEAKING,
            SimulationTemplate::STATUS_WRITING,
        ]);
    }

    /**
     * Scope to get simulations awaiting grading
     */
    public function scopeAwaitingGrading($query)
    {
        return $query->whereIn('status', [
            SimulationTemplate::STATUS_SUBMITTED,
            SimulationTemplate::STATUS_GRADING,
        ]);
    }

    /**
     * Get the user who took this simulation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template used for this simulation
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SimulationTemplate::class, 'template_id');
    }

    /**
     * Get all section results for this simulation
     */
    public function sectionResults(): HasMany
    {
        return $this->hasMany(SectionResult::class, 'result_id');
    }

    /**
     * Check if the simulation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === SimulationTemplate::STATUS_COMPLETED;
    }

    /**
     * Check if the simulation is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            SimulationTemplate::STATUS_INITIATED,
            SimulationTemplate::STATUS_READING,
            SimulationTemplate::STATUS_LISTENING,
            SimulationTemplate::STATUS_BREAK,
            SimulationTemplate::STATUS_SPEAKING,
            SimulationTemplate::STATUS_WRITING,
        ]);
    }

    /**
     * Transition to the next status in the state machine
     */
    public function transitionToNextStatus(): bool
    {
        $nextStatus = SimulationTemplate::getNextStatus($this->status);
        
        if ($nextStatus === null) {
            return false;
        }

        $this->status = $nextStatus;
        
        // Update current section index based on new status
        $this->updateSectionIndexForStatus($nextStatus);
        
        return $this->save();
    }

    /**
     * Transition to a specific status (with validation)
     */
    public function transitionTo(string $newStatus): bool
    {
        if (!SimulationTemplate::isValidTransition($this->status, $newStatus)) {
            throw new \InvalidArgumentException(
                "Invalid transition from {$this->status} to {$newStatus}"
            );
        }

        $this->status = $newStatus;
        $this->updateSectionIndexForStatus($newStatus);
        
        if ($newStatus === SimulationTemplate::STATUS_COMPLETED) {
            $this->end_time = now();
        }
        
        return $this->save();
    }

    /**
     * Update current_section_index based on status
     */
    protected function updateSectionIndexForStatus(string $status): void
    {
        $sectionMap = [
            SimulationTemplate::STATUS_READING => 0,
            SimulationTemplate::STATUS_LISTENING => 1,
            SimulationTemplate::STATUS_BREAK => 2,
            SimulationTemplate::STATUS_SPEAKING => 3,
            SimulationTemplate::STATUS_WRITING => 4,
        ];

        if (isset($sectionMap[$status])) {
            $this->current_section_index = $sectionMap[$status];
        }
    }

    /**
     * Get the current section result
     */
    public function getCurrentSectionResult(): ?SectionResult
    {
        $statusToSection = [
            SimulationTemplate::STATUS_READING => 'reading',
            SimulationTemplate::STATUS_LISTENING => 'listening',
            SimulationTemplate::STATUS_SPEAKING => 'speaking',
            SimulationTemplate::STATUS_WRITING => 'writing',
        ];

        if (!isset($statusToSection[$this->status])) {
            return null;
        }

        return $this->sectionResults()
            ->where('section', $statusToSection[$this->status])
            ->first();
    }

    /**
     * Record time spent on a section
     */
    public function recordSectionTime(string $section, int $seconds): void
    {
        $times = $this->section_times ?? [];
        $times[$section] = ($times[$section] ?? 0) + $seconds;
        $this->section_times = $times;
        $this->save();
    }

    /**
     * Pause the simulation
     */
    public function pause(): void
    {
        if ($this->paused_at === null) {
            $this->paused_at = now();
            $this->save();
        }
    }

    /**
     * Resume the simulation
     */
    public function resume(): void
    {
        if ($this->paused_at !== null) {
            $pausedDuration = $this->paused_at->diffInSeconds(now());
            $this->total_paused_seconds += $pausedDuration;
            $this->paused_at = null;
            $this->save();
        }
    }

    /**
     * Check if the simulation is currently paused
     */
    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    /**
     * Get total elapsed time excluding pauses
     */
    public function getElapsedTimeSeconds(): int
    {
        if ($this->start_time === null) {
            return 0;
        }

        $endTime = $this->end_time ?? now();
        $totalSeconds = $this->start_time->diffInSeconds($endTime);
        
        return $totalSeconds - $this->total_paused_seconds;
    }
}
