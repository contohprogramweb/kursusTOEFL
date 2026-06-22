<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationTemplateSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'section',
        'order_index',
        'duration_minutes',
        'question_count',
        'break_after',
        'break_duration',
        'section_result_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'duration_minutes' => 'integer',
            'question_count' => 'integer',
            'break_after' => 'boolean',
            'break_duration' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the template this section belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SimulationTemplate::class, 'template_id');
    }

    /**
     * Get the section result for this template section
     */
    public function sectionResult(): BelongsTo
    {
        return $this->belongsTo(SectionResult::class, 'section_result_id');
    }

    /**
     * Scope to get sections ordered by order_index
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    /**
     * Check if this section has a break after it
     */
    public function hasBreak(): bool
    {
        return $this->break_after && $this->break_duration > 0;
    }

    /**
     * Get the total time including break (if any)
     */
    public function getTotalTimeMinutes(): int
    {
        return $this->duration_minutes + ($this->break_after ? $this->break_duration : 0);
    }
}
