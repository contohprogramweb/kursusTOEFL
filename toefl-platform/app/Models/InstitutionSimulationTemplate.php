<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionSimulationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'template_id',
        'is_required',
        'assigned_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'assigned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the institution this assignment belongs to
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the template being assigned
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SimulationTemplate::class, 'template_id');
    }

    /**
     * Get the user who assigned this template
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get required templates for an institution
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to get optional templates for an institution
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
