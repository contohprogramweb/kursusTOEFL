<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'mode',
        'total_duration',
        'is_default',
        'status',
        'created_by',
        'institution_id',
        'is_locked',
    ];

    /**
     * Available simulation modes
     */
    const MODE_PRACTICE = 'practice';
    const MODE_SCHEDULED = 'scheduled';
    const MODE_REALISTIC = 'realistic';
    const MODE_FOCUS = 'focus';

    /**
     * State machine statuses for simulation results
     */
    const STATUS_INITIATED = 'initiated';
    const STATUS_READING = 'reading';
    const STATUS_LISTENING = 'listening';
    const STATUS_BREAK = 'break';
    const STATUS_SPEAKING = 'speaking';
    const STATUS_WRITING = 'writing';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADING = 'grading';
    const STATUS_COMPLETED = 'completed';

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'total_duration' => 'integer',
            'is_default' => 'boolean',
            'is_locked' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get locked templates (cannot be deleted)
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope to get templates for a specific institution
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where(function ($q) use ($institutionId) {
            $q->whereNull('institution_id')  // Global templates
              ->orWhere('institution_id', $institutionId);  // Institution-specific
        });
    }

    /**
     * Get all sections in this template ordered by order_index
     */
    public function sections(): HasMany
    {
        return $this->hasMany(SimulationTemplateSection::class, 'template_id')
                    ->orderBy('order_index');
    }

    /**
     * Get all simulation results using this template
     */
    public function simulationResults(): HasMany
    {
        return $this->hasMany(SimulationResult::class, 'template_id');
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the institution this template belongs to (if any)
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get institutions this template is assigned to (B2B)
     */
    public function assignedInstitutions(): HasMany
    {
        return $this->hasMany(InstitutionSimulationTemplate::class, 'template_id');
    }

    /**
     * Check if this template can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_locked && !$this->is_default;
    }

    /**
     * Get the next status in the state machine
     */
    public static function getNextStatus(string $currentStatus): ?string
    {
        $stateMachine = [
            self::STATUS_INITIATED => self::STATUS_READING,
            self::STATUS_READING => self::STATUS_LISTENING,
            self::STATUS_LISTENING => self::STATUS_BREAK,
            self::STATUS_BREAK => self::STATUS_SPEAKING,
            self::STATUS_SPEAKING => self::STATUS_WRITING,
            self::STATUS_WRITING => self::STATUS_SUBMITTED,
            self::STATUS_SUBMITTED => self::STATUS_GRADING,
            self::STATUS_GRADING => self::STATUS_COMPLETED,
            self::STATUS_COMPLETED => null,
        ];

        return $stateMachine[$currentStatus] ?? null;
    }

    /**
     * Get all valid status transitions
     */
    public static function getValidTransitions(): array
    {
        return [
            self::STATUS_INITIATED => [self::STATUS_READING],
            self::STATUS_READING => [self::STATUS_LISTENING],
            self::STATUS_LISTENING => [self::STATUS_BREAK],
            self::STATUS_BREAK => [self::STATUS_SPEAKING],
            self::STATUS_SPEAKING => [self::STATUS_WRITING],
            self::STATUS_WRITING => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_GRADING],
            self::STATUS_GRADING => [self::STATUS_COMPLETED],
        ];
    }

    /**
     * Check if a status transition is valid
     */
    public static function isValidTransition(string $fromStatus, string $toStatus): bool
    {
        $validTransitions = self::getValidTransitions();
        
        if (!isset($validTransitions[$fromStatus])) {
            return false;
        }

        return in_array($toStatus, $validTransitions[$fromStatus]);
    }

    /**
     * Get sections grouped by type
     */
    public function getSectionsByType(): array
    {
        return $this->sections->groupBy('section')->toArray();
    }
}
