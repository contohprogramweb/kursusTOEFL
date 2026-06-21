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
    ];

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

    public function template(): BelongsTo
    {
        return $this->belongsTo(SimulationTemplate::class, 'template_id');
    }
}
